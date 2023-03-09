<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\Exceptions\HttpResponseException;
use Kreait\Firebase\Auth;

class VerifyTelNumberRule implements Rule
{
    protected bool $is_token;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($is_token = false)
    {
        $this->is_token = $is_token;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if ($this->is_token) {
            $token_attribute = $attribute;
            $token_value = $value;
        } else {
            $token_attribute = $attribute . "_token";
            $token_value = request()->get($token_attribute);
            error_log(implode(request()->all()));
            $validator = \Validator::make([$token_attribute => $token_value], [$token_attribute => 'required']);

            if ($validator->fails()) {
                throw new HttpResponseException(response()->json($validator->errors()->toArray(), 422));
//            throw new HttpResponseException(RB::validationError($validator->errors()->toArray(),'auth.user.tokenIsRequired'));
            }
        }

        try {

            /** @var Auth $auth */
            $auth = app('firebase.auth');
            $verifiedIdToken = $auth->verifyIdToken($token_value);
            if ($this->is_token) return true;
            $uid = $verifiedIdToken->claims()->get('sub');
            $data = $auth->getUser($uid);
            return $data->phoneNumber === $value;
        } catch (\Exception $e) {
            error_log($e);
            return false;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'auth.user.firebaseVerificationFailed';
    }
}
