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
        if (!$this->transport->ping()) {
            $this->transport->stop();
            $this->transport->start();
        }

        //TODO: Get message from some source and sent via transport


        $this->transport->stop();
    }
}