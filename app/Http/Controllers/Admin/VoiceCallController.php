<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Voice\Enums\VoiceCallStatus;
use App\Domains\Voice\Models\VoiceCall;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\VoiceCallResource;
use App\Support\VoiceAdminOptions;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VoiceCallController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:voice.calls.view')->only(['index', 'show']);
    }

    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status'),
            'voice_provider_id' => $request->query('voice_provider_id'),
            'per_page' => (int) $request->query('per_page', 20),
        ];

        $query = VoiceCall::query()->with([
            'provider:id,name,slug',
            'employee:id,name',
            'lead:id,first_name,last_name',
        ]);

        if ($filters['q'] !== '') {
            $query->where(function ($builder) use ($filters) {
                $builder->where('uuid', 'like', '%'.$filters['q'].'%')
                    ->orWhere('external_call_id', 'like', '%'.$filters['q'].'%')
                    ->orWhere('to_number', 'like', '%'.$filters['q'].'%');
            });
        }

        if (filled($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (filled($filters['voice_provider_id'])) {
            $query->where('voice_provider_id', $filters['voice_provider_id']);
        }

        $calls = $query->orderByDesc('created_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return Inertia::render('Admin/Voice/Calls/Index', [
            'calls' => VoiceCallResource::collection($calls),
            'filters' => array_map(fn ($value) => $value === '' ? null : $value, $filters),
            'statusOptions' => VoiceAdminOptions::enumOptions(VoiceCallStatus::class),
            'providerOptions' => \App\Domains\Voice\Models\VoiceProvider::query()
                ->orderBy('priority')
                ->get(['id', 'name', 'slug'])
                ->map(fn ($provider) => [
                    'id' => $provider->id,
                    'name' => $provider->name,
                    'slug' => $provider->slug,
                ])
                ->values()
                ->all(),
        ]);
    }

    public function show(VoiceCall $voiceCall): Response
    {
        $voiceCall->load([
            'provider:id,name,slug',
            'employee:id,name',
            'lead:id,first_name,last_name,phone,company',
        ]);

        return Inertia::render('Admin/Voice/Calls/Show', [
            'call' => VoiceCallResource::make($voiceCall)->resolve(),
        ]);
    }
}
