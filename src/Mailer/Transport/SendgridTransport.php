<?php

namespace Mailer\Transport;

use Mailer\SendGrid\SendGridClient;
use Mailer\SendGrid\SwiftToSendGridMessageTranslator;
use SendGrid\Mail\Mail;
use Swift_Events_EventDispatcher;
use Swift_Events_EventListener;
use Swift_Events_SendEvent;
use Swift_Mime_SimpleMessage;

class SendgridTransport implements \Swift_Transport
{
    protected $started = false;

    /** @var SendGridClient */
    private $sendGridClient;

    /** @var SwiftToSendGridMessageTranslator */
    private $swiftToSendGridMessageTranslator;

    /** @var Swift_Events_EventDispatcher */
    private $eventDispatcher;

    /**
     * @param SendGridClient $sendGridClient
     * @param SwiftToSendGridMessageTranslator $swiftToSendGridMessageTranslator
     * @param Swift_Events_EventDispatcher $dispatcher
     */
    public function __construct(
        SendGridClient $sendGridClient,
        SwiftToSendGridMessageTranslator $swiftToSendGridMessageTranslator,
        Swift_Events_EventDispatcher $dispatcher
    ) {
        $this->sendGridClient = $sendGridClient;
        $this->swiftToSendGridMessageTranslator = $swiftToSendGridMessageTranslator;
        $this->eventDispatcher = $dispatcher;
    }

    /**
     * Test if this Transport mechanism has started.
     *
     * @return bool
     */
    public function isStarted()
    {
        return false !== $this->started;
    }

    /**
     * Start this Transport mechanism.
     */
    public function start()
    {
        $this->started = true;
    }

    /**
     * Stop this Transport mechanism.
     */
    public function stop()
    {
        $this->started = false;
    }

    /**
     * Check if this Transport mechanism is alive.
     *
     * If a Transport mechanism session is no longer functional, the method
     * returns FALSE. It is the responsibility of the developer to handle this
     * case and restart the Transport mechanism manually.
     *
     * @example
     *
     *   if (!$transport->ping()) {
     *      $transport->stop();
     *      $transport->start();
     *   }
     *
     * The Transport mechanism will be started, if it is not already.
     *
     * It is undefined if the Transport mechanism attempts to restart as long as
     * the return value reflects whether the mechanism is now functional.
     *
     * @return bool TRUE if the transport is alive
     */
    public function ping()
    {
        return true;
    }

    /**
     * Send the given Message.
     *
     * Recipient/sender data will be retrieved from the Message API.
     * The return value is the number of recipients who were accepted for delivery.
     *
     * This is the responsibility of the send method to start the transport if needed.
     *
     * @param Swift_Mime_SimpleMessage $message
     * @param string[] $failedRecipients An array of failures by-reference
     *
     * @return int
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        if (!$this->isStarted()) {
            $this->start();
        }

        if ($evt = $this->eventDispatcher->createSendEvent($this, $message)) {
            $this->eventDispatcher->dispatchEvent($evt, 'beforeSendPerformed');
            if ($evt->bubbleCancelled()) {
                return 0;
            }
        }

        $sentMails = 0;

        try {
            $mail = $this->swiftToSendGridMessageTranslator->translate($message);

            $response = $this->sendGridClient->getClient()->send($mail);

            if ($this->isSuccessful($response)) {
                $sentMails =
                    count($message->getTo() ?? [])
                    + count($message->getCc() ?? [])
                    + count($message->getBcc() ?? []);

                if ($evt) {
                    $evt->setResult(Swift_Events_SendEvent::RESULT_SUCCESS);
                }
            } else {
                $this->fillFailedRecipients($message, $failedRecipients);

                if ($evt) {
                    $evt->setResult(Swift_Events_SendEvent::RESULT_FAILED);
                    $evt->setFailedRecipients($failedRecipients);
                }
            }
        } catch (\Exception $e) {
            if ($evt) {
                $evt->setResult(Swift_Events_SendEvent::RESULT_FAILED);
            }
        }

        if($evt) {
            $this->eventDispatcher->dispatchEvent($evt, 'sendPerformed');
        }

        return $sentMails;
    }

    /**
     * Register a plugin in the Transport.
     *
     * @param Swift_Events_EventListener $plugin
     */
    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
        $this->eventDispatcher->bindEventListener($plugin);
    }

    /**
     * @param \SendGrid\Response $response
     * @return bool
     */
    private function isSuccessful(\SendGrid\Response $response)
    {
        return $response->statusCode() < 300 && $response->statusCode() > 199;
    }

    /**
     * @param Swift_Mime_SimpleMessage $message
     * @param $failedRecipients
     * @return array
     */
    public function fillFailedRecipients(Swift_Mime_SimpleMessage $message, &$failedRecipients): array
    {
        $failedRecipients = array_merge(
            (array)$message->getTo(),
            (array)$message->getCc(),
            (array)$message->getBcc(),
            (array)$failedRecipients
        );

        return $failedRecipients;
    }
}
