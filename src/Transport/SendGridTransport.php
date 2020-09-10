<?php

namespace ti_sendgrid\Transport;

use Illuminate\Mail\Transport\Transport;
use Swift_Mime_SimpleMessage;
use Igniter\Flame\Setting\Facades\Setting;
use System\Models\Mail_templates_model;

class SendGridTransport extends Transport
{
    protected $apiKey;

    public function __construct()
    {
	$sendgridApi = Setting::get('sendgrid_api');
	if (!$sendgridApi) {
		$sendgridApi = (Setting::get('smtp_user') == 'apikey') ? Setting::get('smtp_pass'):null;
	}

	$this->apiKey = $sendgridApi;
    }

    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $email = new \SendGrid\Mail\Mail();
        $email->setFrom(Setting::get('sender_email'), Setting::get('sender_name'));
        $email->setSubject($message->getSubject());
        $email->addTos($message->getTo());

        $email->addDynamicTemplateDatas($message->rawData);
        $template = Mail_templates_model::where('code', $message->rawView)->first();

        if ($template && $template->sendgrid_template_id) {
            $email->setTemplateId($template->sendgrid_template_id);
        } else {
            throw new \Swift_TransportException('missing template id');
        }

        try {
            $sendgrid = new \SendGrid($this->apiKey);
            $response = $sendgrid->send($email);
            if ($response->statusCode() != 202) {
                throw new \Swift_TransportException($response->body());
            } else {
                return 1;
            }
        } catch (Exception $e) {
            throw $e;
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
