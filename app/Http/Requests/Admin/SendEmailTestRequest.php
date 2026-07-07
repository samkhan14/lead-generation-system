<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SendEmailTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('email.campaigns.send');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'to_email' => ['required', 'email', 'max:255'],
        ];
    }
}
