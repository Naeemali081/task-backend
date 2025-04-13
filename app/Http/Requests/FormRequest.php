<?php

namespace App\Http\Requests;

use App\Http\Responses\HttpResponse;

class FormRequest extends \Illuminate\Foundation\Http\FormRequest
{
    use HttpResponse;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,txt',
        ];
    }

    protected function toBoolean($booleable)
    {
        return filter_var($booleable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

}
