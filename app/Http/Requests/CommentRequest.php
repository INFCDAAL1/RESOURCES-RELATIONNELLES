<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CommentRequest extends FormRequest
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
            'content' => [
                'required',
                'string',
                'min:2',
                'max:1000'
            ],
        ];
        
        // Status can only be changed by admins
        if (Auth::user()->isAdmin()) {
            $rules['status'] = [
                'sometimes',
                'string',
                Rule::in(['published', 'hidden', 'flagged'])
            ];
        }
        
        // Resource ID is required when creating a new comment
        if ($this->isMethod('POST')) {
            $rules['resource_id'] = [
                'required',
                'exists:resources,id'
            ];
        }

        return $rules;
    }
}