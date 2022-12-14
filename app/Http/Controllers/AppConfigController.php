<?php

namespace App\Http\Controllers;

use App\Http\Resources\AppConfigResource;
use App\Models\AppConfig;
use Illuminate\Http\Request;

class AppConfigController extends Controller
{
    public function appConfig(){
        return new AppConfigResource(AppConfig::latest()->first());
    }
}
