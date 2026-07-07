<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Email\Enums\EmailCampaignStatus;
use App\Domains\Email\Models\EmailCampaign;
use App\Domains\Email\Models\EmailProvider;
use App\Domains\Email\Services\EmailCampaignService;
use App\Domains\Email\Services\EmailContentEnhancer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DispatchEmailCampaignRequest;
use App\Http\Requests\Admin\EnhanceEmailCampaignRequest;
use App\Http\Requests\Admin\SendEmailTestRequest;
use App\Http\Requests\Admin\StoreEmailCampaignRequest;
use App\Http\Requests\Admin\UpdateEmailCampaignRequest;
use App\Http\Resources\Admin\EmailCampaignResource;
use App\Http\Resources\Admin\EmailProviderResource;
use App\Support\EmailAdminOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class EmailCampaignController extends Controller
{
    public function __construct(
        private EmailCampaignService $campaignService,
        private EmailContentEnhancer $contentEnhancer,
    ) {
        $this->middleware('permission:email.campaigns.view')->only(['index', 'show']);
        $this->middleware('permission:email.campaigns.create')->only(['create', 'store']);
        $this->middleware('permission:email.campaigns.update')->only(['edit', 'update', 'enhance', 'enhancePreview']);
        $this->middleware('permission:email.campaigns.send')->only(['sendTest', 'dispatch']);
        $this->middleware('permission:email.campaigns.delete')->only(['destroy']);
    }

    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status'),
            'per_page' => (int) $request->query('per_page', 15),
        ];

        $query = EmailCampaign::query()->with('provider')->withCount('sends');

        if ($filters['q'] !== '') {
            $query->where(function ($builder) use ($filters) {
                $builder->where('name', 'like', '%'.$filters['q'].'%')
                    ->orWhere('subject', 'like', '%'.$filters['q'].'%');
            });
        }

        if (filled($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $campaigns = $query->latest('updated_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return Inertia::render('Admin/Email/Campaigns/Index', [
            'campaigns' => EmailCampaignResource::collection($campaigns),
            'filters' => array_map(fn ($value) => $value === '' ? null : $value, $filters),
            'statusOptions' => EmailAdminOptions::enumOptions(EmailCampaignStatus::class),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Email/Campaigns/Form', [
            'campaign' => null,
            'providers' => EmailProviderResource::collection(
                EmailProvider::query()->orderBy('priority')->get(),
            ),
            'statusOptions' => EmailAdminOptions::enumOptions(EmailCampaignStatus::class),
        ]);
    }

    public function show(EmailCampaign $emailCampaign): Response
    {
        $emailCampaign->load(['provider'])->loadCount('sends');

        return Inertia::render('Admin/Email/Campaigns/Show', [
            'campaign' => EmailCampaignResource::make($emailCampaign)->resolve(),
            'providers' => EmailProviderResource::collection(
                EmailProvider::query()->orderBy('priority')->get(),
            ),
            'statusOptions' => EmailAdminOptions::enumOptions(EmailCampaignStatus::class),
        ]);
    }

    public function edit(EmailCampaign $emailCampaign): Response|RedirectResponse
    {
        if ($emailCampaign->status !== EmailCampaignStatus::Draft) {
            return redirect()
                ->route('admin.email.campaigns.show', $emailCampaign)
                ->withErrors(['campaign' => 'Only draft campaigns can be edited.']);
        }

        return Inertia::render('Admin/Email/Campaigns/Form', [
            'campaign' => EmailCampaignResource::make($emailCampaign)->resolve(),
            'providers' => EmailProviderResource::collection(
                EmailProvider::query()->orderBy('priority')->get(),
            ),
            'statusOptions' => EmailAdminOptions::enumOptions(EmailCampaignStatus::class),
        ]);
    }

    public function store(StoreEmailCampaignRequest $request): RedirectResponse
    {
        $campaign = $this->campaignService->create($request->validated(), $request->user());

        return redirect()
            ->route('admin.email.campaigns.show', $campaign)
            ->with('success', 'Email campaign created.');
    }

    public function update(UpdateEmailCampaignRequest $request, EmailCampaign $emailCampaign): RedirectResponse
    {
        try {
            $this->campaignService->update($emailCampaign, $request->validated(), $request->user());
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['campaign' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.email.campaigns.show', $emailCampaign)
            ->with('success', 'Email campaign updated.');
    }

    public function destroy(EmailCampaign $emailCampaign): RedirectResponse
    {
        if ($emailCampaign->status === EmailCampaignStatus::Sending) {
            throw ValidationException::withMessages(['campaign' => 'Cannot delete a campaign while sending.']);
        }

        $emailCampaign->delete();

        return redirect()
            ->route('admin.email.campaigns.index')
            ->with('success', 'Email campaign deleted.');
    }

    public function sendTest(SendEmailTestRequest $request, EmailCampaign $emailCampaign): RedirectResponse
    {
        try {
            $this->campaignService->sendTest(
                $emailCampaign,
                $request->validated('to_email'),
                $request->user(),
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['test_email' => $exception->getMessage()]);
        }

        return redirect()
            ->back()
            ->with('success', 'Test email queued. Check Email Sends for delivery status.');
    }

    public function dispatch(DispatchEmailCampaignRequest $request, EmailCampaign $emailCampaign): RedirectResponse
    {
        try {
            $queued = $this->campaignService->dispatchCampaign(
                $emailCampaign,
                $request->validated('lead_ids'),
                $request->user(),
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['campaign' => $exception->getMessage()]);
        }

        return redirect()
            ->back()
            ->with('success', "Campaign queued for {$queued} recipient(s).");
    }

    public function enhance(EnhanceEmailCampaignRequest $request, EmailCampaign $emailCampaign): RedirectResponse
    {
        try {
            $enhanced = $this->contentEnhancer->enhance(
                $request->validated('subject'),
                $request->validated('html_body'),
                $request->validated('text_body'),
                $request->validated('tone'),
            );

            $this->contentEnhancer->applyToCampaign($emailCampaign, $enhanced);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['ai_enhance' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.email.campaigns.edit', $emailCampaign)
            ->with('success', 'Campaign copy enhanced with AI. Review before sending.');
    }

    public function enhancePreview(EnhanceEmailCampaignRequest $request): RedirectResponse
    {
        try {
            $enhanced = $this->contentEnhancer->enhance(
                $request->validated('subject'),
                $request->validated('html_body'),
                $request->validated('text_body'),
                $request->validated('tone'),
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['ai_enhance' => $exception->getMessage()]);
        }

        return back()->with([
            'success' => 'Copy enhanced with AI. Review the updated fields.',
            'enhanced_content' => $enhanced,
        ]);
    }
}
