<?php


namespace App\Nova\Settings;


use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Stepanenko3\NovaSettings\Types\AbstractType;

class PaymentConfig extends AbstractType
{

    public function fields(): array
    {
        return [
            Number::make('Commission soirée payante', 'fee'),
            Text::make('Stripe public key', 'stripe_pk')
        ];
    }
}
