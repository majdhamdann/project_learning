<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:255|unique:users,phone,' . $this->id,
            'password' => 'sometimes|required|string|min:8',
            'role_id' => 'sometimes|required|exists:roles,id',
        ];
    }
}
