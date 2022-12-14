<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventSearchRequest extends FormRequest
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
            'lat' => 'sometimes',
            'long' => 'sometimes',
            'radius' => 'sometimes|integer',
            'start' => 'sometimes|date',
            "end" => 'sometimes|date',
            'type' => ['sometimes',Rule::in(['private','public'])],
            'pricy' => ['sometimes','boolean']
        ];
    }
}
