<?php

namespace Custom;

use Aternos\Codex\Minecraft\Detective\Detective as BaseDetective;
use Aternos\Codex\Minecraft\Log\Minecraft\MinecraftLog;

/**
 * Custom Detective extending Codex Detective
 * Fixes detection issues for specific log types
 */
class CustomDetective extends BaseDetective
{
    /**
     * Detect the log type with custom logic
     * 
     * @return MinecraftLog
     */
    public function detect(): MinecraftLog
    {
        // First, run the base detection
        $log = parent::detect();
        
        // Apply custom fixes
        $log = $this->fixClientServerDetection($log);
        
        return $log;
    }
    
    /**
     * Fix client logs being detected as server logs
     * 
     * If the log contains "minecraft-*-client.jar", it's a client log
     * This works for any Minecraft version and any mod loader
     * 
     * @param \Aternos\Codex\Log\LogInterface $log
     * @return \Aternos\Codex\Log\LogInterface
     */
    protected function fixClientServerDetection(\Aternos\Codex\Log\LogInterface $log): \Aternos\Codex\Log\LogInterface
    {
        // Only process Minecraft logs
        if (!($log instanceof MinecraftLog)) {
            return $log;
        }
        
        // Get the log content
        $content = $this->logFile->getContent();
        
        // Check for client jar indicator (e.g., minecraft-1.7.10-client.jar, minecraft-1.20.1-client.jar)
        if (preg_match('/minecraft-[\d.]+-client\.jar/', $content)) {
            // This is definitely a client log
            // Try to convert to client version using Codex's getClientLog method if available
            if (method_exists($log, 'getClientLog')) {
                try {
                    $clientLog = $log->getClientLog();
                    if ($clientLog) {
                        $clientLog->setLogFile($this->logFile);
                        return $clientLog;
                    }
                } catch (\Exception $e) {
                    // If conversion fails, continue with original
                }
            }
            
            // Alternative: Check if the log class name contains "Server" and try to replace it with "Client"
            $logClass = get_class($log);
            if (strpos($logClass, 'Server') !== false) {
                $clientClass = str_replace('Server', 'Client', $logClass);
                if (class_exists($clientClass)) {
                    try {
                        $clientLog = new $clientClass();
                        $clientLog->setLogFile($this->logFile);
                        return $clientLog;
                    } catch (\Exception $e) {
                        // If we can't create the client log, return original
                    }
                }
            }
        }
        
        return $log;
    }
}
