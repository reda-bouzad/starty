<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Http\Resources\UserResource;
use App\Models\ChatUser;
use App\Models\Party;
use App\Models\Follow;
use App\Models\ModelReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\SpatialBuilder;

class FollowerController extends Controller
{

        public function getUserFollowers(Request $request, User $user): AnonymousResourceCollection
        {
             return  UserResource::collection($user
                 ->followers()
                 ->orderBy('lastname')
                 ->orderBy('firstname')
                 ->paginate($request->input('per_page',30)));
        }

        public function toggleFollow(User $user): array
        {
            if(Follow::query()->where('user_id',$user->id)->where('follower_id',Auth::id())->exists()){
                $user->followers()->detach(\Auth::id());
                return [
                    "follow" => false
                ];
            }
            $user->followers()->attach(\Auth::id());

            // on follow le user $user, donc on met tous les messages que $user aurait créer à direct
            ChatUser::where('user_id', Auth::id())
                ->whereHas('chat',function($query) use($user){
                    $query->where('created_by',$user->id);
                })
                ->update(["state" => "direct"]);
            return [
                "follow" => true
            ];

        }

        public function users(Request $request): AnonymousResourceCollection
        {

            $query =  User::query()
                ->regular()
                ->where('id','!=',Auth::id())
                ->when($request->lat && $request->long && $request->radius, function (SpatialBuilder $query) use ($request) {
                    $center = new Point($request->get('lat', 0.0), $request->get('long', 0.0));
                    return $query->whereDistanceSphere('last_location', $center, '<=', $request->radius)
                        ->withDistanceSphere('last_location', $center)
                        ->orderByDistanceSphere('last_location', $center);
                })->where(function($query) use($request){
                    $query->when($request->search, function (Builder $query) use ($request) {
                        $lower = Str::lower($request->search);
                        $upper = Str::upper($request->search);
                        return $query->where('firstname', 'like', "%$lower%")
                            ->orWhere('firstname', 'like', "%$upper%")
                            ->orWhere('lastname', 'like', "%$upper%")
                            ->orWhere('lastname', 'like', "%$lower%");
                    })
                        ->select(['id','firstname','lastname']);
                });

            $users = $query->paginate($request->input('per_page',20));
            return UserResource::collection($users);
        }

        public function details( int $user): UserResource
        {
           $user = User::withCount(['followers','follows'])
               ->where('id',$user)
               ->first();
           return new UserResource($user);
        }

        public function userParties(Request $request, int $user): AnonymousResourceCollection
        {
            $events = Party::query()
                ->orderByDesc('created_at')
                ->withCount('participants')
                ->when($request->start, function ($query) use ($request) {
                    return $query->where('start_at','>=', $request->start);
                })
                ->when($request->end, function ($query) use ($request) {
                    return $query->where('start_at','<=', $request->end);
                })
                ->where(function($query)use($user){
                    $query->where('user_id', $user)
                         ->orWhereHas('acceptedParticipants', function ($query) use ($user) {
                                return $query->where('users.id', $user);
                            })->orWhereHas('scannedParticipants', function (Builder $query) use ($user) {
                                return $query->where('users.id', $user);
                            });
                })->paginate($request->input('per_page',20));

            return EventResource::collection($events);
        }


    /**
     * @throws ValidationException
     */
    public function report(Request $request, User $user) {

        $this->validate($request,[
            "report_id" => ["required","exists:reports,id"]
        ]);

        ModelReport::updateOrCreate([
            "user_id" => Auth::id(),
            "model_type" => User::class,
            "model_id" => $user->id,
            "report_id" => $request->report_id
        ],[ ]);

    }

    public function toggleBlock($user): array
    {
        $user = User::withoutGlobalScopes()->find($user);

            if(collect($user->blocked_by ?? [])->contains(Auth::id())){
                $user->blocked_by = collect($user->blocked_by)->reject(fn($el) => $el === Auth::id())->values()->all();
                Auth::user()->blocked_user = collect(Auth::user()->blocked_user)->reject(fn($el) => $el === $user->id)->values()->all();
                Auth::user()->save();
                $user->save();
                return [
                    "blocked" => false
                ];
            }else{
                $user->blocked_by = array_merge([Auth::id()],$user->blocked_by ?? []);
                Auth::user()->blocked_user = array_merge([$user->id],Auth::user()->blocked_user ?? []);
            }

        $user->save();
        Auth::user()->save();
        return [
            "blocked" => true
        ];
    }


    public function blocked(Request $request, User $user): AnonymousResourceCollection
    {
            $per_page = $request->input('per_page',20);
            $user = User::select(['id','firstname','lastname'])->whereIn('id',$user->blocked_user ?? [])->paginate($per_page);
            return UserResource::collection($user);
    }

    public function followers(Request $request, User $user): AnonymousResourceCollection
    {
            $per_page = $request->input('per_page',20);
            $followers =   $user->followers()
                ->withCount('followers','follows')
                ->paginate($per_page);

            return UserResource::collection($followers);
    }
    public function follows(Request $request, User $user): AnonymousResourceCollection
    {
            $per_page = $request->input('per_page',20);
            $followers =   $user->follows()
                ->withCount('followers','follows')
                ->paginate($per_page);
            return UserResource::collection($followers);
    }

    public function networks(Request $request, User $user): AnonymousResourceCollection
    {
            $networks = User::query()
                ->withCount(['followers','follows'])
                ->where(function($query) use($user){
                    $query->whereHas('followers',fn($query) => $query->where('follower_id',$user->id))
                        ->orWhereHas('follows',fn($query) => $query->where('user_id',$user->id));
                })
                ->where('id','!=',Auth::id())
                ->when($request->lat && $request->long && $request->radius, function (SpatialBuilder $query) use ($request) {
                    $center = new Point($request->get('lat', 0.0), $request->get('long', 0.0));
                    return $query->whereDistanceSphere('last_location', $center, '<=', $request->radius)
                        ->withDistanceSphere('last_location', $center)
                        ->orderByDistanceSphere('last_location', $center);
                })
                ->where(function($query) use($request){
                    $query->when($request->search, function (Builder $query) use ($request) {
                        $lower = Str::lower($request->search);
                        $upper = Str::upper($request->search);
                        return $query->where('firstname', 'like', "%$lower%")
                            ->orWhere('firstname', 'like', "%$upper%")
                            ->orWhere('lastname', 'like', "%$upper%")
                            ->orWhere('lastname', 'like', "%$lower%");
                    });
                })
                ->select(['id','firstname','lastname'])
                ->paginate();

            return UserResource::collection($networks);
    }


}
