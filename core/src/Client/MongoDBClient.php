<?php

namespace Client;

use MongoDB\Client;
use MongoDB\Collection;

class MongoDBClient
{
    /**
     * MongoDB Collection name
     */
    protected const COLLECTION_NAME = "logs";

    /**
     * @var null|Client
     */
    protected static ?Client $connection = null;

    /**
     * Connect to MongoDB
     */
    protected static function Connect()
    {
        if (self::$connection === null) {
            $config = \Config::Get("mongo");
            self::$connection = new Client($config['url'] ?? 'mongodb://127.0.0.1/');
            
            // Ensure TTL index exists for automatic log expiration
            static::ensureTTLIndex();
        }
    }
    
    /**
     * Ensure TTL index exists on the logs collection
     */
    protected static function ensureTTLIndex()
    {
        try {
            $collection = self::$connection->mclogs->{static::COLLECTION_NAME};
            
            // Check if TTL index already exists
            $indexes = $collection->listIndexes();
            $hasTTLIndex = false;
            
            foreach ($indexes as $index) {
                if (isset($index['key']['expires']) && isset($index['expireAfterSeconds'])) {
                    $hasTTLIndex = true;
                    break;
                }
            }
            
            // Create TTL index if it doesn't exist
            if (!$hasTTLIndex) {
                $collection->createIndex(
                    ['expires' => 1],
                    ['expireAfterSeconds' => 0]
                );
            }
        } catch (\Exception $e) {
            // Silently fail - index creation is not critical for basic operations
            error_log("Failed to create MongoDB TTL index: " . $e->getMessage());
        }
    }

    /**
     * get the collection specified by {{@link COLLECTION_NAME}}
     * @return Collection
     */
    protected static function getCollection(): Collection
    {
        static::Connect();
        return self::$connection->mclogs->{static::COLLECTION_NAME};
    }
}
