<?php

namespace Storage;


class Filesystem implements StorageInterface
{

    /**
     * Put some data in the storage, returns the (new) id for the data
     *
     * @param string $data
     * @param bool $noResetTimer Don't reset expiry timer on access
     * @param int|null $expiryDays Custom expiration time in days (not used for filesystem - uses file mtime)
     * @return ?\Id ID or false
     * @throws \Exception
     */
    public static function Put(string $data, bool $noResetTimer = false, ?int $expiryDays = null): ?\Id
    {
        $config = \Config::Get("filesystem");
        $basePath = CORE_PATH . $config['path'];

        if (!is_writable($basePath)) {
            throw new \Exception("Filesystem storage driver could not write to " . $basePath . ". Please check if the directory exists and is writable.");
        }

        $id = new \Id();
        $id->setStorage("f");

        do {
            $id->regenerate();
        } while (file_exists($basePath . $id->getRaw()));

        file_put_contents($basePath . $id->getRaw(), $data);
        
        // Store metadata about expiration options if needed
        if ($noResetTimer || $expiryDays !== null) {
            $metadata = [];
            if ($noResetTimer) {
                $metadata['no_reset_timer'] = true;
            }
            if ($expiryDays !== null && $expiryDays > 0) {
                $metadata['expiry_days'] = $expiryDays;
            }
            file_put_contents($basePath . $id->getRaw() . '.meta', json_encode($metadata));
        }
        
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
        $config = \Config::Get("filesystem");
        $basePath = CORE_PATH . $config['path'];

        if (!file_exists($basePath . $id->getRaw())) {
            return false;
        }

        return file_get_contents($basePath . $id->getRaw()) ?: null;
    }

    /**
     * Renew the data to reset the time to live
     *
     * @param \Id $id
     * @return bool Success
     */
    public static function Renew(\Id $id): bool
    {
        $config = \Config::Get("filesystem");
        $basePath = CORE_PATH . $config['path'];

        if (!file_exists($basePath . $id->getRaw())) {
            return false;
        }

        // Check if timer should be reset
        $metaFile = $basePath . $id->getRaw() . '.meta';
        if (file_exists($metaFile)) {
            $metadata = json_decode(file_get_contents($metaFile), true);
            if (isset($metadata['no_reset_timer']) && $metadata['no_reset_timer']) {
                // Don't reset timer
                return true;
            }
        }

        return touch($basePath . $id->getRaw());
    }
}
