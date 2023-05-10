<?php

namespace App\Services;

use App\Models\User;
use Kreait\Firebase\Auth;
use Kreait\Firebase\Auth\UserInfo;
use Kreait\Firebase\Exception\AuthException;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Laravel\Firebase\Facades\Firebase;

class FirebaseAuthService
{
    /**
     * @param string $firebase_token
     * @return User|null
     * @throws AuthException
     * @throws FirebaseException
     */
    public function getOrCreateUser(string $firebase_token): User|null
    {
        /** @var Auth $auth */
        $auth = app("firebase.auth");
        $verifiedIdToken = $auth->verifyIdToken($firebase_token);
        $uid = $verifiedIdToken->claims()->get("sub");

        $data = $auth->getUser($uid);
        if (
            !User::where("firebase_uuid", $uid)->exists() &&
            $data->phoneNumber == null
        ) {
            return null;
        }

        $unique = [];
        $params = [
            "firebase_uuid" => $data->uid,
        ];
        if ($data->phoneNumber) {
            $unique["phone_number"] = $data->phoneNumber;
        }
        if ($data->email) {
            $unique["email"] = $data->email;
        }

        if (count($unique) === 0) {
            $unique = [
                "email" => $data->uid . "@private.com",
            ];
        }
        return User::withoutGlobalScopes()->updateOrCreate($unique, $params);
    }

    /**
     * @param string $email
     * @return bool
     * @throws AuthException
     * @throws FirebaseException
     */
    public function verifyEmail(string $email): bool
    {
        /** @var Auth $auth */
        $auth = app("firebase.auth");
        $users = $auth->listUsers();
        $emailList = [];
        foreach ($users as $user) {
            if ($user->email) {
                $emailList[] = $user->email;
            }
        }
        return in_array($email, $emailList);
    }
}
