<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
/**
 * App\Http\Requests
 * @property string|null $firstname
 * @property string|null fcm_token
 * @property string|null phone_number
 * @property string|null email
 * @property boolean|null show_pseudo_only
 * @property float|null $lat
 * @property float|null $long
 */
class ProfileRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            "firstname" => "sometimes|string|max:255",
            "lastname" => "sometimes|string|max:255",
            "email" => [
                "sometimes",
                'email',
                Rule::unique('users', 'email')
                    ->whereNotNull('firstname')
                    ->whereNotNull('lastname')
                    ->ignore(\Auth::id())
            ],
            "pseudo" => [
                "sometimes",
                'string',
                Rule::unique('users', 'pseudo')
                    ->whereNotNull('firstname')
                    ->whereNotNull('lastname')
                    ->ignore(\Auth::id()),],
            "gender" => "sometimes|in:M,F",
            "birth_date" => "sometimes|date",
            "phone_number" => [
                "sometimes",
                'string',
                Rule::unique('users', 'phone_number')
                    ->whereNotNull('firstname')
                    ->whereNotNull('lastname')
                    ->ignore(\Auth::id())
            ],
            'avatar' => 'sometimes|image',
            'description' => 'sometimes',
            'lat' => 'sometimes|numeric|between:-90,90',
            'long' => 'sometimes|numeric|between:-180,180',
        ];
    }
}
