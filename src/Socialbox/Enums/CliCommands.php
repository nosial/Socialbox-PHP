<?php

namespace Socialbox\Enums;

use Socialbox\Classes\CliCommands\DnsRecordCommand;
use Socialbox\Classes\CliCommands\InitializeCommand;

enum CliCommands : string
{
    case INITIALIZE = 'init';
    case DNS_RECORD = 'dns-record';

    /**
     * Handles the command execution, returns the exit code.
     *
     * @param array $args An array of arguments to be processed.
     * @return int The result of the execution as an integer.
     */
    public function handle(array $args): int
    {
        return match ($this)
        {
            self::INITIALIZE => InitializeCommand::execute($args),
            self::DNS_RECORD => DnsRecordCommand::execute($args)
        };
    }
    public function getHelpMessage(): string
    {
        return match ($this)
        {
            self::INITIALIZE => InitializeCommand::getHelpMessage(),
            self::DNS_RECORD => DnsRecordCommand::getHelpMessage()
        };
    }

    public function getShortHelpMessage(): string
    {
        return match ($this)
        {
            self::INITIALIZE => InitializeCommand::getShortHelpMessage(),
            self::DNS_RECORD => DnsRecordCommand::getShortHelpMessage()
        };
    }
}
