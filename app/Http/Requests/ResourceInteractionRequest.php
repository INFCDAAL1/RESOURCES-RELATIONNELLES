<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ResourceInteractionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check(); // User must be authenticated
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'notes' => [
                'nullable',
                'string',
                'max:500'
            ],
        ];
        
        // Required fields when creating
        if ($this->isMethod('POST')) {
            $rules['resource_id'] = [
                'required',
                'exists:resources,id'
            ];
            $rules['type'] = [
                'required',
                'string',
                Rule::in(['favorite', 'saved', 'exploited'])
            ];
        }

        return $rules;
    }
}