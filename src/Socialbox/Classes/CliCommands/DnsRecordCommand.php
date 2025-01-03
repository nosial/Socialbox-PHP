<?php

    namespace Socialbox\Classes\CliCommands;

    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\Logger;
    use Socialbox\Interfaces\CliCommandInterface;

    class DnsRecordCommand implements CliCommandInterface
    {
        /**
         * @inheritDoc
         */
        public static function execute(array $args): int
        {
            $txt_record = sprintf('v=socialbox;sb-rpc=%s;sb-key=%s',
                Configuration::getInstanceConfiguration()->getRpcEndpoint(),
                Configuration::getCryptographyConfiguration()->getHostPublicKey()
            );

            Logger::getLogger()->info('Please set the following DNS TXT record for the domain:');
            Logger::getLogger()->info(sprintf('  %s', $txt_record));
            return 0;
        }

        /**
         * @inheritDoc
         */
        public static function getHelpMessage(): string
        {
            return <<<HELP
Usage: socialbox dns-record

Displays the DNS TXT record that should be set for the domain.
HELP;
        }

        /**
         * @inheritDoc
         */
        public static function getShortHelpMessage(): string
        {
            return 'Displays the DNS TXT record that should be set for the domain.';
        }
    }