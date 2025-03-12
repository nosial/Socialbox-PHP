<?php

    namespace Socialbox\Managers;

    use InvalidArgumentException;
    use PDOException;
    use Socialbox\Classes\Configuration;
    use Socialbox\Classes\Database;
    use Socialbox\Classes\Validator;
    use Socialbox\Enums\PrivacyState;
    use Socialbox\Enums\Types\InformationFieldName;
    use Socialbox\Exceptions\DatabaseOperationException;
    use Socialbox\Objects\Database\PeerInformationFieldRecord;
    use Socialbox\Objects\Database\PeerDatabaseRecord;

    class PeerInformationManager
    {
        /**
         * Adds a property to a peer's information record.
         *
         * @param string|PeerDatabaseRecord $peerUuid The UUID of the peer to add the property to.
         * @param InformationFieldName $property The name of the property to add.
         * @param string $value The value of the property to add.
         * @param PrivacyState|null $privacyState The privacy state of the property to add.
         * @return void
         * @throws DatabaseOperationException Thrown if the operation fails.
         */
        public static function addField(string|PeerDatabaseRecord $peerUuid, InformationFieldName $property, string $value, ?PrivacyState $privacyState=null): void
        {
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }

            if($privacyState === null)
            {
                $privacyState = match($property)
                {
                    InformationFieldName::DISPLAY_NAME => Configuration::getPoliciesConfiguration()->getDefaultDisplayPicturePrivacy(),
                    InformationFieldName::FIRST_NAME => Configuration::getPoliciesConfiguration()->getDefaultFirstNamePrivacy(),
                    InformationFieldName::MIDDLE_NAME => Configuration::getPoliciesConfiguration()->getDefaultMiddleNamePrivacy(),
                    InformationFieldName::LAST_NAME => Configuration::getPoliciesConfiguration()->getDefaultLastNamePrivacy(),
                    InformationFieldName::EMAIL_ADDRESS => Configuration::getPoliciesConfiguration()->getDefaultEmailAddressPrivacy(),
                    InformationFieldName::PHONE_NUMBER => Configuration::getPoliciesConfiguration()->getDefaultPhoneNumberPrivacy(),
                    InformationFieldName::BIRTHDAY => Configuration::getPoliciesConfiguration()->getDefaultBirthdayPrivacy(),
                    InformationFieldName::DISPLAY_PICTURE => COnfiguration::getPoliciesConfiguration()->getDefaultDisplayPicturePrivacy(),
                    InformationFieldName::URL => Configuration::getPoliciesConfiguration()->getDefaultUrlPrivacy(),
                };
            }

            try
            {
                $stmt = Database::getConnection()->prepare('INSERT INTO peer_information (peer_uuid, property_name, property_value, privacy_state) VALUES (:peer_uuid, :property_name, :property_value, :privacy_state)');
                $stmt->bindValue(':peer_uuid', $peerUuid);
                $propertyName = $property->value;
                $stmt->bindValue(':property_name', $propertyName);
                $stmt->bindValue(':property_value', $value);
                $stmt->bindValue(':privacy_state', $privacyState->value);
                $stmt->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException(sprintf('Failed to add property for peer %s', $peerUuid), $e);
            }
        }

        /**
         * Updates a property for a peer's information record.
         *
         * @param string|PeerDatabaseRecord $peerUuid The UUID of the peer to update the property for.
         * @param InformationFieldName $property The name of the property to update.
         * @param string $value The new value of the property.
         * @return void
         * @throws DatabaseOperationException Thrown if the operation fails.
         */
        public static function updateField(string|PeerDatabaseRecord $peerUuid, InformationFieldName $property, string $value): void
        {
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }

            if(!self::fieldExists($peerUuid, $property))
            {
                throw new DatabaseOperationException(sprintf('Cannot to update property %s for peer %s, property does not exist', $property->value, $peerUuid));
            }

            try
            {
                $stmt = Database::getConnection()->prepare('UPDATE peer_information SET property_value=:property_value WHERE peer_uuid=:peer_uuid AND property_name=:property_name');
                $stmt->bindValue(':peer_uuid', $peerUuid);
                $propertyName = $property->value;
                $stmt->bindValue(':property_name', $propertyName);
                $stmt->bindValue(':property_value', $value);
                $stmt->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException(sprintf('Failed to update property %s for peer %s', $property->value, $peerUuid), $e);
            }
        }

        /**
         * Updates the privacy state for a property in a peer's information record.
         *
         * @param string|PeerDatabaseRecord $peerUuid The UUID of the peer to update the privacy state for.
         * @param InformationFieldName $property The name of the property to update the privacy state for.
         * @param PrivacyState $privacyState The new privacy state of the property.
         * @return void
         * @throws DatabaseOperationException Thrown if the operation fails.
         */
        public static function updatePrivacyState(string|PeerDatabaseRecord $peerUuid, InformationFieldName $property, PrivacyState $privacyState): void
        {
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }
            elseif(!Validator::validateUuid($peerUuid))
            {
                throw new InvalidArgumentException('The given internal peer UUID is not a valid UUID V4');
            }

            if(!self::fieldExists($peerUuid, $property))
            {
                throw new InvalidArgumentException(sprintf('Cannot update privacy state, the requested property %s does not exist with %s', $property->value, $peerUuid));
            }
            
            try
            {
                $stmt = Database::getConnection()->prepare('UPDATE peer_information SET privacy_state=:privacy_state WHERE peer_uuid=:peer_uuid AND property_name=:property_name');
                $stmt->bindValue(':peer_uuid', $peerUuid);
                $propertyName = $property->value;
                $stmt->bindValue(':property_name', $propertyName);
                $privacyState = $privacyState->value;
                $stmt->bindValue(':privacy_state', $privacyState);
                $stmt->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException(sprintf('Failed to update privacy state for property %s for peer %s', $property->value, $peerUuid), $e);
            }
        }

        /**
         * Checks if a property exists for a peer.
         *
         * @param string|PeerDatabaseRecord $peerUuid The UUID of the peer to check for the property.
         * @param InformationFieldName $property The name of the property to check for.
         * @return bool
         * @throws DatabaseOperationException Thrown if the operation fails.
         */
        public static function fieldExists(string|PeerDatabaseRecord $peerUuid, InformationFieldName $property): bool
        {
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }
            elseif(!Validator::validateUuid($peerUuid))
            {
                throw new InvalidArgumentException('The given internal peer UUID is not a valid UUID V4');
            }

            try
            {
                $stmt = Database::getConnection()->prepare('SELECT COUNT(*) FROM peer_information WHERE peer_uuid=:peer_uuid AND property_name=:property_name LIMIT 1');
                $stmt->bindValue(':peer_uuid', $peerUuid);
                $propertyName = $property->value;
                $stmt->bindValue(':property_name', $propertyName);
                $stmt->execute();

                return $stmt->fetchColumn() > 0;
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException(sprintf('Failed to check if property exists for peer %s', $peerUuid), $e);
            }
        }

        /**
         * Gets a property from a peer's information record.
         *
         * @param string|PeerDatabaseRecord $peerUuid The UUID of the peer to get the property from.
         * @param InformationFieldName $property The name of the property to get.
         * @return PeerInformationFieldRecord|null The property record, or null if it does not exist.
         * @throws DatabaseOperationException Thrown if the operation fails.
         */
        public static function getField(string|PeerDatabaseRecord $peerUuid, InformationFieldName $property): ?PeerInformationFieldRecord
        {
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }

            try
            {
                $stmt = Database::getConnection()->prepare('SELECT * FROM peer_information WHERE peer_uuid=:peer_uuid AND property_name=:property_name LIMIT 1');
                $stmt->bindValue(':peer_uuid', $peerUuid);
                $propertyName = $property->value;
                $stmt->bindValue(':property_name', $propertyName);
                $stmt->execute();

                $result = $stmt->fetch();
                if($result === false)
                {
                    return null;
                }

                return PeerInformationFieldRecord::fromArray($result);
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException(sprintf('Failed to get property %s for peer %s', $property->value, $peerUuid), $e);
            }
        }

        /**
         * Gets all properties from a peer's information record.
         *
         * @param string|PeerDatabaseRecord $peerUuid The UUID of the peer to get the properties from.
         * @return PeerInformationFieldRecord[]
         * @throws DatabaseOperationException Thrown if the operation fails.
         */
        public static function getFields(string|PeerDatabaseRecord $peerUuid): array
        {
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }

            try
            {
                $stmt = Database::getConnection()->prepare('SELECT * FROM peer_information WHERE peer_uuid=:peer_uuid');
                $stmt->bindValue(':peer_uuid', $peerUuid);
                $stmt->execute();
                $results = $stmt->fetchAll();

                if(!$results)
                {
                    return [];
                }

                return array_map(fn($result) => PeerInformationFieldRecord::fromArray($result), $results);
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException(sprintf('Failed to get properties for peer %s', $peerUuid), $e);
            }
        }

        /**
         * Gets all properties from a peer's information record that match the provided privacy filters.
         *
         * @param string|PeerDatabaseRecord $peerUuid The UUID of the peer to get the properties from.
         * @param PrivacyState[] $privacyFilters The privacy filters to apply.
         * @return PeerInformationFieldRecord[] The filtered properties.
         * @throws DatabaseOperationException Thrown if the operation fails.
         */
        public static function getFilteredFields(string|PeerDatabaseRecord $peerUuid, array $privacyFilters): array
        {
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }

            $results = [];
            foreach($privacyFilters as $privacyState)
            {
                try
                {
                    $stmt = Database::getConnection()->prepare('SELECT * FROM peer_information WHERE peer_uuid=:peer_uuid AND privacy_state=:privacy_state');
                    $stmt->bindValue(':peer_uuid', $peerUuid);
                    $stmt->bindValue(':privacy_state', $privacyState->value);
                    $stmt->execute();
                    $results = array_merge($results, $stmt->fetchAll());
                }
                catch(PDOException $e)
                {
                    throw new DatabaseOperationException(sprintf('Failed to get properties for peer %s with privacy state %s', $peerUuid, $privacyState->value), $e);
                }
            }

            if(!$results)
            {
                return [];
            }

            return array_map(fn($result) => PeerInformationFieldRecord::fromArray($result), $results);
        }

        /**
         * Deletes a property from a peer's information record.
         *
         * @param string|PeerDatabaseRecord $peerUuid The UUID of the peer to delete the property from.
         * @param InformationFieldName $property The name of the property to delete.
         * @return void
         * @throws DatabaseOperationException Thrown if the operation fails.
         */
        public static function deleteField(string|PeerDatabaseRecord $peerUuid, InformationFieldName $property): void
        {
            if($peerUuid instanceof PeerDatabaseRecord)
            {
                $peerUuid = $peerUuid->getUuid();
            }

            try
            {
                $stmt = Database::getConnection()->prepare('DELETE FROM peer_information WHERE peer_uuid=:peer_uuid AND property_name=:property_name');
                $stmt->bindValue(':peer_uuid', $peerUuid);
                $propertyName = $property->value;
                $stmt->bindValue(':property_name', $propertyName);
                $stmt->execute();
            }
            catch(PDOException $e)
            {
                throw new DatabaseOperationException(sprintf('Failed to delete property %s for peer %s', $property->value, $peerUuid), $e);
            }
        }
    }