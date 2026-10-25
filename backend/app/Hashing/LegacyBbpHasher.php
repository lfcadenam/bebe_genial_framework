<?php

namespace App\Hashing;

use Illuminate\Contracts\Hashing\Hasher;

class LegacyBbpHasher implements Hasher
{
    /**
     * Get information about the given hashed value.
     */
    public function info($hashedValue): array
    {
        return password_get_info($hashedValue);
    }

    /**
     * Hash the given value.
     * Nuevos hashes siempre se generan con bcrypt moderno.
     */
    public function make($value, array $options = []): string
    {
        $cost = $options['rounds'] ?? 12;
        return password_hash($value, PASSWORD_BCRYPT, ['cost' => $cost]);
    }

    /**
     * Check the given plain value against a hash.
     * Soporta:
     * 1. Bcrypt / Argon2 moderno.
     * 2. Hash legacy de Bebé Genial: crypt($pass, 'Bbp.')
     * 3. MD5 histórico.
     */
    public function check($value, $hashedValue, array $options = []): bool
    {
        if (empty($hashedValue)) {
            return false;
        }

        // 1. Verificación nativa (Bcrypt / Argon2)
        if (password_verify($value, $hashedValue)) {
            return true;
        }

        // 2. Hash procedural de Bebé Genial: crypt($pass, 'Bbp.')
        if (hash_equals(crypt($value, 'Bbp.'), $hashedValue)) {
            return true;
        }

        // 3. Fallback MD5 para registros muy antiguos
        if (hash_equals(md5($value), $hashedValue)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the given hash has been hashed using the given options.
     * Si no empieza con $2y$ (bcrypt), necesita rehash automático.
     */
    public function needsRehash($hashedValue, array $options = []): bool
    {
        return !str_starts_with((string)$hashedValue, '$2y$');
    }
}
