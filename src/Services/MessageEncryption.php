<?php

namespace SwellSystems\Conversa\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Config;

class MessageEncryption
{
    /**
     * The encryption key.
     *
     * @var string
     */
    protected $key;

    /**
     * Create a new encryption service instance.
     */
    public function __construct()
    {
        $this->key = Config::get('conversa.encryption.key') ?? Config::get('app.key');
    }

    /**
     * Encrypt a message.
     *
     * @param array $data Message data to encrypt
     * @return array Encrypted message data
     */
    public function encrypt(array $data): array
    {
        $encryptedData = [];

        // Always encrypt the content
        $encryptedData['content'] = $this->encryptValue($data['content']);

        // Encrypt metadata if present
        if (isset($data['metadata'])) {
            $encryptedData['metadata'] = $this->encryptValue($data['metadata']);
        }

        // Keep non-sensitive data unencrypted
        $encryptedData['type'] = $data['type'];
        $encryptedData['user_id'] = $data['user_id'];
        $encryptedData['workspace_id'] = $data['workspace_id'];
        $encryptedData['space_id'] = $data['space_id'] ?? null;
        $encryptedData['thread_id'] = $data['thread_id'] ?? null;
        $encryptedData['parent_id'] = $data['parent_id'] ?? null;

        // Add encryption marker
        $encryptedData['is_encrypted'] = true;

        return $encryptedData;
    }

    /**
     * Decrypt a message.
     *
     * @param array $data Encrypted message data
     * @return array Decrypted message data
     */
    public function decrypt(array $data): array
    {
        if (!($data['is_encrypted'] ?? false)) {
            return $data;
        }

        $decryptedData = $data;

        try {
            // Decrypt content
            $decryptedData['content'] = $this->decryptValue($data['content']);

            // Decrypt metadata if present
            if (isset($data['metadata'])) {
                $decryptedData['metadata'] = $this->decryptValue($data['metadata']);
            }
        } catch (DecryptException $e) {
            report($e);
            throw new DecryptException('Failed to decrypt message data.');
        }

        return $decryptedData;
    }

    /**
     * Encrypt a single value.
     *
     * @param mixed $value
     * @return string
     */
    protected function encryptValue($value): string
    {
        return Crypt::encryptString(
            is_string($value) ? $value : json_encode($value)
        );
    }

    /**
     * Decrypt a single value.
     *
     * @param string $value
     * @return mixed
     */
    protected function decryptValue(string $value): mixed
    {
        $decrypted = Crypt::decryptString($value);
        
        // Attempt to decode JSON if the decrypted value is a valid JSON string
        $decoded = json_decode($decrypted, true);
        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $decrypted;
    }

    /**
     * Rotate encryption key.
     *
     * @param string $newKey
     * @return bool
     */
    public function rotateKey(string $newKey): bool
    {
        try {
            // Store the old key
            $oldKey = $this->key;

            // Set the new key
            $this->key = $newKey;
            Config::set('conversa.encryption.key', $newKey);

            // Re-encrypt all messages with the new key
            // This should be done in a background job for large datasets
            // Here's a basic implementation
            $messages = \SwellSystems\Conversa\Models\ConversaMessage::whereNotNull('content')
                ->where('is_encrypted', true)
                ->cursor();

            foreach ($messages as $message) {
                // Decrypt with old key
                $this->key = $oldKey;
                $decrypted = $this->decrypt($message->toArray());

                // Encrypt with new key
                $this->key = $newKey;
                $encrypted = $this->encrypt($decrypted);

                // Update message
                $message->update($encrypted);
            }

            return true;
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Check if data is encrypted.
     *
     * @param array $data
     * @return bool
     */
    public function isEncrypted(array $data): bool
    {
        return $data['is_encrypted'] ?? false;
    }

    /**
     * Get the current encryption key.
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }
}
