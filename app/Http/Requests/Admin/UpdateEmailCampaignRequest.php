<?php

namespace App\Http\Requests\Admin;

use App\Domains\Email\Models\EmailCampaign;

class UpdateEmailCampaignRequest extends StoreEmailCampaignRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('email.campaigns.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var EmailCampaign $campaign */
        $campaign = $this->route('emailCampaign');

        if ($campaign->status->value !== 'draft') {
            return [];
        }

        return parent::rules();
    }
}
