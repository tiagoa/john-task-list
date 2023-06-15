<?php

namespace App\Http\Requests;

class UpdateTaskRequest extends TaskRequest
{
    public function rules(): array
    {
        $rules = parent::$rules;
        array_shift($rules['title']);
        $rules['del_attachments.*'] = 'string';
        return $rules;
    }
}
