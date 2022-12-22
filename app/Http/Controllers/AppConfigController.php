<?php

namespace App\Http\Controllers;

use App\Http\Resources\AppConfigResource;
use App\Models\AppConfig;

class AppConfigController extends Controller
{
    public function appConfig(): AppConfigResource
    {
        return new AppConfigResource(AppConfig::latest()->first());
    }
}
