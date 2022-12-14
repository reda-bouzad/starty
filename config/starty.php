<?php
return [
    "webhook" => [
        "secret2" => env('STRIPE_WEBHOOK_SECRET2')
    ],
    "stripe_pk" => env('STRIPE_KEY'),
    "revolut_pk" => env('REVOLUT_SECRET')
];
