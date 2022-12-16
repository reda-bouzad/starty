<?php

namespace App\Http\Controllers;

use App\Models\AppConfig;
use App\Models\EventParticipant;
use App\Models\Party;
use App\Models\User;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookReceived;
use Stripe\Exception\SignatureVerificationException;
use Stripe\WebhookSignature;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PaymentController extends Controller
{
    public function getPaymentIntent(Party $event)
    {
        $user = Auth::user();
        $price = $event->price * 100;
        $organiser = User::find($event->user_id);
        if (!$organiser->stripe_account) {
            $organiser->createAccount();
        }
        $event_participant = EventParticipant::updateOrCreate([
            "user_id" => Auth::id(),
            "event_id" => $event->id
        ]);
        if ($event_participant->payment_intent_id) {
            $intent = Cashier::stripe()->paymentIntents->retrieve($event_participant->payment_intent_id);
            if ($intent->status == "require_capture") {
                return [
                    "client_secret" => $intent['client_secret'],
                    "id" => $intent['id']
                ];
            } else if ($intent->status == "success") {
                return [
                    "id" => $intent['id'],
                    "status" => "success"
                ];
            }
        }
        $param = [
            'amount' => $price,
            'currency' => Str::lower($event->devise),
            'payment_method_types' => ['card'],
            'application_fee_amount' => settings('payment_config', 'fee', 0.10) * $price,
            'statement_descriptor' => Str::limit("Starty {$event->label}", 22),
            'transfer_data' => [
                'destination' => $organiser->stripe_account,
            ],
            'capture_method' => 'manual',
            'metadata' => [
                "starty_event_id" => $event->id,
                "starty_user_id" => $user->id
            ]
        ];

        if ($user->email) {
            $param['receipt_email'] = $user->email;
        }

        $intent = Cashier::stripe()->paymentIntents->create($param);
        EventParticipant::updateOrCreate([
            "user_id" => Auth::id(),
            "event_id" => $event->id
        ], ['payment_intent_id' => $intent->id, 'payment_processing' => true]);
        return [
            "client_secret" => $intent['client_secret'],
            "id" => $intent['id']
        ];
    }

    public function accountLink(Request $request)
    {
        $user = Auth::user();
        if (!$user->stripe_account) {
            $user->createAccount();
        }
        $token = encrypt(Auth::id() . "|" . now()->toIso8601String());
        $data = Cashier::stripe()->accountLinks->create([
            'account' => $user->stripe_account,
            'refresh_url' => route('stripe-refresh') . "?token=$token",
            'return_url' => $request->return_url ?? 'https://app.startyworld.com/sh/stripecallback',
            'type' => 'account_onboarding',
        ]);
        return response()->json($data);
    }

    public function refreshAccountLink(Request $request)
    {
        $data = explode("|", decrypt($request->token));
        if (Carbon::parse($data[1])->isBefore(now()->subMinutes(10))) {
            return abort(419);
        }
        $user = User::findOrFail($data[0]);
        $token = encrypt($user->id . "|" . now()->toIso8601String());
        $data = Cashier::stripe()->accountLinks->create([
            'account' => $user->stripe_account,
            'refresh_url' => route('stripe-refresh') . "?token=$token",
            'return_url' => $request->return_url ?? 'https://app.startyworld.com/sh/fdsf',
            'type' => 'account_onboarding',
        ]);
        return response()->redirectTo($data['url']);
    }

    public function dashboardAccountLink()
    {
        $user = Auth::user();

        return Cashier::stripe()->accounts->createLoginLink($user->stripe_account);
    }

    public function paymentLink(Party $event)
    {
        error_log(Auth::user()->revolut_customer_id);
        if (!Auth::user()->revolut_customer_id) {
            $res = Http::withToken(AppConfig::first()->revolut_pk)->post(env('REVOLUT_BASE_URL') . "customers", [
                "email" => Auth::user()->email,
                "full_name" => Auth::user()->firstname . ' ' . Auth::user()->lastname,
                "phone" => Auth::user()?->phone_number
            ]);
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
            "currency" => $event->devise,
            "customer_id" => Auth::user()->revolut_customer_id,
            "capture_mode" => "MANUAL",
            "metadata" => [
                "user_id" => Auth::id(),
                "event_id" => $event->id,
            ]
        ]);

        if (!EventParticipant::where([
            "event_id" => $event->id,
            "user_id" => Auth::id()
        ])->exists()) {
            EventParticipant::Create([
                "user_id" => Auth::id(),
                "event_id" => $event->id,
                "accepted" => false,
                "rejected" => false,
                "status" => $res->json("state"),
                "payment_intent_id" => $res->json('id'),
                "payment_processing" => true]);
        } else {
            EventParticipant::where([
                "event_id" => $event->id,
                "user_id" => Auth::id()
            ])->update([
                "accepted" => false,
                "rejected" => false,
                "status" => $res->json("state"),
                "payment_intent_id" => $res->json('id'),
                "payment_processing" => true]);
        }

        return response()->json(["public_id" => $res->json('public_id')]);

    }

    public function paymentStatus(int $event)
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
        } catch (\Exception $e) {
            return [
                "payment_intent_id" => null,
                "paid" => false
            ];
        }
    }

    public function paymentCallback(Request $request)
    {
        try {
            WebhookSignature::verifyHeader(
                $request->getContent(),
                $request->header('Stripe-Signature'),
                config('starty.webhook.secret2'),
                config('cashier.webhook.tolerance')
            );
            $payload = json_decode($request->getContent(), true);

            WebhookReceived::dispatch($payload);
        } catch (SignatureVerificationException $exception) {
            throw new AccessDeniedHttpException($exception->getMessage(), $exception);
        }
    }

    public function webhook(Request $request)
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
