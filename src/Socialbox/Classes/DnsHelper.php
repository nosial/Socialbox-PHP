<?php

    namespace Socialbox\Classes;

    use InvalidArgumentException;
    use Socialbox\Objects\DnsRecord;

    class DnsHelper
    {
        /**
         * Generates a TXT formatted string containing the provided RPC endpoint, public key, and expiration time.
         *
         * @param string $rpcEndpoint The RPC endpoint to include in the TXT string.
         * @param string $publicKey The public key to include in the TXT string.
         * @param int $expirationTime The expiration time in seconds to include in the TXT string.
         *
         * @return string A formatted TXT string containing the input data.
         */
        public static function generateTxt(string $rpcEndpoint, string $publicKey, int $expirationTime): string
        {
            return sprintf('v=socialbox;sb-rpc=%s;sb-key=%s;sb-exp=%d', $rpcEndpoint, $publicKey, $expirationTime);
        }

        /**
         * Parses a TXT record string and extracts its components into a DnsRecord object.
         *
         * @param string $txtRecord The TXT record string to be parsed.
         * @return DnsRecord The extracted DnsRecord object containing the RPC endpoint, public key, and expiration time.
         * @throws InvalidArgumentException If the TXT record format is invalid.
         */
        public static function parseTxt(string $txtRecord): DnsRecord
        {
            $pattern = '/v=socialbox;sb-rpc=(?P<rpcEndpoint>https?:\/\/[^;]+);sb-key=(?P<publicSigningKey>[^;]+);sb-exp=(?P<expirationTime>\d+)/';
            if (preg_match($pattern, $txtRecord, $matches))
            {
                return new DnsRecord($matches['rpcEndpoint'], $matches['publicSigningKey'], (int)$matches['expirationTime']);
            }

            throw new InvalidArgumentException('Invalid TXT record format.');
        }
    }