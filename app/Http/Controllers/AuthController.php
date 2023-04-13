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
use Illuminate\Auth\AuthenticationException;
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
use Laravel\Sanctum\PersonalAccessToken;
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
     * @return JsonResponse
     * @throws AuthenticationException
     */
    public function signIn(Request $request): JsonResponse
    {
        $this->validate($request, [
            "email" => "required|email",
            "password" => "required|string|min:6",
        ]);
        if (!auth()->attempt($request->only("email", "password"))) {
            throw new AuthenticationException();
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
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            "email" => "required|string|email|max:255|unique:users",
            "password" => "required|string|min:6",
        ]);
        if ($validator->fails()) {
            return response()->json(
                $validator->errors()->toJson(),
                Response::HTTP_BAD_REQUEST
            );
        }
        $input = $request->all();
        $input["password"] = Hash::make($input["password"]);
        $user = User::create([...$input, "organizer_commission" => null]);
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

    public function login(
        LoginRequest        $request,
        FirebaseAuthService $firebaseAuthService
    ): JsonResponse
    {
        $user = $firebaseAuthService->getOrCreateUser($request->firebase_token);
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
        $user->loadCount("unreadNotifications", "followers", "follows");
        $user->loadCount("events");
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
     * @param Request|null $request
     * @return JsonResponse
     */
    public function logout(?Request $request): JsonResponse
    {
        Auth::user()
            ->currentAccessToken()
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
        //        Log::info("update : ".json_encode($profileRequest->all()));
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
