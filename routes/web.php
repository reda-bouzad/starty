<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Jenssegers\Agent\Agent;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::post('/apple/callback',[AuthController::class,'appleCallback']);

Route::get('/', function () {
    return redirect('/admin');
});
Route::get('/getStartyApp', function(){
    $agent = new Agent();
    if($agent->isAndroidOS()) {
        return response()->redirectTo('https://play.google.com/store/apps/details?id=com.startyworld.app');
    }
    if( $agent->isiPad() || $agent->isiPadOS() || $agent->isSafari() || $agent->is('OS X')){
         return response()->redirectTo('https://apps.apple.com/fr/app/starty-app/id1634387021?l=en');
    }

       return response()->redirectTo('https://play.google.com/store/apps/details?id=com.startyworld.app');
});
