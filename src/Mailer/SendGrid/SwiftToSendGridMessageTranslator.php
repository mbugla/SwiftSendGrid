<?php

namespace Mailer\SendGrid;


use SendGrid\Mail\From;
use SendGrid\Mail\Mail;
use SendGrid\Mail\To;
use SendGrid\Mail\Cc;
use SendGrid\Mail\BCc;
use SendGrid\Mail\Personalization;
use Swift_Mime_SimpleMessage;

class SwiftToSendGridMessageTranslator
{
    /**
     * @param Swift_Mime_SimpleMessage $message
     *
     * @return Mail
     * @throws \SendGrid\Mail\TypeException
     */
    public function translate(Swift_Mime_SimpleMessage $message): Mail
    {
        $fromArray = $message->getFrom();
        $fromName = reset($fromArray);
        $fromEmail = key($fromArray);

        $mail = new Mail();

        $mail->setFrom($fromEmail, $fromName);
        $mail->setSubject($message->getSubject());
        $mail->addContent($message->getBodyContentType(), $message->getBody());
        $this->handleTo($message, $mail);
        $this->handleCc($message, $mail);
        $this->handleBcc($message, $mail);
        $this->handleHeaders($message, $mail);

        return $mail;
    }

    /**
     * @param Swift_Mime_SimpleMessage $message
     * @param Mail $mail
     * @return array
     */
    public function handleTo(Swift_Mime_SimpleMessage $message, Mail $mail): void
    {
        $to = $message->getTo();
        if (!empty($to)) {
            $toAddresses = [];

            foreach ($to as $email => $name) {
                $toAddresses[] = new To($email, $name);
            }

            $mail->addTos($toAddresses);
        }
    }

    /**
     * @param Swift_Mime_SimpleMessage $message
     * @param Mail $mail
     * @return array
     */
    public function handleCc(Swift_Mime_SimpleMessage $message, Mail $mail): void
    {
        $cc = $message->getCc();
        if (!empty($cc)) {
            $ccAddresses = [];

            foreach ($cc as $email => $name) {
                $ccAddresses[] = new Cc($email, $name);
            }

            $mail->addCcs($ccAddresses);
        }
    }

    /**
     * @param Swift_Mime_SimpleMessage $message
     * @param Mail $mail
     */
    public function handleBcc(Swift_Mime_SimpleMessage $message, Mail $mail): void
    {
        $bcc = $message->getBcc();
        if (!empty($bcc)) {
            $bccAddresses = [];

            foreach ($bcc as $email => $name) {
                $bccAddresses[] = new Bcc($email, $name);
            }

            $mail->addBccs($bccAddresses);
        }
    }

    /**
     * @param Swift_Mime_SimpleMessage $message
     * @param Mail $mail
     */
    public function handleHeaders(Swift_Mime_SimpleMessage $message, Mail $mail): void
    {
        /** @var \Swift_Mime_Header[] $headers */
        $headers = $message->getHeaders()->getAll();

        if (!empty($headers)) {
            foreach ($headers as $header) {
                $headerName = $header->getFieldName();
                $mail->addHeader($headerName, $header->getFieldBody());

            }
        }
    }
}