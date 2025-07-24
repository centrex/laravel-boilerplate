<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'        => 'required|string',
            'phone'       => 'required|string|min:11|max:11|regex:/^01[0-9]/|unique:users',
            'password'    => 'required|string|min:4',
            'device_name' => 'nullable|string',
            'device_id'   => 'nullable|string',
            'device_type'   => 'nullable|string',
            'fcm_token'   => 'nullable|string',
        ];
    }
}
