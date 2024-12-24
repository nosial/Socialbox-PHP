<?php

    namespace Socialbox\Classes;

    use InvalidArgumentException;
    use Socialbox\Enums\DatabaseObjects;

    class Resources
    {
        /**
         * Retrieves the full path to a database resource based on the provided DatabaseObjects instance.
         *
         * @param DatabaseObjects $object An instance of DatabaseObjects containing the resource value.
         * @return string The full file path to the specified database resource.
         */
        public static function getDatabaseResource(DatabaseObjects $object): string
        {
            $tables_directory = __DIR__ . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'database';
            return $tables_directory . DIRECTORY_SEPARATOR . $object->value;
        }

        /**
         * Retrieves the file path of a document resource based on the provided name.
         *
         * @param string $name The name of the document resource to retrieve.
         * @return string The file path of the specified document resource.
         */
        public static function getDocumentResource(String $name): string
        {
            $documents_directory = __DIR__ . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'documents';
            return $documents_directory . DIRECTORY_SEPARATOR . $name . '.html';
        }

        /**
         * Retrieves the content of the privacy policy document.
         *
         * @return string The content of the privacy policy document. Attempts to fetch the document
         * from a configured location if available and valid; otherwise, retrieves it from a default resource.
         */
        public static function getPrivacyPolicy(): string
        {
            $configuredLocation = Configuration::getRegistrationConfiguration()->getPrivacyPolicyDocument();
            if($configuredLocation !== null && file_exists($configuredLocation))
            {
                return file_get_contents($configuredLocation);
            }

            return file_get_contents(self::getDocumentResource('privacy'));
        }

        /**
         * Retrieves the content of the Terms of Service document.
         *
         * @return string The content of the Terms of Service file. The method checks a configured location first,
         *                and falls back to a default resource if the configured file is unavailable.
         */
        public static function getTermsOfService(): string
        {
            $configuredLocation = Configuration::getRegistrationConfiguration()->getTermsOfServiceDocument();
            if($configuredLocation !== null && file_exists($configuredLocation))
            {
                return file_get_contents($configuredLocation);
            }

            return file_get_contents(self::getDocumentResource('tos'));
        }

        /**
         * Retrieves the community guidelines document content.
         *
         * @return string The content of the community guidelines document, either from a configured location
         *                or a default resource if the configured location is unavailable.
         */
        public static function getCommunityGuidelines(): string
        {
            $configuredLocation = Configuration::getRegistrationConfiguration()->getCommunityGuidelinesDocument();
            if($configuredLocation !== null && file_exists($configuredLocation))
            {
                return file_get_contents($configuredLocation);
            }

            return file_get_contents(self::getDocumentResource('community'));
        }

    }