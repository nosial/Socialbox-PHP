<?php

namespace Socialbox\Enums;

use Socialbox\Classes\CliCommands\HelpCommand;
use Socialbox\Classes\CliCommands\InitializeCommand;

enum CliCommands : string
{
    case INITIALIZE = 'init';

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
        };
    }
    public function getHelpMessage(): string
    {
        return match ($this)
        {
            self::INITIALIZE => InitializeCommand::getHelpMessage()
        };
    }

    public function getShortHelpMessage(): string
    {
        return match ($this)
        {
            self::INITIALIZE => InitializeCommand::getShortHelpMessage()
        };
    }
}
