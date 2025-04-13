<?php

namespace App\Http\Requests;

use App\Enums\ValidationRegex;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ClientLoginRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'phone' => ['required', 'regex:' . ValidationRegex::PHONE->value],
        ];
    }
    
    public function messages()
    {
        return [
            'phone.required' => 'Please provide your phone number.',
            'phone.regex' => 'Please enter a valid 10-digit Indian phone number.',
        ];
    }    

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->error($validator->errors(), 'Please check your data.', 422));
    }
}
