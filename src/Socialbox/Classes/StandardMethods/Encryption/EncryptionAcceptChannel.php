<?php

    namespace Socialbox\Classes\StandardMethods\Encryption;

    use Exception;
    use InvalidArgumentException;
    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Cryptography;
    use Socialbox\Classes\Validator;
    use Socialbox\Enums\ReservedUsernames;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\Standard\InvalidRpcArgumentException;
    use Socialbox\Exceptions\Standard\MissingRpcArgumentException;
    use Socialbox\Exceptions\Standard\StandardRpcException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\EncryptionChannelManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\PeerAddress;
    use Socialbox\Objects\RpcRequest;

    class EncryptionAcceptChannel extends Method
    {

        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {

        }
    }