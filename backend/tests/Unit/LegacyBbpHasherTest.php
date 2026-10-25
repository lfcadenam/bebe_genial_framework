<?php

namespace Tests\Unit;

use App\Hashing\LegacyBbpHasher;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LegacyBbpHasherTest extends TestCase
{
    private LegacyBbpHasher $hasher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hasher = new LegacyBbpHasher();
    }

    public function test_verifies_legacy_bbp_crypt_hash_correctly(): void
    {
        $rawPassword = 'MiPassword123*';
        $legacyHash = crypt($rawPassword, 'Bbp.');

        // Debe validar exitosamente contra el hash legacy
        $this->assertTrue($this->hasher->check($rawPassword, $legacyHash));
        $this->assertFalse($this->hasher->check('ClaveErronea', $legacyHash));

        // Debe marcar que necesita rehash a bcrypt
        $this->assertTrue($this->hasher->needsRehash($legacyHash));
    }

    public function test_verifies_modern_bcrypt_hash_correctly(): void
    {
        $rawPassword = 'MiPassword123*';
        $modernHash = $this->hasher->make($rawPassword);

        // Debe ser un hash bcrypt ($2y$)
        $this->assertStringStartsWith('$2y$', $modernHash);
        $this->assertTrue($this->hasher->check($rawPassword, $modernHash));
        $this->assertFalse($this->hasher->check('ClaveErronea', $modernHash));

        // Un hash moderno NO debe necesitar rehash
        $this->assertFalse($this->hasher->needsRehash($modernHash));
    }

    public function test_facade_uses_legacy_bbp_driver(): void
    {
        $rawPassword = 'PasswordTest2026!';
        $legacyHash = crypt($rawPassword, 'Bbp.');

        $this->assertTrue(Hash::check($rawPassword, $legacyHash));
        $this->assertTrue(Hash::needsRehash($legacyHash));
    }
}
