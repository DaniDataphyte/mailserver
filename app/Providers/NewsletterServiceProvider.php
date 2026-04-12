<?php

namespace App\Providers;

use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\GlobalSet;
use Statamic\Statamic;

class NewsletterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerMorphMap();
        $this->registerEmailViewComposer();
        $this->registerCpNav();
        $this->registerRoutes();
    }

    /**
     * Register short morph aliases so campaign_audiences.targetable_type
     * stores 'subscriber_group' / 'subscriber_sub_group' instead of full class paths.
     */
    /**
     * Inject $newsletterSettings into every emails.* Blade template.
     *
     * The GlobalSet is fetched once per process and cached for 1 hour so
     * bulk campaign sends (thousands of emails) pay the DB cost only once.
     */
    protected function registerEmailViewComposer(): void
    {
        View::composer('emails.*', function ($view) {
            $settings = cache()->remember('newsletter_settings', 3600, function () {
                $set = GlobalSet::findByHandle('newsletter_settings');

                if (! $set) {
                    return [];
                }

                $data = $set->inDefaultSite()?->data() ?? collect();

                return $data->toArray();
            });

            $view->with('newsletterSettings', $settings);
        });
    }

    protected function registerMorphMap(): void
    {
        Relation::morphMap([
            'subscriber_group'     => SubscriberGroup::class,
            'subscriber_sub_group' => SubscriberSubGroup::class,
        ]);
    }

    protected function registerCpNav(): void
    {
        Nav::extend(function ($nav) {
            $nav->content('Newsletter')
                ->section('Newsletter')
                ->icon('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M1.5 8.67v8.58a3 3 0 003 3h15a3 3 0 003-3V8.67l-8.928 5.493a3 3 0 01-3.144 0L1.5 8.67z"/><path d="M22.5 6.908V6.75a3 3 0 00-3-3h-15a3 3 0 00-3 3v.158l9.714 5.978a1.5 1.5 0 001.572 0L22.5 6.908z"/></svg>')
                ->children([
                    $nav->item('Campaigns')
                        ->url(cp_route('newsletter.campaigns.index'))
                        ->icon('send-email'),

                    $nav->item('Analytics')
                        ->url(cp_route('newsletter.analytics.index'))
                        ->icon('charts'),

                    $nav->item('Subscribers')
                        ->url(cp_route('newsletter.subscribers.index'))
                        ->icon('users'),

                    $nav->item('Groups')
                        ->url(cp_route('newsletter.groups.index'))
                        ->icon('tags'),
                ]);
        });
    }

    protected function registerRoutes(): void
    {
        Statamic::pushCpRoutes(function () {
            \Route::name('newsletter.')->prefix('newsletter')->group(function () {

                // Subscribers
                \Route::prefix('subscribers')->name('subscribers.')->group(function () {
                    \Route::get('/',              [\App\Http\Controllers\CP\Newsletter\SubscriberController::class, 'index'])->name('index');
                    \Route::get('/create',        [\App\Http\Controllers\CP\Newsletter\SubscriberController::class, 'create'])->name('create');
                    \Route::post('/',             [\App\Http\Controllers\CP\Newsletter\SubscriberController::class, 'store'])->name('store');
                    \Route::get('/{subscriber}',  [\App\Http\Controllers\CP\Newsletter\SubscriberController::class, 'show'])->name('show');
                    \Route::get('/{subscriber}/edit', [\App\Http\Controllers\CP\Newsletter\SubscriberController::class, 'edit'])->name('edit');
                    \Route::put('/{subscriber}',  [\App\Http\Controllers\CP\Newsletter\SubscriberController::class, 'update'])->name('update');
                    \Route::delete('/{subscriber}', [\App\Http\Controllers\CP\Newsletter\SubscriberController::class, 'destroy'])->name('destroy');

                    // Import / Export
                    \Route::get('/import/form',   [\App\Http\Controllers\CP\Newsletter\ImportController::class, 'form'])->name('import.form');
                    \Route::post('/import',       [\App\Http\Controllers\CP\Newsletter\ImportController::class, 'import'])->name('import');
                    \Route::get('/export/csv',    [\App\Http\Controllers\CP\Newsletter\ImportController::class, 'export'])->name('export');
                });

                // Analytics
                \Route::prefix('analytics')->name('analytics.')->group(function () {
                    \Route::get('/',                     [\App\Http\Controllers\CP\Newsletter\AnalyticsController::class, 'index'])->name('index');
                    \Route::get('/webhooks',             [\App\Http\Controllers\CP\Newsletter\AnalyticsController::class, 'webhooks'])->name('webhooks');
                    \Route::get('/campaign/{campaign}',  [\App\Http\Controllers\CP\Newsletter\AnalyticsController::class, 'campaign'])->name('campaign');
                });

                // Campaigns
                \Route::prefix('campaigns')->name('campaigns.')->group(function () {
                    \Route::get('/',                 [\App\Http\Controllers\CP\Newsletter\CampaignController::class, 'index'])->name('index');
                    \Route::get('/create',           [\App\Http\Controllers\CP\Newsletter\CampaignController::class, 'create'])->name('create');
                    \Route::post('/',                [\App\Http\Controllers\CP\Newsletter\CampaignController::class, 'store'])->name('store');
                    \Route::get('/{campaign}',       [\App\Http\Controllers\CP\Newsletter\CampaignController::class, 'show'])->name('show');
                    \Route::get('/{campaign}/edit',  [\App\Http\Controllers\CP\Newsletter\CampaignController::class, 'edit'])->name('edit');
                    \Route::put('/{campaign}',       [\App\Http\Controllers\CP\Newsletter\CampaignController::class, 'update'])->name('update');
                    \Route::delete('/{campaign}',    [\App\Http\Controllers\CP\Newsletter\CampaignController::class, 'destroy'])->name('destroy');
                    \Route::post('/{campaign}/send',      [\App\Http\Controllers\CP\Newsletter\CampaignController::class, 'send'])->name('send');
                    \Route::post('/{campaign}/cancel',    [\App\Http\Controllers\CP\Newsletter\CampaignController::class, 'cancel'])->name('cancel');
                    \Route::post('/{campaign}/test-send', [\App\Http\Controllers\CP\Newsletter\CampaignController::class, 'testSend'])->name('test-send');
                });

                // GDPR
                \Route::prefix('subscribers/{subscriber}/gdpr')->name('subscribers.gdpr.')->group(function () {
                    \Route::get('/export',    [\App\Http\Controllers\CP\Newsletter\GdprController::class, 'export'])->name('export');
                    \Route::get('/erase',     [\App\Http\Controllers\CP\Newsletter\GdprController::class, 'eraseForm'])->name('erase-form');
                    \Route::delete('/erase',  [\App\Http\Controllers\CP\Newsletter\GdprController::class, 'erase'])->name('erase');
                });

                // Groups
                \Route::prefix('groups')->name('groups.')->group(function () {
                    \Route::get('/',           [\App\Http\Controllers\CP\Newsletter\GroupController::class, 'index'])->name('index');
                    \Route::get('/create',     [\App\Http\Controllers\CP\Newsletter\GroupController::class, 'create'])->name('create');
                    \Route::post('/',          [\App\Http\Controllers\CP\Newsletter\GroupController::class, 'store'])->name('store');
                    \Route::get('/{group}/edit',[\App\Http\Controllers\CP\Newsletter\GroupController::class, 'edit'])->name('edit');
                    \Route::put('/{group}',    [\App\Http\Controllers\CP\Newsletter\GroupController::class, 'update'])->name('update');
                    \Route::delete('/{group}', [\App\Http\Controllers\CP\Newsletter\GroupController::class, 'destroy'])->name('destroy');

                    // Sub-groups (nested under a group)
                    \Route::post('/{group}/sub-groups',              [\App\Http\Controllers\CP\Newsletter\SubGroupController::class, 'store'])->name('sub-groups.store');
                    \Route::put('/{group}/sub-groups/{subGroup}',    [\App\Http\Controllers\CP\Newsletter\SubGroupController::class, 'update'])->name('sub-groups.update');
                    \Route::delete('/{group}/sub-groups/{subGroup}', [\App\Http\Controllers\CP\Newsletter\SubGroupController::class, 'destroy'])->name('sub-groups.destroy');
                });
            });
        });
    }
}
