<?php

    namespace Socialbox\Classes;

    use PHPUnit\Framework\TestCase;
    use Socialbox\Exceptions\CryptographyException;

    class OtpCryptographyTest extends TestCase
    {
        /**
         * Test that generateSecretKey generates a key of default length.
         */
        public function testGenerateSecretKeyDefaultLength()
        {
            $result = OtpCryptography::generateSecretKey();
            $this->assertEquals(64, strlen($result), 'Default secret key length (32 bytes) should produce 64 hex characters.');
        }

        /**
         * Test generateOTP with valid parameters to ensure a correct OTP.
         */
        public function testGenerateOTPValidParameters()
        {
            $secretKey = '12345678901234567890';
            $timeStep = 30;
            $digits = 6;
            $counter = 1;
            $otp = OtpCryptography::generateOTP($secretKey, $timeStep, $digits, $counter, 'sha1');
            $this->assertEquals(6, strlen($otp), 'OTP should have the correct number of digits.');
        }

        /**
         * Test generateOTP produces consistent results for the same inputs.
         */
        public function testGenerateOTPConsistency()
        {
            $secretKey = '12345678901234567890';
            $timeStep = 30;
            $digits = 6;
            $counter = 1;
            $otp1 = OtpCryptography::generateOTP($secretKey, $timeStep, $digits, $counter, 'sha1');
            $otp2 = OtpCryptography::generateOTP($secretKey, $timeStep, $digits, $counter, 'sha1');
            $this->assertEquals($otp1, $otp2, 'OTP should be consistent for the same secret key, counter, and configuration.');
        }

        /**
         * Test generateOTP produces different OTPs for the same secret key but different counters.
         */
        public function testGenerateOTPDifferentCounters()
        {
            $secretKey = '12345678901234567890';
            $timeStep = 30;
            $digits = 6;
            $otp1 = OtpCryptography::generateOTP($secretKey, $timeStep, $digits, 1, 'sha1');
            $otp2 = OtpCryptography::generateOTP($secretKey, $timeStep, $digits, 2, 'sha1');
            $this->assertNotEquals($otp1, $otp2, 'OTP should differ for the same secret key but different counters.');
        }

        /**
         * Test generateOTP throws an exception if hash length is invalid.
         */
        public function testGenerateOTPInvalidHashLength()
        {
            $secretKey = 'shortkey';
            $timeStep = 30;
            $digits = 6;
            $this->expectException(CryptographyException::class);
            OtpCryptography::generateOTP($secretKey, $timeStep, $digits, 1, 'sha1');
        }

        /**
         * Test generateOTP correctly handles different digit lengths.
         */
        public function testGenerateOTPDigitLength()
        {
            $secretKey = '12345678901234567890';
            $timeStep = 30;
            $otp6Digits = OtpCryptography::generateOTP($secretKey, $timeStep, 6, 1, 'sha1');
            $otp8Digits = OtpCryptography::generateOTP($secretKey, $timeStep, 8, 1, 'sha1');
            $this->assertEquals(6, strlen($otp6Digits), 'OTP with 6 digits should be generated.');
            $this->assertEquals(8, strlen($otp8Digits), 'OTP with 8 digits should be generated.');
        }

        /**
         * Test that generateSecretKey generates a key of custom length.
         */
        public function testGenerateSecretKeyCustomLength()
        {
            $customLength = 16; // 16 bytes
            $result = OtpCryptography::generateSecretKey($customLength);
            $this->assertEquals(32, strlen($result), "A secret key of $customLength bytes should produce 32 hex characters.");
        }

        /**
         * Test that generateSecretKey with a length of 0 results in a valid empty key.
         */
        public function testGenerateSecretKeyZeroLength()
        {
            $this->expectException(CryptographyException::class);
            OtpCryptography::generateSecretKey(0);
        }

        /**
         * Test that generateSecretKey with negative length throws an exception.
         */
        public function testGenerateSecretKeyNegativeLength()
        {
            $this->expectException(CryptographyException::class);
            OtpCryptography::generateSecretKey(-1);
        }

        /**
         * Test that generateSecretKey produces unique keys for multiple calls.
         */
        public function testGenerateSecretKeyUniqueKeys()
        {
            $key1 = OtpCryptography::generateSecretKey();
            $key2 = OtpCryptography::generateSecretKey();
            $this->assertNotEquals($key1, $key2, 'Generated secret keys should be unique.');
        }

        /**
         * Test verifyOTP with a valid OTP to ensure it returns true.
         */
        public function testVerifyOTPValid()
        {
            $secretKey = '12345678901234567890';
            $timeStep = 30;
            $digits = 6;
            // Generate the OTP to test validity
            $otp = OtpCryptography::generateOTP($secretKey, $timeStep, $digits, null, 'sha512');
            $this->assertTrue(OtpCryptography::verifyOTP($secretKey, $otp, $timeStep, 1, $digits, 'sha512'), 'verifyOTP should return true for a valid OTP.');
        }

        /**
         * Test verifyOTP with an invalid OTP to ensure it returns false.
         */
        public function testVerifyOTPInvalid()
        {
            $secretKey = '12345678901234567890';
            $timeStep = 30;
            $digits = 6;
            $invalidOtp = '999999'; // Arbitrary invalid OTP
            $this->assertFalse(OtpCryptography::verifyOTP($secretKey, $invalidOtp, $timeStep, 1, $digits, 'sha512'), 'verifyOTP should return false for an invalid OTP.');
        }

        /**
         * Test verifyOTP with an expired OTP window to ensure it returns false.
         */
        public function testVerifyOTPEExpiredWindow()
        {
            $secretKey = '12345678901234567890';
            $timeStep = 30;
            $digits = 6;
            $pastCounter = floor(time() / $timeStep) - 10; // Simulate OTP generated far in the past
            $expiredOtp = OtpCryptography::generateOTP($secretKey, $timeStep, $digits, $pastCounter, 'sha512');
            $this->assertFalse(OtpCryptography::verifyOTP($secretKey, $expiredOtp, $timeStep, 1, $digits, 'sha512'), 'verifyOTP should return false for an OTP outside the valid window.');
        }

        /**
         * Test verifyOTP with an invalid secret key to ensure it throws an exception.
         */
        public function testVerifyOTPInvalidSecretKey()
        {
            $this->expectException(CryptographyException::class);
            $invalidSecretKey = 'invalidkey';
            $otp = '123456';
            OtpCryptography::verifyOTP($invalidSecretKey, $otp, 30, 1, 6, 'sha512');
        }

        /**
         * Test verifyOTP with an incorrect digit length to ensure it returns false.
         */
        public function testVerifyOTPInvalidDigitLength()
        {
            $secretKey = '12345678901234567890';
            $timeStep = 30;
            $digits = 8; // Generate an 8-digit OTP
            $validOtp = OtpCryptography::generateOTP($secretKey, $timeStep, $digits, null, 'sha512');
            // Verifying with a 6-digit configuration instead
            $this->assertFalse(OtpCryptography::verifyOTP($secretKey, $validOtp, $timeStep, 1, 6, 'sha512'), 'verifyOTP should return false if the digit length does not match.');
        }
    }