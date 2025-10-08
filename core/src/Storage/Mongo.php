<?php

namespace Storage;

use MongoDB\BSON\UTCDateTime;

class Mongo extends \Client\MongoDBClient implements StorageInterface
{
    protected const COLLECTION_NAME = "logs";

    /**
     * Put some data in the storage, returns the (new) id for the data
     *
     * @param string $data
     * @param bool $noResetTimer Don't reset expiry timer on access
     * @param int|null $expiryDays Custom expiration time in days
     * @param bool $encrypted Whether the log is encrypted
     * @return ?\Id ID or false
     */
    public static function Put(string $data, bool $noResetTimer = false, ?int $expiryDays = null, bool $encrypted = false): ?\Id
    {
        $config = \Config::Get("storage");
        $id = new \Id();
        $id->setStorage("m");

        do {
            $id->regenerate();
        } while (self::Get($id) !== null);

        // Calculate expiry time
        $expiryTime = $config['storageTime'];
        if ($expiryDays !== null && $expiryDays > 0) {
            $expiryTime = $expiryDays * 86400; // Convert days to seconds
        }
        
        $date = new UTCDateTime((time() + $expiryTime) * 1000);

        self::getCollection()->insertOne([
            "_id" => $id->getRaw(),
            "expires" => $date,
            "data" => $data,
            "no_reset_timer" => $noResetTimer,
            "encrypted" => $encrypted
        ]);

        return $id;
    }

    /**
     * Get some data from the storage by id
     *
     * @param \Id $id
     * @return ?string Data or null, e.g. if it doesn't exist
     */
    public static function Get(\Id $id): ?string
    {
        $result = self::getCollection()->findOne(["_id" => $id->getRaw()]);

        if ($result === null) {
            return null;
        }

        return $result->data;
    }

    /**
     * Renew the data to reset the time to live
     *
     * @param \Id $id
     * @return bool Success
     */
    public static function Renew(\Id $id): bool
    {
        // Check if timer should be reset
        $result = self::getCollection()->findOne(["_id" => $id->getRaw()], ['projection' => ['no_reset_timer' => 1]]);
        
        if ($result && isset($result->no_reset_timer) && $result->no_reset_timer) {
            // Don't reset timer if no_reset_timer flag is set
            return true;
        }
        
        $config = \Config::Get("storage");
        $date = new UTCDateTime((time() + $config['storageTime']) * 1000);

        self::getCollection()->updateOne(["_id" => $id->getRaw()], ['$set' => ['expires' => $date]]);

        return true;
    }
    
    /**
     * Check if a log is encrypted
     *
     * @param \Id $id
     * @return bool Whether the log is encrypted
     */
    public static function IsEncrypted(\Id $id): bool
    {
        $result = self::getCollection()->findOne(
            ["_id" => $id->getRaw()], 
            ['projection' => ['encrypted' => 1]]
        );
        
        return $result && isset($result->encrypted) && $result->encrypted;
    }
}
