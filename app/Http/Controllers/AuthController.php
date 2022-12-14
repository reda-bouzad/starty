<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\ProfileRequest;
use App\Http\Resources\UserResource;
use App\Mail\SendCodeMail;
use App\Models\AppConfig;
use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Notifications\SendEmailCode;
use App\Services\FirebaseAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use MatanYadaev\EloquentSpatial\Objects\Point;

class AuthController extends Controller
{

    public function login(LoginRequest $request, FirebaseAuthService $firebaseAuthService)
    {
        $user = $firebaseAuthService->getOrCreateUser($request->firebase_token);
        $token = $user->createToken(\Str::random(14));
        $user->phone_number = $user->phone_number ?? $request->phone_number;
        $user->save();

        return response()->json([
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    public function me()
    {
        $user = Auth::user();
        $user->load('jointEvents:id', 'likeEvents:id', 'follows:id', 'followers:id');
        $user->loadCount('unreadNotifications', 'followers', 'follows');
        return response()->json(new UserResource($user), 200);
    }

    public function myFollowers()
    {
        return UserResource::collection(Auth::user()->followers()->paginate());
    }

    public function logout()
    {
        Auth::user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnecté',
        ]);
    }

    public function updateProfile(ProfileRequest $profileRequest)
    {
        $user = Auth::user();
        $location = null;
        if ($profileRequest->lat && $profileRequest->long) {
            $location = new Point($profileRequest->lat, $profileRequest->long);
        }
        $token = $profileRequest->has('fcm_token') ? $profileRequest->fcm_token : $user->fcm_token;

        // C'est un user qui avait commencé l'inscription avec le numéro de telephone il n'a pas terminé
        if ($profileRequest->has('phone_number')) {
            $badUser = User::where('phone_number', $profileRequest->phone_number)->where('id', '!=', Auth::id())
                ->whereNull('firstname')
                ->whereNull('lastname')
                ->first();
            optional($badUser)->delete();
        }
        // C'est un user qui avait commencé l'inscription avec l'email il n'a pas terminé
        if ($profileRequest->has('email')) {
            $badUser = User::where('email', $profileRequest->email)->where('id', '!=', Auth::id())
                ->whereNull('firstname')
                ->whereNull('lastname')
                ->first();
            optional($badUser)->delete();
        }

        if ($user->revolut_customer_id) {
            $res = Http::withToken(AppConfig::first()->revolut_pk)->patch(env('REVOLUT_BASE_URL') . "customers/" . $user->revolut_customer_id, [
                "full_name" => $profileRequest->firstname . ' ' . $profileRequest->lastname,
            ]);
            if ($res->status() != 200) {
                return response()->json(["message" => "server_problem"], 500);
            }
        }
        $user->update([
            'firstname' => $profileRequest->firstname ?? $user->firstname,
            'lastname' => $profileRequest->lastname ?? $user->lastname,
            'gender' => $profileRequest->gender ?? $user->gender,
            'birth_date' => $profileRequest->date('birth_date') ?? $user->birth_date,
            'description' => $profileRequest->description ?? $user->description,
            'fcm_token' => $token,
            "last_location" => $location ?? $user->last_location,
            'address' => $profileRequest->address ?? $user->address,
            'phone_number' => $profileRequest->phone_number ?? $user->phone_number,
            'email' => $profileRequest->email ?? $user->email,
            'lang' => $profileRequest->lang ?? $user->lang,
            'preferred_radius' => $profileRequest->preferred_radius ?? $user->preferred_radius
        ]);

        if ($profileRequest->hasFile('avatar') && $profileRequest->file('avatar')->isValid()) {
            $user->save();
            $user->addMediaFromRequest('avatar')->toMediaCollection('avatar');
        }


        return response()->json($user, 200);
    }

    public function addProfilePicture(Request $request)
    {
        $user = Auth::user();

        if ($request->hasFile('avatar')) {
            $user->addMediaFromRequest('avatar')->toMediaCollection('avatar');
        }
        if ($request->hasFile('selfie')) {
            $user->addMediaFromRequest('selfie')->toMediaCollection('self_image');
        }
        $user->append('selfie');
        return response()->json($user, 200);
    }


    public function appleCallback(Request $request)
    {
        $redirect = "intent://callback?" . http_build_query($request->all()) . "#Intent;package=com.startyworld.app;scheme=signinwithapple;end";
        return response()->redirectTo($redirect);
    }


    public function deleteAccount()
    {
        Auth::user()->delete();
    }

    public function sendEmailCode(Request $request)
    {
        $request->validate([
            "email" => [
                "required",
                "email",
                Rule::unique('users', 'email')
                    ->whereNotNull('firstname')
                    ->whereNotNull('lastname')
                    ->ignore(\Auth::id())
            ]
        ]);
        $code = random_int(100000, 999999);
        EmailVerificationCode::updateOrCreate(
            ["email" => $request->email],
            ["code" => $code]
        );
        Mail::to([$request->email])->send(new SendCodeMail($code));
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            "email" => "required|email",
            "code" => "required"
        ]);
        return [
            "exists" => EmailVerificationCode::where('email', $request->email)->where('code', $request->code)->exists()
        ];
    }

    public function stripeAccountStatus()
    {
        return [
            "stripe_account_status" => \Auth::user()->stripe_account_status
        ];
    }

}
