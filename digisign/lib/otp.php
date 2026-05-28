<?php
define('BASE32_CHARS', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567');

function generateTOTPSecret(): string
{
    $secret   = '';
    $alphabet = BASE32_CHARS;
    for ($i = 0; $i < 16; $i++) {
        $secret .= $alphabet[random_int(0, 31)];
    }
    return $secret;
}

function base32Decode(string $input): string
{
    $input  = strtoupper($input);
    $input  = rtrim($input, '=');
    $lookup = array_flip(str_split(BASE32_CHARS));

    $binaryString = '';
    foreach (str_split($input) as $char) {
        if (!isset($lookup[$char])) continue;
        $binaryString .= str_pad(decbin($lookup[$char]), 5, '0', STR_PAD_LEFT);
    }

    $bytes = '';
    foreach (str_split($binaryString, 8) as $chunk) {
        if (strlen($chunk) === 8) {
            $bytes .= chr(bindec($chunk));
        }
    }
    return $bytes;
}

function generateTOTPCode(string $secret, int $timeStep): string
{
    $key     = base32Decode($secret);
    $counter = pack('N*', 0) . pack('N*', $timeStep);
    $hash    = hash_hmac('sha1', $counter, $key, true);
    $offset  = ord($hash[19]) & 0x0F;
    $code    = (
        ((ord($hash[$offset])     & 0x7F) << 24) |
        ((ord($hash[$offset + 1]) & 0xFF) << 16) |
        ((ord($hash[$offset + 2]) & 0xFF) <<  8) |
        ((ord($hash[$offset + 3]) & 0xFF))
    );
    return str_pad((string)($code % 1000000), 6, '0', STR_PAD_LEFT);
}

function verifyTOTP(string $secret, string $code): bool
{
    if (!preg_match('/^\d{6}$/', $code)) {
        return false;
    }
    $timeStep = (int)floor(time() / 30);
    for ($window = -1; $window <= 1; $window++) {
        if (hash_equals(generateTOTPCode($secret, $timeStep + $window), $code)) {
            return true;
        }
    }
    return false;
}

function getQRCodeDataUrl(string $secret, string $email, string $issuer = 'DigiSign'): string
{
    $label  = rawurlencode($issuer . ':' . $email);
    $params = http_build_query([
        'secret'    => $secret,
        'issuer'    => $issuer,
        'algorithm' => 'SHA1',
        'digits'    => 6,
        'period'    => 30,
    ]);
    $otpUri = "otpauth://totp/{$label}?{$params}";
    return 'https://api.qrserver.com/v1/create-qr-code/?' . http_build_query([
        'size'   => '200x200',
        'data'   => $otpUri,
        'margin' => 8,
        'format' => 'png',
    ]);
}
