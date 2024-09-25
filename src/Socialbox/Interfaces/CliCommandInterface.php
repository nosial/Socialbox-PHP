<?php

namespace Socialbox\Interfaces;

interface CliCommandInterface
{
    /**
     * Executes the given set of arguments.
     *
     * @param array $args An array of arguments to be processed.
     * @return int The result of the execution as an integer.
     */
    public static function execute(array $args): int;

    /**
     * Returns the help message for the command.
     *
     * @return string The help message for the command.
     */
    public static function getHelpMessage(): string;

    /**
     * Returns the short help message for the command.
     *
     * @return string The short help message for the command.
     */
    public static function getShortHelpMessage(): string;
}