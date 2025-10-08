<?php
$urls = Config::Get('urls');
$legal = Config::Get('legal');
$id = new Id(substr($_SERVER['REQUEST_URI'], 1));
$log = new Log($id);
$shouldWrapLogLines = filter_var($_COOKIE["WRAP_LOG_LINES"] ?? "true", FILTER_VALIDATE_BOOLEAN);

// Check if log is encrypted
$isEncrypted = false;
if ($log->exists()) {
    $config = Config::Get('storage');
    $storageId = $config['storageId'];
    $storage = $config['storages'][$storageId]['class'];
    $isEncrypted = $storage::IsEncrypted($id);
}

$title = "mclo.gs - Paste, share & analyse your Minecraft logs";
$description = "Easily paste your Minecraft logs to share and analyse them.";
if (!$log->exists()) {
    $title = "Log not found - mclo.gs";
    http_response_code(404);
} else if (!$isEncrypted) {
    $codexLog = $log->get();
    $analysis = $log->getAnalysis();
    $information = $analysis->getInformation();
    $problems = $analysis->getProblems();
    $title = $codexLog->getTitle() . " [#" . $id->get() . "]";
    $lineNumbers = $log->getLineNumbers();
    $lineString = $lineNumbers === 1 ? "line" : "lines";

    $errorCount = $log->getErrorCount();
    $errorString = $errorCount === 1 ? "error" : "errors";

    $description = $lineNumbers . " " . $lineString;
    if ($errorCount > 0) {
       $description .= " | " . $errorCount . " " . $errorString;
    }

    if (count($problems) > 0) {
        $problemString = "problems";
        if (count($problems) === 1) {
            $problemString = "problem";
        }
        $description .= " | " . count($problems) . " " . $problemString . " automatically detected";
    }
}
?><!DOCTYPE html>
<html lang="en">
    <head>
        <meta name="robots" content="noindex,nofollow">
	<meta charset="utf-8" />

	<?php
$themeColor = $_ENV['PRIMARY_COLOR'] ?? '#2d3943';
?>
	<meta name="theme-color" content="<?= htmlspecialchars($themeColor) ?>" />

        <title><?=$title; ?> - mclo.gs</title>

        <base href="/" />

	<link rel="stylesheet" href="/theme.php">	
<link rel="stylesheet" href="vendor/fonts/fonts.css" />
        <link rel="stylesheet" href="vendor/fontawesome/css/all.min.css" />
        <link rel="stylesheet" href="css/btn.css" />
        <link rel="stylesheet" href="css/mclogs.css?v=071224" />
        <link rel="stylesheet" href="css/log.css?v=071222" />

        <link rel="shortcut icon" href="img/favicon.ico" type="image/x-icon" />

        <meta name="description" content="<?=$description; ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />

        <meta property="og:type" content="website" />
        <meta property="og:site_name" content="mclo.gs" />
        <meta property="og:title" content="<?=$title; ?>" />
        <meta property="og:description" content="<?=$description; ?>" />
        <meta property="og:url" content="<?=$urls['baseUrl'] . "/" . $id->get(); ?>" />

        <script>
            let _paq = window._paq = window._paq || [];
            _paq.push(['disableCookies']);
            _paq.push(['trackPageView']);
            _paq.push(['enableLinkTracking']);
            (function() {
                _paq.push(['setTrackerUrl', '/data']);
                _paq.push(['setSiteId', '5']);
                let d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
                g.async=true; g.src='/data.js'; s.parentNode.insertBefore(g,s);
            })();
        </script>
    </head>
    <body class="log-body">
        <header class="row navigation">
            <div class="row-inner">
                <a href="/" class="logo">
                    <img src="branding/logo.png" />
                </a>
                <div class="menu">
                    <a class="menu-item" href="/#info">
                        <i class="fa fa-info-circle"></i> Info
                    </a>
                    <a class="menu-item" href="/#plugin">
                        <i class="fa fa-database"></i> Plugin
                    </a>
                    <a class="menu-item" href="/#mod">
                        <i class="fa fa-puzzle-piece"></i> Mod
                    </a>
                    <a class="menu-item" href="/#api">
                        <i class="fa fa-code"></i> API
                    </a>
                    <a class="menu-social btn btn-black btn-notext btn-large btn-no-margin" href="<?= getenv('GITHUB_URL') ?>" target="_blank">
                        <i class="fab fa-github"></i>
                    </a>
                </div>
            </div>
        </header>
        <div class="row dark log-row">
            <div class="row-inner<?= $shouldWrapLogLines ? "" : " no-wrap"?>">
                <?php if($log->exists()): ?>
                <?php if ($isEncrypted): ?>
                <!-- Encrypted log - show password prompt -->
                <div class="log-info">
                    <div class="log-title">
                        <h1><i class="fas fa-lock"></i> Password Protected Log</h1>
                        <div class="log-id">#<?=$id->get(); ?></div>
                    </div>
                </div>
                <div class="password-prompt" style="background: #2a2a2a; padding: 40px; border-radius: 10px; text-align: center; margin: 40px 0;">
                    <h2 style="color: #3a87c7; margin-bottom: 20px;"><i class="fa fa-lock"></i> This log is password protected</h2>
                    <p style="color: #999; margin-bottom: 30px;">Enter the password to decrypt and view this log.</p>
                    <input type="password" id="decrypt-password" placeholder="Enter password" style="padding: 12px; width: 300px; background: #1a1a1a; border: 1px solid #444; border-radius: 5px; color: #e0e0e0; margin-right: 10px;">
                    <button onclick="decryptAndDisplay()" class="btn btn-blue" style="padding: 12px 24px;">Decrypt Log</button>
                    <div id="decrypt-error" style="color: #c73838; margin-top: 15px; display: none;"></div>
                    <div id="decrypt-loading" style="color: #3a87c7; margin-top: 15px; display: none;"><i class="fa fa-spinner fa-spin"></i> Decrypting...</div>
                </div>
                <textarea id="encrypted-data" style="display:none;"><?php 
                    $storage = $config['storages'][$storageId]['class'];
                    echo htmlspecialchars($storage::Get($id)); 
                ?></textarea>
                <div id="decrypted-content" style="display:none;"></div>
                <?php else: ?>
                <!-- Normal unencrypted log -->
                <div class="log-info">
                    <div class="log-title">
                        <h1><i class="fas fa-file-lines"></i> <?=$codexLog->getTitle(); ?></h1>
                        <div class="log-id">#<?=$id->get(); ?></div>
                    </div>
                    <div class="log-info-actions">
                        <?php if($errorCount): ?>
                        <div class="btn btn-red btn-small btn-no-margin" id="error-toggle">
                            <i class="fa fa-exclamation-circle"></i>
                            <?=$errorCount . " " . $errorString; ?>
                        </div>
                        <?php endif; ?>
                        <div class="btn btn-blue btn-small btn-no-margin" id="down-button">
                            <i class="fa fa-arrow-circle-down"></i>
                            <?=$lineNumbers . " " . $lineString; ?>
                        </div>
                        <a class="btn btn-white btn-small btn-no-margin" id="raw" target="_blank" href="<?=$urls['apiBaseUrl'] . "/1/raw/". $id->get()?>">
                            <i class="fa fa-arrow-up-right-from-square"></i>
                            Raw
                        </a>
                    </div>
                </div>
                <?php if(count($analysis) > 0): ?>
                    <div class="analysis">
                        <div class="analysis-headline"><i class="fa fa-info-circle"></i> Analysis</div>
                        <?php if(count($information) > 0): ?>
                            <div class="information-list">
                                <?php foreach($information as $info): ?>
                                    <div class="information">
                                        <div class="information-label">
                                            <?=$info->getLabel(); ?>:
                                        </div>
                                        <div class="information-value">
                                            <?=$info->getValue(); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if(count($problems) > 0): ?>
                            <div class="problem-list">
                                <?php foreach($problems as $problem): ?>
                                    <div class="problem">
                                        <div class="problem">
                                            <div class="problem-header">
                                                <div class="problem-message">
                                                    <i class="fa fa-exclamation-triangle"></i> <?=htmlspecialchars($problem->getMessage()); ?>
                                                </div>
                                                <?php $number = $problem->getEntry()[0]->getNumber(); ?>
                                                <a href="/<?=$id->get() . "#L" . $number; ?>" class="btn btn-blue btn-no-margin btn-small" onclick="updateLineNumber('#L<?=$number; ?>');">
                                                    <span class="hide-mobile"><i class="fa fa-arrow-right"></i> Line </span>#<?=$number; ?>
                                                </a>
                                            </div>
                                            <div class="problem-body">
                                                <div class="problem-solution-headline">
                                                    Solutions
                                                </div>
                                                <div class="problem-solution-list">
                                                    <?php foreach($problem->getSolutions() as $solution): ?>
                                                        <div class="problem-solution">
                                                            <?=preg_replace("/'([^']+)'/", "'<strong>$1</strong>'", htmlspecialchars($solution->getMessage())); ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="log">
                    <?php
                        $log->renew();
                        echo $log->getPrinter()->print();
                    ?>
                </div>
                <div class="log-bottom">
                    <div class="btn btn-blue btn-small btn-notext" id="up-button">
                        <i class="fa fa-arrow-circle-up"></i>
                    </div>
                    <div class="checkbox-container">
                        <input type="checkbox" id="wrap-checkbox"<?=$shouldWrapLogLines ? " checked" : ""?>/>
                        <label for="wrap-checkbox">Wrap log lines</label>
                    </div>
                </div>
                <div class="log-notice">
                    <?php
                    // Get expiration information
                    $storageConfig = Config::Get('storage');
                    $defaultDays = floor($storageConfig['storageTime'] / 86400);
                    $expiryDays = $defaultDays;
                    $resetsOnView = true;
                    
                    // Check storage backend for expiration metadata
                    if ($id->getStorage() == 'm') {
                        // MongoDB
                        try {
                            $mongoClass = new ReflectionClass('\Storage\Mongo');
                            $collectionMethod = $mongoClass->getMethod('getCollection');
                            $collectionMethod->setAccessible(true);
                            $collection = $collectionMethod->invoke(null);
                            
                            $doc = $collection->findOne(['_id' => $id->getRaw()], ['projection' => ['no_reset_timer' => 1, 'expires' => 1]]);
                            if ($doc) {
                                $resetsOnView = !isset($doc->no_reset_timer) || !$doc->no_reset_timer;
                                // Calculate remaining days until expiration
                                if (isset($doc->expires)) {
                                    $expiresAt = $doc->expires->toDateTime()->getTimestamp();
                                    $now = time();
                                    $remainingTime = $expiresAt - $now;
                                    $expiryDays = max(0, floor($remainingTime / 86400));
                                }
                            }
                        } catch (Exception $e) {
                            // Use defaults
                        }
                    } elseif ($id->getStorage() == 'r') {
                        // Redis
                        try {
                            \Client\RedisClient::Connect();
                            $redis = \Client\RedisClient::$connection;
                            $resetsOnView = !$redis->exists($id->getRaw() . ':no_reset');
                            // Get TTL for current expiry
                            $ttl = $redis->ttl($id->getRaw());
                            if ($ttl > 0) {
                                $expiryDays = floor($ttl / 86400);
                            }
                        } catch (Exception $e) {
                            // Use defaults
                        }
                    } elseif ($id->getStorage() == 'f') {
                        // Filesystem
                        $fsConfig = Config::Get('filesystem');
                        $basePath = CORE_PATH . $fsConfig['path'];
                        $metaFile = $basePath . $id->getRaw() . '.meta';
                        
                        if (file_exists($metaFile)) {
                            $metadata = json_decode(file_get_contents($metaFile), true);
                            if (isset($metadata['no_reset_timer'])) {
                                $resetsOnView = !$metadata['no_reset_timer'];
                            }
                            if (isset($metadata['expiry_days'])) {
                                $expiryDays = $metadata['expiry_days'];
                            }
                        }
                    }
                    
                    $message = "This log will be saved for " . $expiryDays . " " . ($expiryDays == 1 ? "day" : "days");
                    if ($resetsOnView) {
                        $message .= " from their last view";
                    }
                    $message .= ".";
                    
                    echo $message;
                    ?><br />
                    <a href="mailto:<?=$legal['abuseEmail']?>?subject=Report%20mclo.gs/<?=$id->get(); ?>">Report abuse</a>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div class="not-found">
                    <div class="not-found-title">404 - Log not found.</div>
                    <div class="not-found-text">The log you try to open does not exist (anymore).<br />We automatically delete all logs that weren't opened in the last 90 days.</div>
                    <div class="not-found-buttons">
                        <a href="/" class="btn btn-no-margin btn-blue btn-small">
                            <i class="fa fa-home"></i> Paste a new log
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="row footer">
            <div class="row-inner">
                <?= getenv('FOOTER_LINE') ?>" |
                <a target="_blank" href="<?=$legal['imprint']?>">Imprint</a> |
                <a target="_blank" href="<?=$legal['privacy']?>">Privacy</a>
            </div>
        </div>
        <script src="js/viewer.js"></script>
        <script>
        // Decryption function for password-protected logs
        async function decryptLog(encryptedBase64, password) {
            try {
                // Decode base64 
                const binaryString = atob(encryptedBase64);
                const len = binaryString.length;
                const bytes = new Uint8Array(len);
                for (let i = 0; i < len; i++) {
                    bytes[i] = binaryString.charCodeAt(i);
                }
                
                // Extract components (salt: 16 bytes, IV: 12 bytes, rest: encrypted data)
                const salt = bytes.slice(0, 16);
                const iv = bytes.slice(16, 28);
                const data = bytes.slice(28);
                
                // Derive key from password
                const encoder = new TextEncoder();
                const keyMaterial = await crypto.subtle.importKey(
                    'raw',
                    encoder.encode(password),
                    'PBKDF2',
                    false,
                    ['deriveKey']
                );
                
                const key = await crypto.subtle.deriveKey(
                    {
                        name: 'PBKDF2',
                        salt: salt,
                        iterations: 100000,
                        hash: 'SHA-256'
                    },
                    keyMaterial,
                    { name: 'AES-GCM', length: 256 },
                    false,
                    ['decrypt']
                );
                
                // Decrypt
                const decrypted = await crypto.subtle.decrypt(
                    { name: 'AES-GCM', iv: iv },
                    key,
                    data
                );
                
                return new TextDecoder().decode(decrypted);
            } catch (e) {
                throw new Error('Decryption failed - incorrect password or corrupted data');
            }
        }

        async function decryptAndDisplay() {
            const password = document.getElementById('decrypt-password').value;
            const errorDiv = document.getElementById('decrypt-error');
            const loadingDiv = document.getElementById('decrypt-loading');
            
            if (!password) {
                errorDiv.textContent = 'Please enter a password';
                errorDiv.style.display = 'block';
                return;
            }
            
            // Show loading
            errorDiv.style.display = 'none';
            loadingDiv.style.display = 'block';
            
            try {
                const encryptedData = document.getElementById('encrypted-data').value;
                const decrypted = await decryptLog(encryptedData, password);
                
                // Display decrypted content as plain text
                const decryptedDiv = document.getElementById('decrypted-content');
                decryptedDiv.innerHTML = '<pre style="background: #2a2a2a; padding: 20px; border-radius: 5px; color: #e0e0e0; overflow-x: auto; white-space: pre-wrap; word-wrap: break-word;">' + 
                    decrypted.replace(/</g, '&lt;').replace(/>/g, '&gt;') + 
                    '</pre>';
                
                // Hide password prompt, show decrypted content
                document.querySelector('.password-prompt').style.display = 'none';
                decryptedDiv.style.display = 'block';
                loadingDiv.style.display = 'none';
                
            } catch (e) {
                loadingDiv.style.display = 'none';
                errorDiv.textContent = e.message;
                errorDiv.style.display = 'block';
            }
        }
        
        // Allow Enter key to decrypt
        const passwordInput = document.getElementById('decrypt-password');
        if (passwordInput) {
            passwordInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    decryptAndDisplay();
                }
            });
        }
        </script>
    </body>
</html>
