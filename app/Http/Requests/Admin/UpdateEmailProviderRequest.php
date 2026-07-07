<?php

namespace App\Http\Requests\Admin;

use App\Domains\Email\Models\EmailProvider;
use Illuminate\Validation\Rule;

class UpdateEmailProviderRequest extends StoreEmailProviderRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('email.providers.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var EmailProvider $provider */
        $provider = $this->route('emailProvider');

        return $this->providerRules($provider->id);
    }
}
