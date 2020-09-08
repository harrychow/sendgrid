<?php

namespace ti_sendgrid\Transport;

use Illuminate\Mail\Transport\Transport;
use Swift_Mime_SimpleMessage;

class SendGridTransport extends Transport
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('SENDGRID_API');
    }

    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $email = new \SendGrid\Mail\Mail();
        $email->setFrom("h.chow@reply.com", "Example User");
        $email->setSubject($message->getSubject());
        $email->addTos($message->getTo());

        $email->addDynamicTemplateDatas($message->rawData);
        $email->setTemplateId('d-34702e4cae004e7ba343ed23d4091fe5');

        try {
            $sendgrid = new \SendGrid($this->apiKey);
            $response = $sendgrid->send($email);
            print $response->statusCode() . "\n";
            print_r($response->headers());
            print $response->body() . "\n";
        } catch (Exception $e) {
            dd('Caught exception: '. $e->getMessage() ."\n");
        }
    }

    /**
     * Get body for the message.
     *
     * @param \Swift_Mime_SimpleMessage $message
     * @return array
     */

    protected function getBody(Swift_Mime_SimpleMessage $message)
    {
        return [
            'Messages' => [
                [
                    'From' => [
                        'Email' => config('mail.from.address'),
                        'Name' => config('mail.from.name')
                    ],
                    'To' => $this->getTo($message),
                    'Subject' => $message->getSubject(),
                    'HTMLPart' => $message->getBody(),
                ]
            ]
        ];
    }

    /**
     * Get the "to" payload field for the API request.
     *
     * @param \Swift_Mime_SimpleMessage $message
     * @return string
     */
    protected function getTo(Swift_Mime_SimpleMessage $message)
    {
        return collect($this->allContacts($message))->map(function ($display, $address) {
            return $display ? [$address => $display] : [$address];
        })->values()->toArray();
    }

    /**
     * Get all of the contacts for the message.
     *
     * @param \Swift_Mime_SimpleMessage $message
     * @return array
     */
    protected function allContacts(Swift_Mime_SimpleMessage $message)
    {
        return array_merge(
            (array) $message->getTo(), (array) $message->getCc(), (array) $message->getBcc()
        );
    }
}
