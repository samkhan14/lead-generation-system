<?php

namespace App\Http\Requests\Admin;

use App\Domains\Voice\Models\VoiceProvider;

class UpdateVoiceProviderRequest extends StoreVoiceProviderRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('voice.providers.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var VoiceProvider $provider */
        $provider = $this->route('voiceProvider');

        return $this->providerRules($provider->id);
    }
}
