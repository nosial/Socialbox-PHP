<?php

    require 'ncc';
    import('net.nosial.socialbox');

    $client = new \Socialbox\Classes\RpcClient(generateRandomPeer());
    var_dump($client->exportSession());

    function generateRandomPeer()
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < 16; $i++)
        {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return 'userTest' . $randomString . '@intvo.id';
    }