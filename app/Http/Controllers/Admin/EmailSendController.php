<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Email\Enums\EmailSendStatus;
use App\Domains\Email\Models\EmailProvider;
use App\Domains\Email\Models\EmailSend;
use App\Domains\Email\Services\EmailGateway;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendSingleEmailRequest;
use App\Http\Resources\Admin\EmailProviderResource;
use App\Http\Resources\Admin\EmailSendResource;
use App\Support\EmailAdminOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class EmailSendController extends Controller
{
    public function __construct(
        private EmailGateway $gateway,
    ) {
        $this->middleware('permission:email.sends.view')->only(['index', 'show']);
        $this->middleware('permission:email.sends.create')->only(['create', 'store']);
    }

    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status'),
            'per_page' => (int) $request->query('per_page', 15),
        ];

        $query = EmailSend::query()->with(['campaign', 'provider'])->latest();

        if ($filters['q'] !== '') {
            $query->where(function ($builder) use ($filters) {
                $builder->where('to_email', 'like', '%'.$filters['q'].'%')
                    ->orWhere('subject', 'like', '%'.$filters['q'].'%');
            });
        }

        if (filled($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $sends = $query->paginate($filters['per_page'])->withQueryString();

        return Inertia::render('Admin/Email/Sends/Index', [
            'sends' => EmailSendResource::collection($sends),
            'filters' => array_map(fn ($value) => $value === '' ? null : $value, $filters),
            'statusOptions' => EmailAdminOptions::enumOptions(EmailSendStatus::class),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Email/Sends/Compose', [
            'providers' => EmailProviderResource::collection(
                EmailProvider::query()->orderBy('priority')->get(),
            ),
        ]);
    }

    public function show(EmailSend $emailSend): Response
    {
        $emailSend->load(['campaign', 'provider']);

        return Inertia::render('Admin/Email/Sends/Show', [
            'send' => EmailSendResource::make($emailSend)->resolve(),
        ]);
    }

    public function store(SendSingleEmailRequest $request): RedirectResponse
    {
        try {
            $send = $this->gateway->createAndQueueSingle(
                $request->validated(),
                $request->user(),
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['email' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.email.sends.show', $send)
            ->with('success', 'Email queued for delivery.');
    }
}
