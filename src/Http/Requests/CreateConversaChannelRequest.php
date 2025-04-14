<?php

namespace VendorName\Conversa\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateConversaChannelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // You can add authorization logic here
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:public,private',
            'icon_url' => 'nullable|url',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'A channel name is required.',
            'type.in' => 'The channel type must be either public or private.',
        ];
    }
}
