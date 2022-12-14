<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class StartUpController extends Controller
{
    public function getStartUps(){
        return User::select(["id","lastname","firstname","description","is_verified"])
            ->whereNotNull('firstname')
            ->whereNotNull('lastname')
            ->withCount('events')
            ->orderByDesc('is_verified')
            ->orderByDesc('events_count')
            ->take(10)
            ->get();
    }
}
