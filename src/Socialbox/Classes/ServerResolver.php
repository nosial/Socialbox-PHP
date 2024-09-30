<?php

namespace Socialbox\Classes;

use Socialbox\Exceptions\ResolutionException;
use Socialbox\Objects\ResolvedServer;

class ServerResolver
{
    /**
     * Resolves a given domain to fetch the RPC endpoint and public key from its DNS TXT records.
     *
     * @param string $domain The domain to be resolved.
     * @return ResolvedServer An instance of ResolvedServer containing the endpoint and public key.
     * @throws ResolutionException If the DNS TXT records cannot be resolved or if required information is missing.
     */
    public static function resolveDomain(string $domain): ResolvedServer
    {
        $txtRecords = dns_get_record($domain, DNS_TXT);

        if ($txtRecords === false)
        {
            throw new ResolutionException(sprintf("Failed to resolve DNS TXT records for %s", $domain));
        }

        $endpoint = null;
        $publicKey = null;
        foreach ($txtRecords as $txt)
        {
            if (isset($txt['txt']) && str_starts_with($txt['txt'], 'socialbox='))
            {
                $endpoint = substr($txt['txt'], strlen('socialbox='));
            }
            elseif (isset($txt['txt']) && str_starts_with($txt['txt'], 'socialbox-key='))
            {
                $publicKey = substr($txt['txt'], strlen('socialbox-key='));
            }
        }

        if ($endpoint === null)
        {
            throw new ResolutionException(sprintf("Failed to resolve RPC endpoint for %s", $domain));
        }

        if ($publicKey === null)
        {
            throw new ResolutionException(sprintf("Failed to resolve public key for %s", $domain));
        }

        return new ResolvedServer($endpoint, $publicKey);
    }
}