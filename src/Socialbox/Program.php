<?php

    namespace Socialbox;

    class Program
    {
        /**
         * Socialbox main entry point
         *
         * @param string[] $args Command-line arguments
         * @return int Exit code
         */
        public static function main(array $args): int
        {
            print("Hello World from net.nosial.socialbox!" . PHP_EOL);
            return 0;
        }
    }