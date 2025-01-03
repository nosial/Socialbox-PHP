<?php

    namespace Socialbox\Classes;

    use InvalidArgumentException;
    use Socialbox\Exceptions\ResolutionException;
    use Socialbox\Managers\ResolvedDnsRecordsManager;
    use Socialbox\Objects\DnsRecord;

    class ServerResolver
    {
        /**
         * Resolves a domain by retrieving and parsing its DNS TXT records.
         * Optionally checks a database for cached resolution data before performing a DNS query.
         *
         * @param string $domain The domain name to resolve.
         * @param bool $useDatabase Whether to check the database for cached resolution data; defaults to true.
         * @return DnsRecord The parsed DNS record for the given domain.
         * @throws ResolutionException If the DNS TXT records cannot be retrieved or parsed.
         */
        public static function resolveDomain(string $domain, bool $useDatabase=true): DnsRecord
        {
            // Check the database if enabled
            if ($useDatabase)
            {
                $resolvedServer = ResolvedDnsRecordsManager::getDnsRecord($domain);
                if ($resolvedServer !== null)
                {
                    return $resolvedServer;
                }
            }

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
    }