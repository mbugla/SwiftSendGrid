<?php

namespace Mailer;


class Mailer
{
    /**
     * @var \Swift_Transport
     */
    private $transport;

    public function __construct(\Swift_Transport $transport)
    {
        $this->transport = $transport;
    }

    public function run()
    {

    }
}