<?php

namespace Storage;

use Client\RedisClient;

class Redis extends RedisClient implements StorageInterface {

    /**
     * Put some data in the storage, returns the (new) id for the data
     *
     * @param string $data
     * @param bool $noResetTimer Don't reset expiry timer on access (not applicable for Redis)
     * @param int|null $expiryDays Custom expiration time in days
     * @return ?\Id ID or false
     */
    public static function Put(string $data, bool $noResetTimer = false, ?int $expiryDays = null): ?\Id
    {
        $config = \Config::Get("storage");
        $id = new \Id();
        $id->setStorage("r");

        do {
            $id->regenerate();
        } while (self::Get($id) !== null);

        // Calculate expiry time
        $expiryTime = $config['storageTime'];
        if ($expiryDays !== null && $expiryDays > 0) {
            $expiryTime = $expiryDays * 86400; // Convert days to seconds
        }

        \Client\RedisClient::Connect();
        \Client\RedisClient::$connection->setEx($id->getRaw(), $expiryTime, $data);
        
        // Store no_reset_timer flag if needed (as metadata key)
        if ($noResetTimer) {
            \Client\RedisClient::$connection->setEx($id->getRaw() . ':no_reset', $expiryTime, '1');
        }

        return $id;
    }

    /**
     * Get some data from the storage by id
     *
     * @param \Id $id
     * @return ?string Data or false, e.g. if it doesn't exist
     */
    public static function Get(\Id $id): ?string
    {
        self::Connect();

        return self::$connection->get($id->getRaw()) ?: null;
    }

    /**
     * Renew the data to reset the time to live
     *
     * @param \Id $id
     * @return bool Success
     */
    public static function Renew(\Id $id): bool
    {
        self::Connect();
        
        // Check if timer should be reset
        if (self::$connection->exists($id->getRaw() . ':no_reset')) {
            // Don't reset timer if no_reset flag exists
            return true;
        }
        
        $config = \Config::Get("storage");
        self::$connection->expire($id->getRaw(), $config['storageTime']);
        return true;
    }
}
