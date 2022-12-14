<?php

namespace App\Services;

use App\Models\User;
use Kreait\Firebase\Auth;
use Kreait\Firebase\Auth\UserInfo;
use Kreait\Laravel\Firebase\Facades\Firebase;

class FirebaseAuthService
{

    public function getOrCreateUser(string $firebase_token) : User
    {
        /** @var Auth $auth */
        $auth = app('firebase.auth');
        $verifiedIdToken= $auth->verifyIdToken($firebase_token);
        $uid = $verifiedIdToken->claims()->get('sub');

        $data = $auth->getUser($uid);
        $unique = [];
        $params =[
        "firebase_uuid" => $data->uid
    ];
        if($data->email){
            $unique["email"] = $data->email;
        }
        if($data->phoneNumber){
            $unique['phone_number'] = $data->phoneNumber;
        }
        return User::withoutGlobalScopes()->updateOrCreate($unique, $params);

    }
}
