<?php

namespace ti_sendgrid\Transport;

use Illuminate\Mail\Transport\Transport;
use Swift_Mime_SimpleMessage;

class SendGridTransport extends Transport
{

    protected $apiKey;

    public function __construct()
    {
        //getenv('SENDGRID_API_KEY'));
        //config('sendgrid.secret_key');
        $this->apiKey = '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';
    }

    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $email = new \SendGrid\Mail\Mail();
        $email->setFrom("h.chow@reply.com", "Example User");
        $email->setSubject($message->getSubject());
        $email->addTos($message->getTo());
        dd($message->getBody());

        $email->addDynamicTemplateData("city1", "Denver");
        $substitutions = [
            "subject2" => "Example Subject 2",
            "name2" => "Example Name 2",
            "city2" => "Orange"
        ];

        $email->addDynamicTemplateDatas($substitutions);
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
