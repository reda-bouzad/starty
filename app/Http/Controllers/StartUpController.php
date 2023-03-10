<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;

class StartUpController extends Controller
{
    public function getStartUps()
    {
        return User::select(["id", "lastname", "firstname", "description", "is_verified", "show_pseudo_only"])
            ->whereNotNull('firstname')
            ->whereNotNull('lastname')
            ->withCount(['followers', 'follows'])
            ->where('user_type', '!=', 'administrator')
            ->withCount('events')
            ->orderByDesc('is_verified')
            ->orderByDesc('events_count')
            ->take(10)->get()->map(fn($u) => new UserResource($u));
    }
}
