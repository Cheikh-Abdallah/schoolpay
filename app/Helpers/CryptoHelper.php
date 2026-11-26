<?php

declare(strict_types=1);

namespace App\Helpers;

use RuntimeException;

/**
 * Chiffrement et déchiffrement réversible de niveau militaire (AES-256-GCM)
 * Utilisé pour protéger les clés d'API et secrets SendText en base de données
 */
class CryptoHelper
{
    private const CIPHER = 'aes-256-gcm';

    private static function getKey(): string
    {
        $rawKey = defined('APP_KEY') ? APP_KEY : '';
        if (str_starts_with($rawKey, 'base64:')) {
            $key = base64_decode(substr($rawKey, 7), true);
            if ($key !== false && strlen($key) === 32) {
                return $key;
            }
        }

        // Fallback déterministe si la clé n'est pas en base64 de 32 octets
        return hash('sha256', $rawKey !== '' ? $rawKey : 'SchoolPaySecretDefaultKey2026', true);
    }

    /**
     * Chiffre une chaîne en AES-256-GCM et retourne une chaîne encodée en base64
     */
    public static function encrypt(string $plainText): string
    {
        $key = self::getKey();
        $ivLen = openssl_cipher_iv_length(self::CIPHER);
        $iv = openssl_random_pseudo_bytes($ivLen);
        $tag = '';

        $cipherText = openssl_encrypt(
            $plainText,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );

        if ($cipherText === false) {
            throw new RuntimeException('Échec du chiffrement des données.');
        }

        // Assemblage : IV (12/16 octets) + Tag (16 octets) + Données chiffrées
        return base64_encode($iv . $tag . $cipherText);
    }

    /**
     * Déchiffre une chaîne chiffrée par encrypt()
     */
    public static function decrypt(string $encryptedBase64): ?string
    {
        $decoded = base64_decode($encryptedBase64, true);
        if ($decoded === false) {
            return null;
        }

        $key = self::getKey();
        $ivLen = openssl_cipher_iv_length(self::CIPHER);
        $tagLen = 16;

        if (strlen($decoded) < ($ivLen + $tagLen)) {
            return null;
        }

        $iv = substr($decoded, 0, $ivLen);
        $tag = substr($decoded, $ivLen, $tagLen);
        $cipherText = substr($decoded, $ivLen + $tagLen);

        $plainText = openssl_decrypt(
            $cipherText,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return $plainText !== false ? $plainText : null;
    }
}
