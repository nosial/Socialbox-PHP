<?php

namespace Socialbox\Classes\Configuration;

class SecurityConfiguration
{
    private bool $displayInternalErrors;
    private int $resolvedServersTtl;
    private int $captchaTtl;

    /**
     * Constructor method for initializing class properties.
     *
     * @param array $data An associative array containing values for initializing the properties.
     *
     * @return void
     */
    public function __construct(array $data)
    {
        $this->displayInternalErrors = $data['display_internal_errors'];
        $this->resolvedServersTtl = $data['resolved_servers_ttl'];
        $this->captchaTtl = $data['captcha_ttl'];
    }

    /**
     * Determines if the display of internal errors is enabled.
     *
     * @return bool True if the display of internal errors is enabled, false otherwise.
     */
    public function isDisplayInternalErrors(): bool
    {
        return $this->displayInternalErrors;
    }

    /**
     * Retrieves the time-to-live (TTL) value for resolved servers.
     *
     * @return int The TTL value for resolved servers.
     */
    public function getResolvedServersTtl(): int
    {
        return $this->resolvedServersTtl;
    }

    /**
     * Retrieves the time-to-live (TTL) value for captchas.
     *
     * @return int The TTL value for captchas.
     */
    public function getCaptchaTtl(): int
    {
        return $this->captchaTtl;
    }

}