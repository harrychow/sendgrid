<?php

namespace ti_sendgrid;

use ti_sendgrid;
use ti_sendgrid\Transport\SendGridAddedTransportManager;
use ti_sendgrid\Mail\Mailer;
use Illuminate\Mail\MailServiceProvider;
use Igniter\Flame\Setting\Facades\Setting;

use Illuminate\Support\ServiceProvider;

class SendGridServiceProvider extends MailServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/sendgrid.php' => config_path('sendgrid.php')
        ], 'sendgridconfig');
    }


    protected function registerIlluminateMailer()
    {
        $this->app->singleton('mailer', function ($app) {

            $config = $app->make('config')->get('mail');

            // Once we have create the mailer instance, we will set a container instance
            // on the mailer. This allows us to resolve mailer classes via containers
            // for maximum testability on said classes instead of passing Closures.
            $mailer = new Mailer(
                $app['view'], $app['swift.mailer'], $app['events']
            );

            if ($app->bound('queue')) {
                $mailer->setQueue($app['queue']);
            }

            // Next we will set all of the global addresses on this mailer, which allows
            // for easy unification of all "from" addresses as well as easy debugging
            // of sent messages since they get be sent into a single email address.
            foreach (['from', 'reply_to', 'to'] as $type) {
                $this->setGlobalAddress($mailer, $config, $type);
            }

            $this->app['events']->fire('mailer.register', [$this, $mailer]);

            return $mailer;
        });
    }


    protected function registerSwiftTransport()
    {
        // Switch here depending on what's selected in settings
        if (Setting::get('protocol') == 'sendgrid') {
            $this->app->singleton('swift.transport', function ($app) {
                return new SendGridAddedTransportManager($app);
            });
        } else {
            parent::registerSwiftTransport();
        }
    }
}
