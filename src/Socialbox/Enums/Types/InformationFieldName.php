<?php

    namespace Socialbox\Enums\Types;

    enum InformationFieldName : string
    {
        /**
         * The display name of the peer
         */
        case DISPLAY_NAME = 'DISPLAY_NAME';

        /**
         * The display picture of the peer, the value is a resource ID hosted on the server
         */
        case DISPLAY_PICTURE = 'DISPLAY_PICTURE';

        /**
         * The first name of the peer
         */
        case FIRST_NAME = 'FIRST_NAME';

        /**
         * The middle name of the peer
         */
        case MIDDLE_NAME = 'MIDDLE_NAME';

        /**
         * The last name of the peer
         */
        case LAST_NAME = 'LAST_NAME';

        /**
         * The email address of the peer
         */
        case EMAIL_ADDRESS = 'EMAIL_ADDRESS';

        /**
         * The phone number of the peer
         */
        case PHONE_NUMBER = 'PHONE_NUMBER';

        /**
         * The birthday of the peer
         */
        case BIRTHDAY = 'BIRTHDAY';

        /**
         * The peer's personal/public URL
         */
        case URL = 'URL';

        /**
         * Validates the value of the field
         *
         * @param string $value The value to validate
         * @return bool Returns true if the value is valid, false otherwise
         */
        public function validate(string $value): bool
        {
            return match ($this)
            {
                InformationFieldName::DISPLAY_NAME => strlen($value) >= 3 && strlen($value) <= 50,
                InformationFieldName::LAST_NAME, InformationFieldName::MIDDLE_NAME, InformationFieldName::FIRST_NAME => strlen($value) >= 2 && strlen($value) <= 50,
                InformationFieldName::EMAIL_ADDRESS => filter_var($value, FILTER_VALIDATE_EMAIL),
                InformationFieldName::PHONE_NUMBER => preg_match('/^\+?[0-9]{1,3}-?[0-9]{3}-?[0-9]{3}-?[0-9]{4}$/', $value),
                InformationFieldName::BIRTHDAY => preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $value),
                InformationFieldName::URL => filter_var($value, FILTER_VALIDATE_URL),
                default => true,
            };
        }
    }
