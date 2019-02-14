<?php

namespace Test\Mailer\SendGrid\Fake;


use Mailer\SendGrid\SendGridClient;
use SendGrid\Response;

class AlwaysFailingSendGridClient extends SendGridClient
{
    public function getClient()
    {
        return (new class(123) extends \SendGrid
        {
            public function send(\SendGrid\Mail\Mail $email)
            {
                return new Response(400);
            }
        });
    }
}