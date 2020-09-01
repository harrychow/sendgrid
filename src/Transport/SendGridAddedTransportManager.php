<?php


namespace SendGridDriver\Transport;

use Illuminate\Mail\TransportManager;

class SendGridAddedTransportManager extends TransportManager
{
    protected function createSendGridDriver(): SendGridTransport
    {
        return new SendGridTransport;
    }
}
