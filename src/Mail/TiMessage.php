<?php

namespace ti_sendgrid\Mail;

use Illuminate\Mail\Message;

class TiMessage extends \Swift_Message
{
    public $rawData;
    public $rawView;
    public $templateId;

    public function __construct($subject = null, $body = null, $contentType = null, $charset = null)
    {
        parent::__construct($subject, $body, $contentType, $charset);
    }
}
