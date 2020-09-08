<?php


namespace ti_sendgrid\Transport;

use Illuminate\Mail\TransportManager;

class SendGridAddedTransportManager extends TransportManager
{
    protected function createSmtpDriver(): SendGridTransport
    {
        return new SendGridTransport;
    }
}
