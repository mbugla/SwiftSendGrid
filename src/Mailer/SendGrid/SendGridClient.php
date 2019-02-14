<?php

namespace Mailer\SendGrid;


class SendGridClient
{
    /** @var string */
    private $apiKey;

    /** @var \SendGrid */
    private $client = null;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return \SendGrid
     */
    public function getClient()
    {
        if(null === $this->client) {
            $this->client =  new \SendGrid($this->apiKey);
        }

        return $this->client;
    }
}