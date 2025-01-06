<?php

    namespace Socialbox\Classes\StandardMethods;

    use Exception;
    use Socialbox\Abstracts\Method;
    use Socialbox\Classes\Validator;
    use Socialbox\Enums\Flags\SessionFlags;
    use Socialbox\Enums\StandardError;
    use Socialbox\Exceptions\StandardException;
    use Socialbox\Interfaces\SerializableInterface;
    use Socialbox\Managers\RegisteredPeerManager;
    use Socialbox\Managers\SessionManager;
    use Socialbox\Objects\ClientRequest;
    use Socialbox\Objects\RpcRequest;

    class SettingsSetBirthday extends Method
    {
        /**
         * @inheritDoc
         */
        public static function execute(ClientRequest $request, RpcRequest $rpcRequest): ?SerializableInterface
        {
            if(!$rpcRequest->containsParameter('month'))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Missing 'month' parameter");
            }

            if(!$rpcRequest->containsParameter('day'))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Missing 'day' parameter");
            }

            if(!$rpcRequest->containsParameter('year'))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Missing 'year' parameter");
            }

            $month = $rpcRequest->getParameter('month');
            $day = $rpcRequest->getParameter('day');
            $year = $rpcRequest->getParameter('year');

            if(!Validator::validateDate($month, $day, $year))
            {
                return $rpcRequest->produceError(StandardError::RPC_INVALID_ARGUMENTS, "Invalid date provided, must be a valid gregorian calender date.");
            }

            try
            {
                // Set the password
                RegisteredPeerManager::updateBirthday($request->getPeer(), $month, $day, $year);

                // Check & update the session flow
                SessionManager::updateFlow($request->getSession(), [SessionFlags::SET_BIRTHDAY]);
            }
            catch(Exception $e)
            {
                throw new StandardException('Failed to set birthday due to an internal exception', StandardError::INTERNAL_SERVER_ERROR, $e);
            }

            return $rpcRequest->produceResponse(true);
        }
    }