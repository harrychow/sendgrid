<?php

namespace ti_sendgrid\Mail;

use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Mail\Message;
use InvalidArgumentException;
use Event;

class Mailer extends \October\Rain\Mail\Mailer
{
    public function sendToMany($recipients, $view, array $data = [], $callback = null, $queue = FALSE)
    {
        if ($callback && !$queue && !is_callable($callback)) {
            $queue = $callback;
        }

        $method = $queue === TRUE ? 'queue' : 'send';
        $recipients = $this->processRecipients($recipients);

        foreach ($recipients as $address => $name) {
            $this->{$method}($view, $data, function ($message) use ($address, $name, $callback) {
                $message->to($address, $name);

                if (is_callable($callback)) {
                    $callback($message);
                }
            });
        }
    }

    protected function parseView($view)
    {
        if (is_string($view)) {
            return [$view, null, null];
        }

        // If the given view is an array with numeric keys, we will just assume that
        // both a "pretty" and "plain" view were provided, so we will return this
        // array as is, since it should contain both views with numerical keys.
        if (is_array($view) && isset($view[0])) {
            return [$view[0], $view[1], null];
        }

        // If this view is an array but doesn't contain numeric keys, we will assume
        // the views are being explicitly specified and will extract them via the
        // named keys instead, allowing the developers to use one or the other.
        if (is_array($view)) {

            // This is to help the Rain\Mailer::send() logic when adding raw content
            // to mail the raw value is expected to be bool
            if (isset($view['raw'])) {
                $view['text'] = $view['raw'];
                $view['raw'] = TRUE;
            }

            return [
                $view['html'] ?? null,
                $view['text'] ?? null,
                $view['raw'] ?? null,
            ];
        }

        throw new InvalidArgumentException('Invalid view.');
    }

    /**
     * Send a new message using a view.
     *
     * @param string|array $view
     * @param array $data
     * @param \Closure|string $callback
     * @return mixed
     */
    public function send($view, array $data = [], $callback = null)
    {
        /**
         * @event mailer.beforeSend
         * Fires before the mailer processes the sending action
         *
         * Example usage (stops the sending process):
         *
         *     Event::listen('mailer.beforeSend', function ((string|array) $view, (array) $data, (\Closure|string) $callback) {
         *         return false;
         *     });
         *
         * Or
         *
         *     $mailerInstance->bindEvent('mailer.beforeSend', function ((string|array) $view, (array) $data, (\Closure|string) $callback) {
         *         return false;
         *     });
         *
         */
        if (
            ($this->fireEvent('mailer.beforeSend', [$view, $data, $callback], true) === false) ||
            (Event::fire('mailer.beforeSend', [$view, $data, $callback], true) === false)
        ) {
            return;
        }

        if ($view instanceof MailableContract) {
            return $this->sendMailable($view);
        }

        /*
         * Inherit logic from Illuminate\Mail\Mailer
         */
        list($view, $plain, $raw) = $this->parseView($view);

        $data['message'] = $message = $this->createMessage();

        if ($callback !== null) {
            call_user_func($callback, $message);
        }

        if (is_bool($raw) && $raw === true) {
            $this->addContentRaw($message, $view, $plain);
        } else {
            $this->addContent($message, $view, $plain, $raw, $data);
        }

        if (isset($this->to['address'])) {
            $this->setGlobalTo($message);
        }

        /**
         * @event mailer.prepareSend
         * Fires before the mailer processes the sending action
         *
         * Parameters:
         * - $view: View code as a string
         * - $message: Illuminate\Mail\Message object, check Swift_Mime_SimpleMessage for useful functions.
         *
         * Example usage (stops the sending process):
         *
         *     Event::listen('mailer.prepareSend', function ((\October\Rain\Mail\Mailer) $mailerInstance, (string) $view, (\Illuminate\Mail\Message) $message) {
         *         return false;
         *     });
         *
         * Or
         *
         *     $mailerInstance->bindEvent('mailer.prepareSend', function ((string) $view, (\Illuminate\Mail\Message) $message) {
         *         return false;
         *     });
         *
         */
        if (
            ($this->fireEvent('mailer.prepareSend', [$view, $message], true) === false) ||
            (Event::fire('mailer.prepareSend', [$this, $view, $message], true) === false)
        ) {
            return;
        }

        $swiftMessage = $message->getSwiftMessage();
        unset($data['message']);
        $swiftMessage->rawData = $data;
        $swiftMessage->rawView = $view;
        $swiftMessage->templateId = 'id';

        /*
         * Send the message
         */
        $this->sendSwiftMessage($swiftMessage);
        $this->dispatchSentEvent($message);

        /**
         * @event mailer.send
         * Fires after the message has been sent
         *
         * Example usage (logs the message):
         *
         *     Event::listen('mailer.send', function ((\October\Rain\Mail\Mailer) $mailerInstance, (string) $view, (\Illuminate\Mail\Message) $message) {
         *         \Log::info("Message was rendered with $view and sent");
         *     });
         *
         * Or
         *
         *     $mailerInstance->bindEvent('mailer.send', function ((string) $view, (\Illuminate\Mail\Message) $message) {
         *         \Log::info("Message was rendered with $view and sent");
         *     });
         *
         */
        $this->fireEvent('mailer.send', [$view, $message]);
        Event::fire('mailer.send', [$this, $view, $message]);
    }

    /**
     * Create a new message instance.
     *
     * @return \Illuminate\Mail\Message
     */
    protected function createMessage()
    {
        $message = new Message(new TiMessage());

        // If a global from address has been specified we will set it on every message
        // instance so the developer does not have to repeat themselves every time
        // they create a new message. We'll just go ahead and push this address.
        if (!empty($this->from['address'])) {
            $message->from($this->from['address'], $this->from['name']);
        }

        // When a global reply address was specified we will set this on every message
        // instance so the developer does not have to repeat themselves every time
        // they create a new message. We will just go ahead and push this address.
        if (!empty($this->replyTo['address'])) {
            $message->replyTo($this->replyTo['address'], $this->replyTo['name']);
        }

        return $message;
    }

}