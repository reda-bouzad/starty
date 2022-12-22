<?php

namespace App\Http\Controllers;

use App\Models\AppConfig;
use App\Models\EventParticipant;
use App\Models\Party;
use App\Models\User;
use Auth;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{

    public function paymentLink(Party $event): JsonResponse
    {


        if (!Auth::user()->revolut_customer_id) {

            $res = Http::withToken(AppConfig::first()->revolut_pk)->post(env('REVOLUT_BASE_URL') . "customers", [
                "email" => Auth::user()?->email,
                "full_name" => Auth::user()->firstname . ' ' . Auth::user()->lastname,
                "phone" => Auth::user()?->phone_number
            ]);
            Log::info($res->status());
            if ($res->status() != 201) {
                if ($res->json('code') == 1018) {
                    $cus = Http::withToken(AppConfig::first()->revolut_pk)->get(env('REVOLUT_BASE_URL') . "customers");
                    $id = collect($cus->object())->firstWhere(fn($x) => $x->email == Auth::user()->email)->id;
                    User::where(["id" => Auth::id()])->update(["revolut_customer_id" => $id]);
                } else {
                    return response()->json(["message" => "server_problem"], 500);
                }
            } else {
                User::where(["id" => Auth::id()])->update(["revolut_customer_id" => $res->json("id")]);
            }
        }


        $res = Http::withToken(AppConfig::first()->revolut_pk)->post(env('REVOLUT_BASE_URL') . "orders", [
            "amount" => $event->price * 100,
            "description" => 'événement "' . $event->label . '" pour "' . $event->user->firstname . ' ' . $event->user->lastname . '"',
            "currency" => $event->devise,
            "customer_id" => Auth::user()->revolut_customer_id,
            "capture_mode" => $event->type == "public" ? "AUTOMATIC" : "MANUAL",
            "metadata" => [
                "user_id" => Auth::id(),
                "event_id" => $event->id,
            ]
        ]);

        if (
            !EventParticipant::where([
                "event_id" => $event->id,
                "user_id" => Auth::id()
            ])->exists()
        ) {
            EventParticipant::Create([
                "user_id" => Auth::id(),
                "event_id" => $event->id,
                "accepted" => false,
                "rejected" => false,
                "status" => $res->json("state"),
                "payment_intent_id" => $res->json('id'),
                "payment_processing" => true
            ]);
        } else {
            EventParticipant::where([
                "event_id" => $event->id,
                "user_id" => Auth::id()
            ])->update([
                    "accepted" => false,
                    "rejected" => false,
                    "status" => $res->json("state"),
                    "payment_intent_id" => $res->json('id'),
                    "payment_processing" => true
                ]);
        }

        return response()->json(["url" => $res->json('checkout_url')]);
    }

    public function paymentStatus(int $event): array
    {
        try {

            $event_participant = EventParticipant::getElement($event, Auth::id());

            error_log($event_participant);


            $res = Http::withToken(AppConfig::first()->revolut_pk)->get(env('REVOLUT_BASE_URL') . "orders/" . $event_participant->payment_intent_id);


            if ($event_participant) {

                EventParticipant::where([
                    "event_id" => $event,
                    "user_id" => Auth::id()
                ])->first()->
                update(["status" => $res->json("state")]);
                return [
                    "payment_intent_id" => optional($event_participant)->payment_intent_id,
                    "status" => $res->json('state'),
                    "paid" => $res->json('state') == "COMPLETED"
                ];
            }
            return [
                "payment_intent_id" => null,
                "paid" => false
            ];
        } catch (Exception) {
            return [
                "payment_intent_id" => null,
                "paid" => false
            ];
        }
    }

    public function webhook(Request $request): JsonResponse
    {

        switch ($request->get('event')) {
            case "ORDER_COMPLETED":
            {
                $res = Http::withToken(AppConfig::first()->revolut_pk)->
                get(env('REVOLUT_BASE_URL') . "orders/" . $request->get('order_id'));


                $metadata = $res->json('metadata');
                EventParticipant::where([
                    "event_id" => $metadata['event_id'],
                    "user_id" => $metadata['user_id']
                ])->first()?->update(["payment_intent_id" => $res->json('id'), "accepted" => true, "status" => "COMPLETED"]);
                break;
            }
            case "ORDER_PAYMENT_DECLINED":
            case "ORDER_PAYMENT_FAILED":
            {
                $res = Http::withToken(AppConfig::first()->revolut_pk)->
                post(env('REVOLUT_BASE_URL') . "orders/" . $request->get('order_id') . "/cancel");
                if ($res->ok()) {
                    EventParticipant::where(["payment_intent_id" => $request->get('order_id')])->delete();
                }
                break;
            }
            case "ORDER_AUTHORISED":
            {
                EventParticipant::where(["payment_intent_id" => $request->get('order_id')])->update(["payment_processing" => false]);
                break;
            }
        }

        return response()->json(["message" => "ok"]);
    }
}
