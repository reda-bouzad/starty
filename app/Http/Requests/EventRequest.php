<?php

namespace App\Http\Requests;

use App\Models\PriceCategory;
use Illuminate\Foundation\Http\FormRequest;

/**
 * App\Http\Requests
 *
 * @property string label
 * @property string type
 * @property string contact
 * @property boolean pricy
 * @property array price_categories
 * @property float price
 * @property int nb_participants
 * @property float lat
 * @property float long
 */
class EventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            "label" => "required|string|max:255",
            "type" => "required|string|in:public,private",
            "contact" => "required|string|max:255",
            "pricy" => "required|boolean",
            "price_categories" => "required_if:pricy,==,1|array",
            "price" => "required_if:pricy,==,1",
            "nb_participants" => "required|integer",
            "start_at" => "required",
            "end_at" => "required",
            "lat" => "required|numeric|between:-90,90",
            "long" => "required|numeric|between:-180,180",
            "images" => "sometimes",
            "description" => "sometimes",
            "address" => "sometimes",
            "devise" => "sometimes",
            "phone_number" => "sometimes",
            "event_visible" => "sometimes|in:visible,hidden",
        ];
    }
}
