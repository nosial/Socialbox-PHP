<?php

    require 'ncc';
    import('net.nosial.socialbox');

    try
    {
        \Socialbox\Socialbox::handleRpc();
    }
    catch(Exception $e)
    {
        http_response_code(500);

        if(\Socialbox\Classes\Configuration::getSecurityConfiguration()->isDisplayInternalExceptions())
        {
            print_r($e);
            return;
        }

        print('An internal error occurred');
    }
