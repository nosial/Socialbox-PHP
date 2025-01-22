<?php

    namespace Socialbox;

    use OptsLib\Parse;
    use Socialbox\Classes\Logger;
    use Socialbox\Enums\CliCommands;

    class Program
    {
        private static ?\LogLib2\Logger $logger;

        /**
         * Socialbox main entry point
         *
         * @param string[] $args Command-line arguments
         * @return int Exit code
         */
        public static function main(array $args): int
        {
            // Parse the arguments into a more usable array format
            $args = Parse::parseArgument($args);

            if(isset($args['help']))
            {
                if($args['help'] === true)
                {
                    return self::displayHelp();
                }

                $command = CliCommands::tryFrom($args['help']);

                if($command === null)
                {
                    print(sprintf("Unknown command '%s'\n", $args['help']));
                    return 0;
                }

                print($command->getHelpMessage() . "\n");
                return 0;
            }

            if(isset($args[CliCommands::INITIALIZE->value]))
            {
                return CliCommands::INITIALIZE->handle($args);
            }

            if(isset($args[CliCommands::DNS_RECORD->value]))
            {
                return CliCommands::DNS_RECORD->handle($args);
            }

            return self::displayHelp();
        }

        /**
         * Displays the help message for the Socialbox CLI Management Interface.
         *
         * This method prints out the usage instructions and a list of available commands.
         *
         * @return int Returns 0 upon successful display of the help message.
         */
        private static function displayHelp(): int
        {
            print("Socialbox - CLI Management Interface\n");
            print("Usage: socialbox [command] [arguments]\n\n");
            print("Commands:\n");
            print("  help - Displays this help message.\n");

            foreach(CliCommands::cases() as $command)
            {
                print(sprintf("  %s - %s\n", $command->value, $command->getShortHelpMessage()));
            }

            print("Use 'socialbox --help=[command]' for more information about a command.\n");

            return 0;
        }

        /**
         * Retrieves the logger instance for the Socialbox program.
         *
         * @return \LogLib2\Logger Returns the logger instance.
         */
        public static function getLogger(): \LogLib2\Logger
        {
            if(self::$logger === null)
            {
                self::$logger = new \LogLib2\Logger('net.nosial.socialbox');
                \LogLib2\Logger::registerHandlers();
            }

            return self::$logger;
        }
    }