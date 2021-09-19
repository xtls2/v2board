<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class OrderSave extends FormRequest
{



    public function rules()
    {
        return [
            'plan_id' => 'required|integer',
            'cycle' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'subject.required' => __('Ticket subject cannot be empty'),
            'level.required' => __('Ticket level cannot be empty'),
            'level.in' => __('Incorrect ticket level format'),
            'message.required' => __('Message cannot be empty')
        ];
    }
}
