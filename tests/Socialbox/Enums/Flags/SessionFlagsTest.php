<?php

    namespace Socialbox\Enums\Flags;

    use PHPUnit\Framework\TestCase;

    class SessionFlagsTest extends TestCase
    {

        /**
         * Test that fromString correctly converts a valid comma-separated string to an array of SessionFlags.
         */
        public function testFromStringWithValidString()
        {
            $flagString = 'SET_PASSWORD,SET_EMAIL,VER_SMS';
            $expectedFlags = [
                SessionFlags::SET_PASSWORD,
                SessionFlags::SET_EMAIL,
                SessionFlags::VER_SMS,
            ];

            $result = SessionFlags::fromString($flagString);

            $this->assertEquals($expectedFlags, $result);
        }

        /**
         * Test that fromString handles an empty string correctly by returning an empty array.
         */
        public function testFromStringWithEmptyString()
        {
            $flagString = '';
            $result = SessionFlags::fromString($flagString);

            $this->assertEquals([], $result);
        }

        /**
         * Test that fromString correctly trims whitespace from flag values.
         */
        public function testFromStringWithWhitespace()
        {
            $flagString = ' SET_PASSWORD , SET_EMAIL , VER_SMS ';
            $expectedFlags = [
                SessionFlags::SET_PASSWORD,
                SessionFlags::SET_EMAIL,
                SessionFlags::VER_SMS,
            ];

            $result = SessionFlags::fromString($flagString);

            $this->assertEquals($expectedFlags, $result);
        }

        /**
         * Test that fromString throws an error for invalid values.
         */
        public function testFromStringWithInvalidValues()
        {
            $this->expectException(\ValueError::class);

            $flagString = 'INVALID_FLAG';
            SessionFlags::fromString($flagString);
        }

        /**
         * Test that fromString works for a single valid flag.
         */
        public function testFromStringWithSingleValue()
        {
            $flagString = 'SET_PASSWORD';
            $expectedFlags = [SessionFlags::SET_PASSWORD];

            $result = SessionFlags::fromString($flagString);

            $this->assertEquals($expectedFlags, $result);
        }

        /**
         * Test that fromString correctly handles duplicate flag values in the input string.
         */
        public function testFromStringWithDuplicateValues()
        {
            $flagString = 'SET_EMAIL,SET_EMAIL,VER_SMS';
            $expectedFlags = [
                SessionFlags::SET_EMAIL,
                SessionFlags::SET_EMAIL,
                SessionFlags::VER_SMS,
            ];

            $result = SessionFlags::fromString($flagString);

            $this->assertEquals($expectedFlags, $result);
        }

        /**
         * Test that isComplete returns true for an empty array of flags.
         */
        public function testIsCompleteWithEmptyFlags()
        {
            $flags = [];

            $result = SessionFlags::isComplete($flags);
            $this->assertTrue($result);
        }

        /**
         * Test that isComplete returns false when registration flags are incomplete.
         */
        public function testIsCompleteWithIncompleteRegistrationFlags()
        {
            $flags = [
                SessionFlags::REGISTRATION_REQUIRED,
                SessionFlags::SET_PASSWORD,
            ];

            $result = SessionFlags::isComplete($flags);

            $this->assertFalse($result);
        }

        /**
         * Test that isComplete returns false when authentication flags are incomplete.
         */
        public function testIsCompleteWithIncompleteAuthenticationFlags()
        {
            $flags = [
                SessionFlags::AUTHENTICATION_REQUIRED,
                SessionFlags::VER_PASSWORD,
            ];

            $result = SessionFlags::isComplete($flags);

            $this->assertFalse($result);
        }

        /**
         * Test that isComplete returns true when registration flags are complete.
         */
        public function testIsCompleteWithCompleteRegistrationFlags()
        {
            $flags = [
                SessionFlags::REGISTRATION_REQUIRED,
            ];

            $result = SessionFlags::isComplete($flags);

            $this->assertTrue($result);
        }

        /**
         * Test that isComplete returns true when authentication flags are complete.
         */
        public function testIsCompleteWithCompleteAuthenticationFlags()
        {
            $flags = [
                SessionFlags::AUTHENTICATION_REQUIRED,
            ];

            $result = SessionFlags::isComplete($flags);

            $this->assertTrue($result);
        }

        /**
         * Test that isComplete ignores non-relevant flags while processing.
         */
        public function testIsCompleteIgnoringNonRelevantFlags()
        {
            $flags = [
                SessionFlags::RATE_LIMITED,
                SessionFlags::AUTHENTICATION_REQUIRED,
            ];

            $result = SessionFlags::isComplete($flags);

            $this->assertTrue($result);
        }
    }
