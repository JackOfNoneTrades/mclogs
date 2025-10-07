<?php

$config = [
    /**
     * A class that should be used to cache data
     * The class should implement \Cache\CacheInterface
     */

	 "cacheId" => getenv('CACHE_ID') ?: "\\Cache\\RedisCache"
];
