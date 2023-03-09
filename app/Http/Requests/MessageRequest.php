<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MessageRequest extends FormRequest
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
            'to' => [
                Rule::when($this->type == "single", [
                    'required',
                    'integer',
                    Rule::exists('users', 'id')
                        ->whereNot('id', \Auth::id())
                ],)
            ],
            'response_to' => ['sometimes', Rule::exists('chat_messages', 'id')->where('chat_id', $this->route('chat'))],
            'from_message_id' => ['sometimes', Rule::exists('chat_messages', 'id')],
            'content' => 'sometimes',
            'files' => ['sometimes', Rule::requiredIf(function () {
                return $this->content === null && $this->from_message_id === null;
            })]
        ];
    }
}
