<?php
function generateOtpSecret(): string
{
    $bytes = random_bytes(20);
    return strtoupper(base_encode32($bytes));
}

function getQrCodeUrl(string $secret, string $label): string
{
    $issuer = 'DigiSign';
    $url_encoded_label = urlencode($label);
    $url_encoded_issuer = urlencode($issuer);
    $secret_url_encoded = urlencode($secret);

    return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" .
           urlencode("otpauth://totp/{$issuer}:{$url_encoded_label}?secret={$secret_url_encoded}&issuer={$url_encoded_issuer}");
}

function verifyOtp(string $secret, string $code): bool
{
    return verifyTOTP($secret, $code);
}

function verifyTOTP(string $secret, string $code): bool
{
    $secret = strtoupper($secret);
    $code = str_pad($code, 6, '0', STR_PAD_LEFT);
    $time_slice = floor(time() / 30);

    for ($i = -1; $i <= 1; $i++) {
        $expected_code = calculateTOTP($secret, $time_slice + $i);
        if (hash_equals($expected_code, $code)) {
            return true;
        }
    }

    return false;
}

function calculateTOTP(string $secret, int $time_slice): string
{
    $time_hex = str_pad(dechex($time_slice), 16, '0', STR_PAD_LEFT);
    $time_binary = hex2bin($time_hex);
    $secret_binary = base32_decode($secret);
    $hash = hash_hmac('sha1', $time_binary, $secret_binary, true);
    $offset = ord(substr($hash, -1)) & 0x0F;
    $binary = (ord(substr($hash, $offset)) & 0x7F) << 24 |
              (ord(substr($hash, $offset + 1)) & 0xFF) << 16 |
              (ord(substr($hash, $offset + 2)) & 0xFF) << 8 |
              (ord(substr($hash, $offset + 3)) & 0xFF);
    $otp = $binary % pow(10, 6);
    return str_pad((string)$otp, 6, '0', STR_PAD_LEFT);
}

function base32_decode(string $data): string
{
    $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $charset_map = array_flip(str_split($charset));
    $data = strtoupper(preg_replace('/[^A-Z2-7]/', '', $data));
    $binary = '';
    $buffer = 0;
    $bits_left = 0;
    for ($i = 0; $i < strlen($data); $i++) {
        $char = $data[$i];
        if (!isset($charset_map[$char])) {
            continue;
        }
        $buffer = ($buffer << 5) | $charset_map[$char];
        $bits_left += 5;
        if ($bits_left >= 8) {
            $bits_left -= 8;
            $binary .= chr(($buffer >> $bits_left) & 0xFF);
        }
    }
    return $binary;
}

function base_encode32(string $data): string
{
    $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $buffer = 0;
    $bits_left = 0;
    $output = '';
    for ($i = 0; $i < strlen($data); $i++) {
        $buffer = ($buffer << 8) | ord($data[$i]);
        $bits_left += 8;
        while ($bits_left >= 5) {
            $bits_left -= 5;
            $output .= $charset[($buffer >> $bits_left) & 0x1F];
        }
    }
    if ($bits_left > 0) {
        $output .= $charset[($buffer << (5 - $bits_left)) & 0x1F];
    }
    return $output;
}