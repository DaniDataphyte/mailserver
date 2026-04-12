<?php

namespace App\Providers;

use App\Mail\Transport\ElasticEmailTransport;
use Illuminate\Mail\MailManager;
use Illuminate\Support\ServiceProvider;

class MailServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->make(MailManager::class)->extend('elasticemail', function () {
            return new ElasticEmailTransport(
                config('mail.mailers.elasticemail.key')
            );
        });
    }
}
