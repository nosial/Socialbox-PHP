<?php

    require 'ncc';
    import('net.nosial.socialbox');

    \Socialbox\Classes\ServerResolver::addMock('coffee.com', 'v=socialbox;sb-rpc=http://127.0.0.0:8086/;sb-key=sig:g59Cf8j1wmQmRg1MkveYbpdiZ-1-_hFU9eRRJmQAwmc;sb-exp=0');
    \Socialbox\Classes\ServerResolver::addMock('teapot.com', 'v=socialbox;sb-rpc=http://127.0.0.0:8087/;sb-key=sig:MDXUuripAo_IAv-EZTEoFhpIdhsXxfMLNunSnQzxYiY;sb-exp=0');

    $client = new \Socialbox\SocialClient(generateRandomPeer());
    var_dump($client->getSessionState());


    function generateRandomPeer()
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < 16; $i++)
        {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return 'userTest' . $randomString . '@coffee.com';
    }