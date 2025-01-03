<?php

    namespace Socialbox\Objects;

    use Socialbox\Objects\Standard\ServerInformation;

    class ResolvedServer
    {
        private DnsRecord $dnsRecord;
        private ServerInformation $serverInformation;
    }