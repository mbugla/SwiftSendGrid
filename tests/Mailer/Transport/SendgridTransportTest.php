<?php

namespace Test\Mailer\Transport;

use Mailer\SendGrid\SwiftToSendGridMessageTranslator;
use Mailer\Transport\SendgridTransport;
use Test\Mailer\SendGrid\Fake\AlwaysFailingSendGridClient;
use Test\Mailer\SendGrid\Fake\AlwaysSuccessSendGridClient;

class SendgridTransportTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function canBeStarted()
    {
        $transport = new SendgridTransport($this->getSuccessClient(), new SwiftToSendGridMessageTranslator());

        $transport->start();

        $this->assertTrue($transport->isStarted());
    }

    /**
     * @test
     */
    public function canBeStopped()
    {
        $transport = new SendgridTransport($this->getSuccessClient(), new SwiftToSendGridMessageTranslator());

        $transport->stop();

        $this->assertFalse($transport->isStarted());
    }

    /**
     * @test
     */
    public function canBePinged()
    {
        $transport = new SendgridTransport($this->getSuccessClient(), new SwiftToSendGridMessageTranslator());

        $this->assertTrue($transport->ping());
    }

    /**
     * @test
     */
    public function isAbleToSendMessage()
    {
       $message = (new \Swift_Message('The Message'))
            ->setFrom('john@doe.com')
            ->setTo('jane@doe.com')
            ->setBody('Something really important', 'text/plain')
        ;

        $transport = new SendgridTransport($this->getSuccessClient(), new SwiftToSendGridMessageTranslator());

        $sentMails = $transport->send($message);

        $this->assertEquals(1, $sentMails);
    }

    /**
     * @test
     */
    public function isAbleToSendMessageWithCcRecipients()
    {
        $message = (new \Swift_Message('The Message'))
            ->setFrom('john@doe.com')
            ->setTo('jane@doe.com')
            ->setCc(['will@smith.com', 'bruceLee@kung.fu'])
            ->setBody('Something really important', 'text/plain')
        ;

        $transport = new SendgridTransport($this->getSuccessClient(), new SwiftToSendGridMessageTranslator());

        $sentMails = $transport->send($message);

        $this->assertEquals(3, $sentMails);
    }

    /**
     * @test
     */
    public function isAbleToSendMessageWithBCcRecipients()
    {
        $message = (new \Swift_Message('The Message'))
            ->setFrom('john@doe.com')
            ->setTo('jane@doe.com')
            ->setBcc(['will@smith.com', 'bruceLee@kung.fu'])
            ->setBody('Something really important', 'text/plain')
        ;

        $transport = new SendgridTransport($this->getSuccessClient(), new SwiftToSendGridMessageTranslator());

        $sentMails = $transport->send($message);

        $this->assertEquals(3, $sentMails);
    }

    /**
     * @test
     */
    public function returns0OnInCaseOfSentFailure()
    {
        $message = (new \Swift_Message('The Message'))
            ->setFrom('john@doe.com')
            ->setTo('jane@doe.com')
            ->setBcc(['will@smith.com', 'bruceLee@kung.fu'])
            ->setBody('Something really important', 'text/plain')
        ;

        $transport = new SendgridTransport($this->getFailingClient(), new SwiftToSendGridMessageTranslator());

        $sentMails = $transport->send($message,$failedRecipients);

        $this->assertEquals(0, $sentMails);
        $this->assertCount(3, $failedRecipients);
    }

    /**
     * @return AlwaysSuccessSendGridClient
     */
    public function getSuccessClient(): AlwaysSuccessSendGridClient
    {
        return new AlwaysSuccessSendGridClient(123);
    }

    public function getFailingClient(): AlwaysFailingSendGridClient
    {
        return new AlwaysFailingSendGridClient(123);
    }
}