<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskRequest extends FormRequest
{

    protected static $rules = [
        'title' => ['required', 'string', 'max:255'],
        'description' => 'string',
        'attachments.*' => 'file|max:2048',
        'completed' => 'nullable|boolean'
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::$rules;
    }

    protected function prepareForValidation(): void
    {
        if ($this->data) {
            $this->merge(json_decode($this->data, true));
        }
    }
}
