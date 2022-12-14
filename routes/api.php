<?php

use App\Http\Controllers\AppConfigController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\FollowerController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\StartUpController;
use App\Http\Controllers\ReportController;
use App\Models\EventParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Broadcast::routes(['middleware' => ['auth:sanctum']]);

// Authentification

Route::post('/login', [AuthController::class, 'login']);

Route::get('/profile', [AuthController::class, 'me'])->middleware('auth:sanctum');
Route::get('/profile/my-followers', [AuthController::class, 'myFollowers'])->middleware('auth:sanctum');


Route::post('/add-profile-picture', [AuthController::class, 'addProfilePicture'])->middleware('auth:sanctum');

Route::post('/update-profile', [AuthController::class, 'updateProfile'])->middleware('auth:sanctum');

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::delete('/account/delete', [AuthController::class, 'deleteAccount'])->middleware('auth:sanctum');

Route::group(['middleware' => ['auth:sanctum', 'languable']], function () {
    Route::post('/verification/send', [AuthController::class, 'sendEmailCode']);
    Route::post('/verification/verify', [AuthController::class, 'verifyCode']);
    Route::get('/stripe-status', [AuthController::class, 'stripeAccountStatus']);
    //Evènement

    Route::post('/create-event', [EventController::class, 'createEvent']);

    Route::post('/add-images/{event}', [EventController::class, 'addImagesToEvent']);
    Route::post('/add-single-image/{event}', [EventController::class, 'addSingleImageToEvent']);

    Route::get('/event/{event}', [EventController::class, 'getEvent']);

    Route::post('/update-event/{event}', [EventController::class, 'updateEvent']);


    Route::get('/upcoming-events', [EventController::class, 'getUpcomingEvents']);

    Route::get('/my-parties', [EventController::class, 'myParties']);
    Route::get('/like-events', [EventController::class, 'getMyFavoriteEvents']);

    Route::put('/join-event/{event}', [EventController::class, 'joinEvent']);
    Route::put('/toggle-like-event/{event}', [EventController::class, 'toggleLikeEvent']);

    Route::delete('/delete-event-image/{media}', [EventController::class, 'deleteEventImage']);

    Route::post('/search-event', [EventController::class, 'searchEvent']);

    Route::get('events/{event}/{uuid}/scan', [EventController::class, 'scannedQrcode']);
    Route::put('events/{event}/scan/{user}', [EventController::class, 'scanned2Qrcode']);
    Route::get('events/{event}/participants', [EventController::class, 'participants']);
    Route::put('events/{event}/accept/{user}', [EventController::class, 'acceptRequest']);
    Route::put('events/{event}/leave',[EventController::class,'leaveTheEvent']);
    Route::put('events/{event}/reject/{user}', [EventController::class, 'rejectRequest']);
    Route::put('events/{event}/report', [EventController::class, 'report']);
    Route::delete('/events/{event}', [EventController::class, 'deleteEvent']);
    Route::put('events/{event}/block', [EventController::class, 'toggleBlock']);
    Route::post('events/{event}/review', [ReviewController::class, 'create']);
    Route::get('events/{event}/review', [ReviewController::class, 'eventReviews']);
    Route::get('events/to-reviews-list', [ReviewController::class, 'reviewsList']);


    //Messages


    Route::get('/chats', [ChatController::class, 'getChats']);
    Route::post('/chats', [ChatController::class, 'createChat']);

    Route::get('/chats/{chat}/messages', [ChatController::class, 'getChatMessages']);
    Route::get('/chats/{chat}/messages/after', [ChatController::class, 'getLastMessagesAfterId']);
    Route::get('/chats/{chat}/members', [ChatController::class, 'getChatMembers']);
    Route::post('/chats/{chat}/messages', [ChatController::class, 'createMessage']);
    Route::put('/chats/{chat}/mark-as-read', [ChatController::class, 'markChatAsRead']);
    Route::put('/chats/{chat}/add-to-group', [ChatController::class, 'addToGroup']);
    Route::put('/chats/{chat}/remove-from-group', [ChatController::class, 'removeToGroup']);
    Route::post('/chats/{chat}/update-image', [ChatController::class, 'addImageToGroup']);
    Route::put('/chats/{chat}', [ChatController::class, 'updateName']);
    Route::delete('/chats/{chat}', [ChatController::class, 'deleteGroup']);
    Route::put('/chats/{chat}/archive', [ChatController::class, 'archiveChat']);
    Route::put('/chats/{chat}/unarchive', [ChatController::class, 'unArchiveChat']);
    Route::get('/chats/archives', [ChatController::class, 'archiveList']);
    Route::put('/messages/{message}/mark-as-read', [ChatController::class, 'markAsRead']);
    Route::delete('/messages/{message}', [ChatController::class, 'deleteMessage']);


    Route::get('start-ups', [StartUpController::class, 'getStartUps']);

    //notifications
    Route::get('notifications', [NotificationController::class, 'getNotifications']);
    Route::put('notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
    Route::put('notifications/{notification_id}/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::delete('notifications/{notification_id}', [NotificationController::class, 'deleteNotification']);


    //users/followers

    Route::get('/users', [FollowerController::class, 'users']);
    Route::get('/users/{user}', [FollowerController::class, 'details']);
    Route::get('/users/{user}/followers', [FollowerController::class, 'getUserFollowers']);
    Route::get('/users/{user}/parties', [FollowerController::class, 'userParties']);
    Route::put('/users/{user}/toggle-follow', [FollowerController::class, 'toggleFollow']);
    Route::put('/users/{user}/report', [FollowerController::class, 'report']);
    Route::put('/users/{user}/block', [FollowerController::class, 'toggleBlock']);
    Route::get('/users/{user}/blocked', [FollowerController::class, 'blocked']);
    Route::get('/users/{user}/followers', [FollowerController::class, 'followers']);
    Route::get('/users/{user}/follows', [FollowerController::class, 'follows']);
    Route::get('/users/{user}/networks', [FollowerController::class, 'networks']);

    //report

    Route::get('reports', [ReportController::class, 'lists']);

    //payments
    Route::get('payments/{event}/intent', [PaymentController::class, 'getPaymentIntent']);

    Route::get('payments/account-link', [PaymentController::class, 'accountLink']);
    Route::get('payments/dashboard-link', [PaymentController::class, 'dashboardAccountLink']);
    Route::get('payments/{event}/checkout-link', [PaymentController::class, 'paymentLink']);
    Route::get('payments/{event}/status', [PaymentController::class, 'paymentStatus']);

});
Route::get('payments/account-link/refresh', [PaymentController::class, 'refreshAccountLink'])->name('stripe-refresh');
Route::get('app-config', [AppConfigController::class, 'appConfig']);
Route::post('stripe/general-callback', [PaymentController::class, 'paymentCallback']);
Route::any('webhook', [PaymentController::class, 'webhook']);