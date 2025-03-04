<?php

    namespace Socialbox\Objects\Client;

    use Socialbox\SocialClient;

    class EncryptionChannelInstance
    {
        private SocialClient $client;

        /**
         * Public constructor
         *
         * @param SocialClient $client The client to use
         * @param EncryptionChannelSecret $secret The secret to use
         */
        public function __construct(SocialClient $client, EncryptionChannelSecret $secret)
        {
            $this->client = $client;
        }

        public function sendMessage(string $message): void
        {
            $this->client->sendMessage($message);
        }
    }