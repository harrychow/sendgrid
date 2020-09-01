<?php

namespace harrychow;

use harrychow;
use harrychow\Transport\SendGridAddedTransportManager;
use Illuminate\Mail\MailServiceProvider;
use Illuminate\Support\ServiceProvider;

class SendGridServiceProvider extends MailServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/sendgrid.php' => config_path('sendgrid.php')
        ], 'sendgridconfig');
    }

    protected function registerSwiftTransport()
    {
        $this->app->singleton('swift.transport', function ($app) {
            return new SendGridAddedTransportManager($app);
        });
    }
}
