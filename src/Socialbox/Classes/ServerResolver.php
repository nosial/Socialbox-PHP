<?php

namespace Socialbox\Classes;

use Socialbox\Exceptions\DatabaseOperationException;
use Socialbox\Exceptions\ResolutionException;
use Socialbox\Managers\ResolvedServersManager;
use Socialbox\Objects\ResolvedServer;

class ServerResolver
{
    private const string PATTERN = '/v=socialbox;sb-rpc=(https?:\/\/[^;]+);sb-key=([^;]+)/';

    /**
     * Resolves a given domain to fetch the RPC endpoint and public key from its DNS TXT records.
     *
     * @param string $domain The domain to be resolved.
     * @return ResolvedServer An instance of ResolvedServer containing the endpoint and public key.
     * @throws ResolutionException If the DNS TXT records cannot be resolved or if required information is missing.
     * @throws DatabaseOperationException
     */
    public static function resolveDomain(string $domain, bool $useDatabase=true): ResolvedServer
    {
        // First query the database to check if the domain is already resolved
        if($useDatabase)
        {
            $resolvedServer = ResolvedServersManager::getResolvedServer($domain);
            if($resolvedServer !== null)
            {
                return $resolvedServer->toResolvedServer();
            }
        }

        $txtRecords = self::dnsGetTxtRecords($domain);
        if ($txtRecords === false)
        {
            throw new ResolutionException(sprintf("Failed to resolve DNS TXT records for %s", $domain));
        }

        $fullRecord = self::concatenateTxtRecords($txtRecords);

        if (preg_match(self::PATTERN, $fullRecord, $matches))
        {
            $endpoint = trim($matches[1]);
            $publicKey = trim(str_replace(' ', '', $matches[2]));

            if (empty($endpoint))
            {
                throw new ResolutionException(sprintf("Failed to resolve RPC endpoint for %s", $domain));
            }

            if (empty($publicKey))
            {
                throw new ResolutionException(sprintf("Failed to resolve public key for %s", $domain));
            }

            return new ResolvedServer($endpoint, $publicKey);
        }
        else
        {
            throw new ResolutionException(sprintf("Failed to find valid SocialBox record for %s", $domain));
        }
    }

    /**
     * Retrieves the TXT records for a given domain using the dns_get_record function.
     *
     * @param string $domain The domain name to fetch TXT records for.
     * @return array|false An array of DNS TXT records on success, or false on failure.
     */
    private static function dnsGetTxtRecords(string $domain)
    {
        return dns_get_record($domain, DNS_TXT);
    }

    /**
     * Concatenates an array of TXT records into a single string.
     *
     * @param array $txtRecords An array of TXT records, where each record is expected to have a 'txt' key.
     * @return string A concatenated string of all TXT records.
     */
    private static function concatenateTxtRecords(array $txtRecords): string
    {
        $fullRecordBuilder = '';

        foreach ($txtRecords as $txt)
        {
            if (isset($txt['txt']))
            {
                $fullRecordBuilder .= trim($txt['txt'], '" ');
            }
        }

        return $fullRecordBuilder;
    }
}