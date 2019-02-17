<?php

namespace Test\Mailer\Transport;

use Mailer\SendGrid\SwiftToSendGridMessageTranslator;
use Mailer\Transport\SendgridTransport;
use Prophecy\Argument;
use Swift_Events_EventDispatcher;
use Test\Mailer\SendGrid\Fake\AlwaysFailingSendGridClient;
use Test\Mailer\SendGrid\Fake\AlwaysSuccessSendGridClient;

class SendgridTransportTest extends \PHPUnit\Framework\TestCase
{
    protected $eventDispatcher;

    /**
     * @test
     */
    public function canBeStarted()
    {
        $transport = $this->getTransport();

        $transport->start();

        $this->assertTrue($transport->isStarted());
    }

    /**
     * @test
     */
    public function canBeStopped()
    {
        $transport = $this->getTransport();

        $transport->stop();

        $this->assertFalse($transport->isStarted());
    }

    /**
     * @test
     */
    public function canBePinged()
    {
        $transport = $this->getTransport();

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
            ->setBody('Something really important', 'text/plain');

        $transport = $this->getTransport();

        $sentMails = $transport->send($message);

        $this->assertEquals(1, $sentMails);

        $this->eventDispatcher->dispatchEvent(
            Argument::type(\Swift_Events_SendEvent::class),
            Argument::exact('beforeSendPerformed')
        )->shouldHaveBeenCalledTimes(1);

        $this->eventDispatcher->dispatchEvent(
            Argument::type(\Swift_Events_SendEvent::class),
            Argument::exact('sendPerformed')
        )->shouldHaveBeenCalledTimes(1);
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
            ->setBody('Something really important', 'text/plain');

        $transport = $this->getTransport();

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
            ->setBody('Something really important', 'text/plain');

        $transport = $this->getTransport();

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
            ->setBody('Something really important', 'text/plain');

        $transport = $this->getTransport('failing');

        $sentMails = $transport->send($message, $failedRecipients);

        $this->assertEquals(0, $sentMails);
        $this->assertCount(3, $failedRecipients);

        $this->eventDispatcher->dispatchEvent(
            Argument::type(\Swift_Events_SendEvent::class),
            Argument::exact('beforeSendPerformed')
        )->shouldHaveBeenCalledTimes(1);

        $this->eventDispatcher->dispatchEvent(
            Argument::type(\Swift_Events_SendEvent::class),
            Argument::exact('sendPerformed')
        )->shouldHaveBeenCalledTimes(1);
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

    /**
     * @param string $clientType
     *
     * @return SendgridTransport
     */
    public function getTransport($clientType = 'success'): SendgridTransport
    {
        switch ($clientType) {
            default:
            case 'success':
                $client = $this->getSuccessClient();
                break;
            case 'failing':
                $client = $this->getFailingClient();
                break;
        }

        $this->eventDispatcher = $this->prophesize(Swift_Events_EventDispatcher::class);

        $event = $this->prophesize(\Swift_Events_SendEvent::class);
        $this->eventDispatcher->createSendEvent(
            Argument::type(\Swift_Transport::class),
            Argument::type(\Swift_Message::class)
        )->willReturn($event->reveal());

        $this->eventDispatcher->dispatchEvent(
            Argument::type(\Swift_Events_SendEvent::class),
            Argument::type('string')
        )->willReturn(null);

        $transport = new SendgridTransport(
            $client,
            new SwiftToSendGridMessageTranslator(),
            $this->eventDispatcher->reveal()
        );

        return $transport;
    }
}
