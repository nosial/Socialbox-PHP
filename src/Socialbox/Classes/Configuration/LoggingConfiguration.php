<?php

    namespace Socialbox\Classes\Configuration;

    use LogLib\Enums\LogLevel;

    class LoggingConfiguration
    {
        private bool $consoleLoggingEnabled;
        private string $consoleLoggingLevel;
        private bool $fileLoggingEnabled;
        private string $fileLoggingLevel;

        /**
         * Initializes a new instance of the class with the given configuration data.
         *
         * @param array $data An associative array containing logging configuration settings.
         * @return void
         */
        public function __construct(array $data)
        {
            $this->consoleLoggingEnabled = (bool) $data['console_logging_enabled'];
            $this->consoleLoggingLevel = $data['console_logging_level'];
            $this->fileLoggingEnabled = (bool) $data['file_logging_enabled'];
            $this->fileLoggingLevel = $data['file_logging_level'];
        }

        /**
         * Checks if console logging is enabled.
         *
         * @return bool True if console logging is enabled, otherwise false.
         */
        public function isConsoleLoggingEnabled(): bool
        {
            return $this->consoleLoggingEnabled;
        }

        /**
         * Retrieves the logging level for console output.
         *
         * @return LogLevel The logging level configured for console output.
         */
        public function getConsoleLoggingLevel(): LogLevel
        {
            return $this->parseLogLevel($this->consoleLoggingLevel);
        }

        /**
         * Checks if file logging is enabled.
         *
         * @return bool True if file logging is enabled, false otherwise.
         */
        public function isFileLoggingEnabled(): bool
        {
            return $this->fileLoggingEnabled;
        }

        /**
         * Retrieves the logging level for file logging.
         *
         * @return LogLevel The logging level set for file logging.
         */
        public function getFileLoggingLevel(): LogLevel
        {
            return $this->parseLogLevel($this->fileLoggingLevel);
        }

        /**
         * Parses the given log level from string format to a LogLevel enumeration.
         *
         * @param string $logLevel The log level as a string.
         * @return LogLevel The corresponding LogLevel enumeration.
         */
        private function parseLogLevel(string $logLevel): LogLevel
        {
            switch (strtolower($logLevel))
            {
                case LogLevel::DEBUG:
                case 'debug':
                case '6':
                case 'dbg':
                    return LogLevel::DEBUG;

                case LogLevel::VERBOSE:
                case 'verbose':
                case '5':
                case 'vrb':
                    return LogLevel::VERBOSE;

                default:
                case LogLevel::INFO:
                case 'info':
                case '4':
                case 'inf':
                    return LogLevel::INFO;

                case LogLevel::WARNING:
                case 'warning':
                case '3':
                case 'wrn':
                    return LogLevel::WARNING;
                case LogLevel::ERROR:
                case 'error':
                case '2':
                case 'err':
                    return LogLevel::ERROR;

                case LogLevel::FATAL:
                case 'fatal':
                case '1':
                case 'crt':
                    return LogLevel::FATAL;

                case LogLevel::SILENT:
                case 'silent':
                case '0':
                case 'sil':
                    return LogLevel::SILENT;
            }
        }
    }