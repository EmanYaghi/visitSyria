<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'status' => 'required|in:block,unblock',
            'reason' => 'required_if:status,block|string',
            'duration' => 'required_if:status,block|in:minute,hour,day,week,month,year,always',
        ];
    }
}
