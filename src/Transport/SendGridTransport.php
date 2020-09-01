<?php

namespace SendGridDriver\Transport;

use Illuminate\Mail\Transport\Transport;
use Swift_Mime_SimpleMessage;

class SendGridTransport extends Transport
{

    protected $publicKey;
    protected $secretKey;

    public function __construct()
    {
        $this->publicKey = config('sendgrid.public_key');
        $this->secretKey = config('sendgrid.secret_key');
    }

    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        dd($message);

        $email = new \SendGrid\Mail\Mail();
        $email->setFrom("h.chow@reply.com", "Example User");
        $email->setSubject("Sending with SendGrid is Fun");
        $email->addTo("h.chow@reply.com", "Example User");
//        $email->addContent("text/plain", "and easy to do anywhere, even with PHP");
//        $email->addContent(
//            "text/html", "<strong>and easy to do anywhere, even with PHP</strong>"
//        );
        $email->addDynamicTemplateData("city1", "Denver");
        $substitutions = [
            "subject2" => "Example Subject 2",
            "name2" => "Example Name 2",
            "city2" => "Orange"
        ];
        $email->addDynamicTemplateDatas($substitutions);
        $email->setTemplateId('d-85169858bbd345f0b544e358680822a4');
        $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
        $name = AdminAuth::getStaffName();
        $email = AdminAuth::getStaffEmail();
        $text = 'This is a test email. If you\'ve received this, it means emails are working in TastyIgniter.';

        try {
            $response = $sendgrid->send($email);
            print $response->statusCode() . "\n";
            print_r($response->headers());
            print $response->body() . "\n";
        } catch (Exception $e) {
            dd('Caught exception: '. $e->getMessage() ."\n");
        }


//        $mj = new \Mailjet\Client($this->publicKey, $this->secretKey,
//                true,['version' => 'v3.1']);
//
//            $response = $mj->post(\Mailjet\Resources::$Email, ['body' => $this->getBody($message)]);
//
//            $this->sendPerformed($message);
//
//            return $this->numberOfRecipients($message);
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
            return $display ? [
                'Email' => $address,
                'Name' =>$display
            ] : [
                'Email' => $address,
            ];

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
