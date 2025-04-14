<?php

namespace VendorName\Conversa\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use VendorName\Conversa\Models\ConversaAttachment;

class SendConversaMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has access to the space or thread
        if ($this->space_id) {
            return $this->user()->can('sendMessage', $this->space_id);
        }

        if ($this->thread_id) {
            return $this->user()->can('sendMessage', $this->thread_id);
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $maxLength = config('conversa.messages.max_length', 10000);

        return [
            'space_id' => 'required_without:thread_id|exists:conversa_spaces,id',
            'thread_id' => 'required_without:space_id|exists:conversa_threads,id',
            'parent_id' => 'nullable|exists:conversa_messages,id',
            'content' => 'required_without:attachments|string|max:' . $maxLength,
            'type' => 'required|in:text,system,file',
            'metadata' => 'nullable|array',
            'attachments' => 'nullable|array',
            'attachments.*' => [
                'file',
                'max:' . config('conversa.attachments.max_size', 10240),
                function ($attribute, $value, $fail) {
                    if (!ConversaAttachment::isAllowedFileType($value->getMimeType())) {
                        $fail('The file type is not allowed.');
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'content.required_without' => 'A message content is required when no attachments are present.',
            'content.max' => 'The message cannot be longer than :max characters.',
            'attachments.*.max' => 'Files cannot be larger than :max kilobytes.',
            'space_id.required_without' => 'A space or thread ID is required.',
            'thread_id.required_without' => 'A space or thread ID is required.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'content' => 'message',
            'attachments.*' => 'file',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set the workspace_id from the authenticated user
        $this->merge([
            'workspace_id' => $this->user()->workspace_id,
            'user_id' => $this->user()->id,
        ]);

        // Set default type if not provided
        if (!$this->has('type')) {
            $this->merge([
                'type' => $this->has('attachments') ? 'file' : 'text',
            ]);
        }
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        // Process any mentions in the content
        if ($this->has('content')) {
            preg_match_all('/@([a-zA-Z0-9_]+)/', $this->content, $matches);
            if (!empty($matches[1])) {
                $this->merge([
                    'metadata' => array_merge($this->metadata ?? [], [
                        'mentions' => $matches[1],
                    ]),
                ]);
            }
        }
    }
}
