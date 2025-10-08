<?php

namespace Storage;

interface StorageInterface
{
    /**
     * Put some data in the storage, returns the (new) id for the data
     *
     * @param string $data
     * @param bool $noResetTimer Don't reset expiry timer on access
     * @param int|null $expiryDays Custom expiration time in days
     * @return ?\Id ID or false
     */
    public static function Put(string $data, bool $noResetTimer = false, ?int $expiryDays = null): ?\Id;

    /**
     * Get some data from the storage by id
     *
     * @param \Id $id
     * @return ?string Data or null, e.g. if it doesn't exist
     */
    public static function Get(\Id $id): ?string;

    /**
     * Renew the data to reset the time to live
     *
     * @param \Id $id
     * @return bool Success
     */
    public static function Renew(\Id $id): bool;
}
