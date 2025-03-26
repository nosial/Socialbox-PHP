<?php

    namespace Socialbox\Classes;

    use InvalidArgumentException;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Exceptions\ResolutionException;
    use Socialbox\Managers\ResolvedDnsRecordsManager;
    use Socialbox\Objects\DnsRecord;

    class ServerResolver
    {
        private static array $mockedRecords = [];

        /**
         * Resolves a domain by retrieving and parsing its DNS TXT records.
         * Optionally checks a database for cached resolution data before performing a DNS query.
         *
         * @param string $domain The domain name to resolve.
         * @param bool $useDatabase Whether to check the database for cached resolution data; defaults to true.
         * @return DnsRecord The parsed DNS record for the given domain.
         * @throws ResolutionException If the DNS TXT records cannot be retrieved or parsed.
         * @throws DatabaseOperationException If an error occurs while interacting with the database. (Only if $useDatabase is true)
         */
        public static function resolveDomain(string $domain, bool $useDatabase=true): DnsRecord
        {
            $domain = strtolower($domain);

            // Return the mocked record if the mocking record is set
            if(isset(self::$mockedRecords[$domain]))
            {
                return self::$mockedRecords[$domain];
            }

            // Return the mocked record from the configuration if one is set
            if(isset(Configuration::getInstanceConfiguration()->getDnsMocks()[$domain]))
            {
                return DnsHelper::parseTxt(Configuration::getInstanceConfiguration()->getDnsMocks()[$domain]);
            }

            // Check the database if enabled
            if ($useDatabase)
            {
                // Return from the database cache if one exists
                // TODO: Implement renewal here
                $resolvedServer = ResolvedDnsRecordsManager::getDnsRecord($domain);
                if ($resolvedServer !== null)
                {
                    return $resolvedServer;
                }
            }

            // Resolve DNS & Records
            $txtRecords = self::dnsGetTxtRecords($domain);
            if ($txtRecords === false)
            {
                throw new ResolutionException(sprintf("Failed to resolve DNS TXT records for %s", $domain));
            }
            $fullRecord = self::concatenateTxtRecords($txtRecords);

            try
            {
                // Parse the TXT record using DnsHelper
                $record = DnsHelper::parseTxt($fullRecord);

                // Cache the resolved server record in the database
                if($useDatabase)
                {
                    ResolvedDnsRecordsManager::addResolvedServer($domain, $record);
                }

                return $record;
            }
            catch (InvalidArgumentException $e)
            {
                throw new ResolutionException(sprintf("Failed to find valid SocialBox record for %s: %s", $domain, $e->getMessage()));
            }
        }

        /**
         * Retrieves the TXT records for a given domain using the dns_get_record function.
         *
         * @param string $domain The domain name to fetch TXT records for.
         * @return array|false An array of DNS TXT records on success, or false on failure.
         */
        private static function dnsGetTxtRecords(string $domain): array|false
        {
            return @dns_get_record($domain, DNS_TXT);
        }

        /**
         * Concatenates an array of TXT records into a single string, filtering for SocialBox records.
         *
         * @param array $txtRecords An array of TXT records, where each record is expected to have a 'txt' key.
         * @return string A concatenated string of all relevant TXT records.
         */
        private static function concatenateTxtRecords(array $txtRecords): string
        {
            $fullRecordBuilder = '';
            foreach ($txtRecords as $txt)
            {
                if (isset($txt['txt']))
                {
                    $record = trim($txt['txt'], '" ');
                    // Only include records that start with v=socialbox
                    if (stripos($record, 'v=socialbox') === 0)
                    {
                        $fullRecordBuilder .= $record;
                    }
                }
            }
            return $fullRecordBuilder;
        }

        /**
         * Retrieves the mocked records.
         *
         * @return array The list of mocked records.
         */
        public static function getMockedRecords(): array
        {
            return self::$mockedRecords;
        }

        /**
         * Adds a mock DNS record for a specific domain.
         *
         * @param string $domain The domain name for which the DNS record is being mocked.
         * @param DnsRecord|string $record The DNS record to be associated with the specified domain.
         * @return void
         */
        public static function addMock(string $domain, DnsRecord|string $record): void
        {
            $domain = strtolower($domain);

            if(isset(self::$mockedRecords[$domain]))
            {
                return;
            }

            if(is_string($record))
            {
                $record = DnsHelper::parseTxt($record);
            }

            self::$mockedRecords[$domain] = $record;
        }

        /**
         * Clears all mocked records by resetting the mocked records array.
         *
         * @return void
         */
        public static function clearMockedRecords(): void
        {
            self::$mockedRecords = [];
        }
    }