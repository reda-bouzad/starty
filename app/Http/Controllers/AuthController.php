<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\ProfileRequest;
use App\Http\Resources\UserResource;
use App\Mail\SendCodeMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Services\FirebaseAuthService;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Kreait\Firebase\Exception\FirebaseException;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Str;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * Login With Email and Password
     *
     * @param Request $request
     * @return Application|ResponseFactory|JsonResponse|\Illuminate\Http\Response
     */
    public function signIn(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "email" => "required|email|string",
                "password" => "required|string|min:6",
            ],
            [
                "email.required" => "email_required",
                "email.string" => "email_not_string",
                "email.email" => "email_invalid",
                "password.required" => "password_required",
                "password.string" => "password_not_string",
                "password.min" => "password_min",
            ]
        );
        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $error) {
                $errorMessage = implode($error);
                return response($errorMessage, Response::HTTP_BAD_REQUEST);
            }
        }
        if (!auth()->attempt($request->only("email", "password"))) {
            return response("incorrect_user", Response::HTTP_UNAUTHORIZED);
        }
        $user = auth()->user();
        $token = $user->createToken(Str::random(32));
        $user->loadCount("events");
        return response()->json([
            "access_token" => $token->plainTextToken,
            "token_type" => "Bearer",
            "user" => $user,
        ]);
    }

    /**
     * Register User
     *
     * @param Request $request
     * @return Application|ResponseFactory|JsonResponse|\Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "pseudo" => "required|string",
                "email" => "required|string|email|max:255|unique:users,email",
                "password" => "required|string|min:6",
            ],
            [
                "pseudo.required" => "pseudo_required",
                "pseudo.string" => "pseudo_not_string",
                "email.required" => "email_required",
                "email.string" => "email_not_string",
                "email.email" => "email_invalid",
                "email.max" => "email_max",
                "email.unique" => "email_unique",
                "password.required" => "password_required",
                "password.string" => "password_not_string",
                "password.min" => "password_min",
            ]
        );
        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $error) {
                $errorMessage = implode($error);
                return response($errorMessage, Response::HTTP_BAD_REQUEST);
            }
        }
        $input = $request->all();
        $user = User::create([
            "pseudo" => $input["pseudo"],
            "email" => $input["email"],
            "password" => Hash::make($input["password"]),
            "organizer_commission" => null,
        ]);
        $token = $user->createToken(Str::random(32));
        $user->loadCount("events");
        return response()->json(
            [
                "status" => "success",
                "message" => "User created successfully",
                "access_token" => $token->plainTextToken,
                "token_type" => "Bearer",
                "user" => $user,
            ],
            Response::HTTP_CREATED
        );
    }

    /**
     * @throws FirebaseException
     */
    public function login(
        LoginRequest $request,
        FirebaseAuthService $firebaseAuthService
    ): JsonResponse {
        $user = $firebaseAuthService->getOrCreateUser($request->firebase_token);
        error_log($request->phone_number);
        if ($user == null) {
            return response()->json(
                "you_cant_register_with_social_network",
                422
            );
        }
        $token = $user->createToken(Str::random(14));
        $user->phone_number = $user->phone_number ?? $request->phone_number;
        $user->save();
        $user->loadCount("events");
        Log::channel("stderr")->error($user->events_count);

        return response()->json([
            "access_token" => $token->plainTextToken,
            "token_type" => "Bearer",
            "user" => $user,
        ]);
    }

    public function me(): JsonResponse
    {
        $user = Auth::user();
        $user->load(
            "jointEvents:id",
            "likeEvents:id",
            "follows:id",
            "followers:id"
        );
        $user->loadCount(
            "unreadNotifications",
            "followers",
            "follows",
            "events"
        );
        //        $user->loadCount("events");
        return response()->json(new UserResource($user));
    }

    public function myFollowers(): AnonymousResourceCollection
    {
        return UserResource::collection(
            Auth::user()
                ->followers()
                ->paginate()
        );
    }

    /**
     * Logout from Starty App
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $request
            ->user()
            ->tokens()
            ->delete();
        return response()->json([
            "message" => "Log out",
        ]);
    }

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function updateProfile(ProfileRequest $profileRequest): JsonResponse
    {
        $user = Auth::user();
        $location = null;
        if ($profileRequest->lat && $profileRequest->long) {
            $location = new Point($profileRequest->lat, $profileRequest->long);
        }
        $token = $profileRequest->has("fcm_token")
            ? $profileRequest->fcm_token
            : $user->fcm_token;
        // C'est un user qui avait commencé l'inscription avec le numéro de telephone il n'a pas terminé
        if ($profileRequest->has("phone_number")) {
            $badUser = User::where(
                "phone_number",
                $profileRequest->phone_number
            )
                ->where("id", "!=", Auth::id())
                ->whereNull("firstname")
                ->whereNull("lastname")
                ->first();
            optional($badUser)->delete();
        }
        // C'est un user qui avait commencé l'inscription avec l'email il n'a pas terminé
        if ($profileRequest->has("email")) {
            $badUser = User::where("email", $profileRequest->email)
                ->where("id", "!=", Auth::id())
                ->whereNull("firstname")
                ->whereNull("lastname")
                ->first();
            optional($badUser)->delete();
        }
        //                Log::info("update : ".json_encode($profileRequest->all()));
        Log::info($user);
        $user->update([
            "firstname" => $profileRequest->firstname ?? $user->firstname,
            "lastname" => $profileRequest->lastname ?? $user->lastname,
            "pseudo" => $profileRequest->pseudo ?? $user->pseudo,
            "show_pseudo_only" =>
                $profileRequest->show_pseudo_only ?? $user->show_pseudo_only,
            "gender" => $profileRequest->gender ?? $user->gender,
            "birth_date" =>
                $profileRequest->date("birth_date") ?? $user->birth_date,
            "description" => $profileRequest->description ?? $user->description,
            "fcm_token" => $token,
            "last_location" => $location ?? $user->last_location,
            "address" => $profileRequest->address ?? $user->address,
            "phone_number" =>
                $profileRequest->phone_number ?? $user->phone_number,
            "email" => $profileRequest->email ?? $user->email,
            "lang" => $profileRequest->lang ?? $user->lang,
            "preferred_radius" =>
                $profileRequest->preferred_radius ?? $user->preferred_radius,
        ]);
        $user->loadCount("events");

        if (
            $profileRequest->hasFile("avatar") &&
            $profileRequest->file("avatar")->isValid()
        ) {
            $user->save();
            $user->addMediaFromRequest("avatar")->toMediaCollection("avatar");
        }
        return response()->json($user);
    }

    /**
     * @throws FileIsTooBig
     * @throws FileDoesNotExist
     */
    public function addProfilePicture(Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($request->hasFile("avatar")) {
            $user->addMediaFromRequest("avatar")->toMediaCollection("avatar");
        }
        if ($request->hasFile("selfie")) {
            $user
                ->addMediaFromRequest("selfie")
                ->toMediaCollection("self_image");
        }
        $user->append("selfie");
        return response()->json($user);
    }

    public function appleCallback(Request $request): RedirectResponse
    {
        $redirect =
            "intent://callback?" .
            http_build_query($request->all()) .
            "#Intent;package=com.startyworld.app;scheme=signinwithapple;end";
        return response()->redirectTo($redirect);
    }

    public function deleteAccount()
    {
        Auth::user()->delete();
    }

    /**
     * @throws Exception
     */
    public function sendEmailCode(Request $request)
    {
        $request->validate([
            "email" => [
                "required",
                "email",
                Rule::unique("users", "email")
                    ->whereNotNull("firstname")
                    ->whereNotNull("lastname")
                    ->ignore(\Auth::id()),
            ],
        ]);
        $code = random_int(100000, 999999);
        EmailVerificationCode::updateOrCreate(
            ["email" => $request->email],
            ["code" => $code]
        );
        Mail::to([$request->email])->send(new SendCodeMail($code));
    }

    public function verifyCode(Request $request): array
    {
        $request->validate([
            "email" => "required|email",
            "code" => "required",
        ]);
        return [
            "exists" => EmailVerificationCode::where("email", $request->email)
                ->where("code", $request->code)
                ->exists(),
        ];
    }
}
