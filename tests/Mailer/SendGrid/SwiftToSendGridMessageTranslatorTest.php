<?php

namespace Test\Mailer\SendGrid;


use Mailer\SendGrid\SwiftToSendGridMessageTranslator;
use PHPUnit\Framework\TestCase;

class SwiftToSendGridMessageTranslatorTest extends TestCase
{
    /**
     * @test
     */
    public function canTranslateSwiftMessage()
    {
        $message = (new \Swift_Message('The Message'))
            ->setFrom('john@doe.com')
            ->setTo('jane@doe.com')
            ->setBcc(['will@smith.com', 'bruceLee@kung.fu'])
            ->setBody('Something really important', 'text/plain');

        $translator = new SwiftToSendGridMessageTranslator();

        $sendGridMessage = $translator->translate($message);

        $sendGridMessage = json_decode(json_encode($sendGridMessage));

        $this->assertEquals('The Message', $sendGridMessage->subject);
        $this->assertEquals('john@doe.com', $sendGridMessage->from->email);
        $this->assertEquals('Something really important', $sendGridMessage->content[0]->value);
        $this->assertEquals('text/plain', $sendGridMessage->content[0]->type);
        $this->assertEquals('jane@doe.com', $sendGridMessage->personalizations[0]->to[0]->email);
        $this->assertEquals('will@smith.com', $sendGridMessage->personalizations[0]->bcc[0]->email);
        $this->assertEquals('bruceLee@kung.fu', $sendGridMessage->personalizations[0]->bcc[1]->email);

    }
}