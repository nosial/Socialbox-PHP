<?php

namespace Socialbox\Classes\ClientCommands;

use Socialbox\Classes\Cryptography;
use Socialbox\Classes\Logger;
use Socialbox\Classes\Utilities;
use Socialbox\Exceptions\CryptographyException;
use Socialbox\Exceptions\DatabaseOperationException;
use Socialbox\Exceptions\ResolutionException;
use Socialbox\Exceptions\RpcException;
use Socialbox\Interfaces\CliCommandInterface;
use Socialbox\Objects\ClientSession;
use Socialbox\SocialClient;

class ConnectCommand implements CliCommandInterface
{
    public static function execute(array $args): int
    {
        if(!isset($args['name']))
        {
            Logger::getLogger()->error('The name argument is required, this is the name of the session');
        }

        $workingDirectory = getcwd();

        if(isset($args['directory']))
        {
            if(!is_dir($args['directory']))
            {
                Logger::getLogger()->error('The directory provided does not exist');
                return 1;
            }

            $workingDirectory = $args['directory'];
        }

        $sessionFile = $workingDirectory . DIRECTORY_SEPARATOR . Utilities::sanitizeFileName($args['name']) . '.json';

        if(!file_exists($sessionFile))
        {
            return self::createSession($args, $sessionFile);
        }

        Logger::getLogger()->info(sprintf('Session file already exists at %s', $sessionFile));
        return 0;
    }

    private static function createSession(array $args, string $sessionFile): int
    {
        if(!isset($args['domain']))
        {
            Logger::getLogger()->error('The domain argument is required, this is the domain of the socialbox instance');
            return 1;
        }

        try
        {
            $client = new SocialClient($args['domain']);
        }
        catch (DatabaseOperationException $e)
        {
            Logger::getLogger()->error('Failed to create the client session', $e);
            return 1;
        }
        catch (ResolutionException $e)
        {
            Logger::getLogger()->error('Failed to resolve the domain', $e);
            return 1;
        }

        try
        {
            $keyPair = Cryptography::generateKeyPair();
            $session = $client->createSession($keyPair);
        }
        catch (CryptographyException | RpcException $e)
        {
            Logger::getLogger()->error('Failed to create the session', $e);
            return 1;
        }

        $sessionData = new ClientSession([
            'domain' => $args['domain'],
            'session_uuid' => $session,
            'public_key' => $keyPair->getPublicKey(),
            'private_key' => $keyPair->getPrivateKey()
        ]);

        $sessionData->save($sessionFile);
        Logger::getLogger()->info(sprintf('Session created and saved to %s', $sessionFile));
        return 0;
    }

    public static function getHelpMessage(): string
    {
        return <<<HELP
Usage: socialbox connect --name <name> --domain <domain> [--directory <directory>]

Creates a new session with the specified name and domain. The session will be saved to the current working directory by default, or to the specified directory if provided.

Options:
  --name       The name of the session to create.
  --domain     The domain of the socialbox instance.
  --directory  The directory where the session file should be saved.

Example:
    socialbox connect --name mysession --domain socialbox.example.com
HELP;

    }

    public static function getShortHelpMessage(): string
    {
        return 'Connect Command - Creates a new session with the specified name and domain';
    }
}