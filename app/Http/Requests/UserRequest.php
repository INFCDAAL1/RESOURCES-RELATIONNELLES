<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'string|max:255',
            'email' => 'email|max:255',
            'is_active' => 'boolean',
        ];

        // Pour la création, certains champs sont obligatoires
        if ($this->isMethod('post')) {
            $rules['name'] = 'required|' . $rules['name'];
            $rules['email'] = 'required|' . $rules['email'] . '|unique:users';
            $rules['password'] = 'required|string|min:8';
        }

        // Pour la mise à jour, email doit être unique sauf pour l'utilisateur actuel
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $userId = $this->route('user');
            $rules['email'] .= '|' . Rule::unique('users')->ignore($userId);
            $rules['password'] = 'nullable|string|min:8';
        }

        return $rules;
    }
}