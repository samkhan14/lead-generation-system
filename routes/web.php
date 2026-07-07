<?php

use App\Http\Controllers\Admin\AiEmployeeController;
use App\Http\Controllers\Admin\AiLogController;
use App\Http\Controllers\Admin\AiModelController;
use App\Http\Controllers\Admin\AiProviderController;
use App\Http\Controllers\Admin\EmailCampaignController;
use App\Http\Controllers\Admin\EmailProviderController;
use App\Http\Controllers\Admin\EmailSendController;
use App\Http\Controllers\Admin\KnowledgeBaseController;
use App\Http\Controllers\Admin\PromptTemplateController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\VoiceCallController;
use App\Http\Controllers\Admin\VoiceProviderController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadBulkVoiceCallController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadVoiceCallController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScraperController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('leads', LeadController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::post('/leads/{lead}/verify', [LeadController::class, 'reverify'])->name('leads.reverify');
    Route::post('/leads/voice-calls/bulk', [LeadBulkVoiceCallController::class, 'store'])->name('leads.voice-calls.bulk');
    Route::post('/leads/{lead}/voice-calls', [LeadVoiceCallController::class, 'store'])->name('leads.voice-calls.store');

    Route::prefix('scraper')->name('scraper.')->group(function () {
        Route::get('/', [ScraperController::class, 'index'])->name('index');
        Route::post('/', [ScraperController::class, 'store'])->name('store');
        Route::get('/{scrapeJob}', [ScraperController::class, 'show'])->name('show');
        Route::get('/{scrapeJob}/status', [ScraperController::class, 'statusPoll'])->name('status');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('services', ServiceController::class)
            ->only(['index', 'show', 'store', 'update', 'destroy']);

        Route::prefix('ai')->name('ai.')->group(function () {
            Route::resource('providers', AiProviderController::class)
                ->parameters(['providers' => 'aiProvider'])
                ->only(['index', 'show', 'store', 'update', 'destroy']);

            Route::resource('models', AiModelController::class)
                ->parameters(['models' => 'aiModel'])
                ->only(['index', 'show', 'store', 'update', 'destroy']);

            Route::resource('employees', AiEmployeeController::class)
                ->parameters(['employees' => 'aiEmployee'])
                ->only(['index', 'show', 'store', 'update', 'destroy']);

            Route::resource('prompts', PromptTemplateController::class)
                ->parameters(['prompts' => 'promptTemplate'])
                ->only(['index', 'show', 'store', 'update', 'destroy']);

            Route::resource('knowledge', KnowledgeBaseController::class)
                ->parameters(['knowledge' => 'knowledgeBase'])
                ->only(['index', 'show', 'store', 'update', 'destroy']);

            Route::resource('logs', AiLogController::class)
                ->parameters(['logs' => 'aiLog'])
                ->only(['index', 'show']);
        });

        Route::prefix('voice')->name('voice.')->group(function () {
            Route::resource('providers', VoiceProviderController::class)
                ->parameters(['providers' => 'voiceProvider'])
                ->only(['index', 'show', 'store', 'update', 'destroy']);

            Route::resource('calls', VoiceCallController::class)
                ->parameters(['calls' => 'voiceCall'])
                ->only(['index', 'show']);
        });

        Route::prefix('email')->name('email.')->group(function () {
            Route::resource('providers', EmailProviderController::class)
                ->parameters(['providers' => 'emailProvider'])
                ->only(['index', 'show', 'store', 'update', 'destroy']);

            Route::resource('campaigns', EmailCampaignController::class)
                ->parameters(['campaigns' => 'emailCampaign'])
                ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

            Route::post('campaigns/{emailCampaign}/test', [EmailCampaignController::class, 'sendTest'])
                ->name('campaigns.test');
            Route::post('campaigns/{emailCampaign}/send', [EmailCampaignController::class, 'dispatch'])
                ->name('campaigns.send');
            Route::post('campaigns/{emailCampaign}/enhance', [EmailCampaignController::class, 'enhance'])
                ->name('campaigns.enhance');
            Route::post('enhance-content', [EmailCampaignController::class, 'enhancePreview'])
                ->name('enhance-content');

            Route::resource('sends', EmailSendController::class)
                ->parameters(['sends' => 'emailSend'])
                ->only(['index', 'create', 'store', 'show']);
        });
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
