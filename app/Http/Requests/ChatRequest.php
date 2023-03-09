<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChatRequest extends FormRequest
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
            "type" => "sometimes|in:single,group",
            "name" => [Rule::requiredIf(function () {
                return request()->type === "group";
            })],

            "with" => "required|array",
//            "with.*" => [
//                'required',
//                'integer',
//                Rule::exists('users','id')
//                    ->whereNot('id',\Auth::id())
//            ]
        ];
    }
}
