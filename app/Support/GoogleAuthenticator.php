<?php

namespace App\Support;

class GoogleAuthenticator
{
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a new random Base32 secret key.
     */
    public static function generateSecret(int $length = 16): string
    {
        $secret = '';
        $validChars = self::BASE32_CHARS;
        $maxIndex = strlen($validChars) - 1;

        $bytes = random_bytes($length);
        for ($i = 0; $i < $length; $i++) {
            $secret .= $validChars[ord($bytes[$i]) % ($maxIndex + 1)];
        }

        return $secret;
    }

    /**
     * Calculate the current 6-digit TOTP code for a secret.
     */
    public static function getCode(string $secret, ?int $timeSlice = null): string
    {
        if ($timeSlice === null) {
            $timeSlice = (int) floor(time() / 30);
        }

        $secretBinary = self::base32Decode($secret);

        // Pack time into 8-byte big-endian binary string
        $timePacked = pack('N*', 0) . pack('N*', $timeSlice);

        // Calculate HMAC-SHA1
        $hmac = hash_hmac('sha1', $timePacked, $secretBinary, true);

        // Dynamic truncation (RFC 4226 / RFC 6238)
        $offset = ord(substr($hmac, -1)) & 0x0F;
        $truncatedHash = (
            ((ord($hmac[$offset]) & 0x7F) << 24) |
            ((ord($hmac[$offset + 1]) & 0xFF) << 16) |
            ((ord($hmac[$offset + 2]) & 0xFF) << 8) |
            (ord($hmac[$offset + 3]) & 0xFF)
        );

        $code = $truncatedHash % 1000000;

        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a 6-digit code against a secret with clock drift tolerance.
     *
     * @param string $secret
     * @param string $code
     * @param int $discrepancy Number of 30-second windows before/after to check (1 = +/- 30s)
     * @return bool
     */
    public static function verifyCode(string $secret, string $code, int $discrepancy = 1): bool
    {
        $code = trim($code);
        if (strlen($code) !== 6 || ! ctype_digit($code)) {
            return false;
        }

        $currentTimeSlice = (int) floor(time() / 30);

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = self::getCode($secret, $currentTimeSlice + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate the standard otpauth URL for Google Authenticator.
     */
    public static function getOtpAuthUrl(string $email, string $secret, string $issuer = 'Microfleet'): string
    {
        $account = rawurlencode($email);
        $encodedIssuer = rawurlencode($issuer);

        return "otpauth://totp/{$encodedIssuer}:{$account}?secret={$secret}&issuer={$encodedIssuer}&algorithm=SHA1&digits=6&period=30";
    }

    /**
     * Get QR Code image URL for scanning with Google Authenticator.
     */
    public static function getQrCodeUrl(string $email, string $secret, string $issuer = 'Microfleet', int $size = 200): string
    {
        $otpUrl = self::getOtpAuthUrl($email, $secret, $issuer);
        return 'https://api.qrserver.com/v1/create-qr-code/?size='.$size.'x'.$size.'&margin=2&data='.rawurlencode($otpUrl);
    }

    /**
     * Format a secret key into spaced readable groups (e.g. ABCD EFGH IJKL MNOP)
     */
    public static function formatSecret(string $secret): string
    {
        return trim(chunk_split(strtoupper($secret), 4, ' '));
    }

    /**
     * Generate one-time emergency recovery codes.
     */
    public static function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $code = strtoupper(bin2hex(random_bytes(2))) . '-' . strtoupper(bin2hex(random_bytes(2)));
            $codes[] = $code;
        }

        return $codes;
    }

    /**
     * Decode a Base32 string to binary.
     */
    private static function base32Decode(string $b32): string
    {
        $b32 = strtoupper(str_replace([' ', '-', '='], '', $b32));
        $chars = self::BASE32_CHARS;
        $buffer = 0;
        $bitsLeft = 0;
        $binary = '';

        for ($i = 0, $len = strlen($b32); $i < $len; $i++) {
            $char = $b32[$i];
            $val = strpos($chars, $char);
            if ($val === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $binary .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $binary;
    }
}
