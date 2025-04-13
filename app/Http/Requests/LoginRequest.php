<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'email.required' => 'Please provide your email.',
            'email.email' => 'Please provide a valid email.',
            'password.required' => 'Password is required.',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->error($validator->errors(), 'Please check your data.', 422));
    }
}
