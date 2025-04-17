<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ResourceRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'published' => 'boolean',
            'type_id' => 'required|exists:types,id',
            'category_id' => 'required|exists:categories,id',
            'visibility_id' => 'required|exists:visibilities,id',
            'origin_id' => 'required|exists:origins,id',
        ];

        // If creating a new resource or updating the file
        if ($this->isMethod('POST') || $this->hasFile('file')) {
            $rules['file'] = 'required|file|mimes:csv,pdf,doc,docx|max:10240'; // 10MB max
        }

        return $rules;
    }
}