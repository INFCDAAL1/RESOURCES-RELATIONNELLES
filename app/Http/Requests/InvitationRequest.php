<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class InvitationRequest extends FormRequest
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
        $rules = [];
        
        // Rules depend on whether this is a creation or update request
        if ($this->isMethod('POST')) {
            $rules = [
                'receiver_id' => [
                    'required',
                    'exists:users,id',
                    // Cannot invite yourself
                    Rule::notIn([Auth::id()])
                ],
                'resource_id' => [
                    'required',
                    'exists:resources,id'
                ]
            ];
        } elseif ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = [
                'status' => [
                    'required',
                    Rule::in(['accepted', 'declined'])
                ]
            ];
        }

        return $rules;
    }
}