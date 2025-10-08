<?php

require_once("../../core/core.php");
require_once("../../core/config/urls.php");

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Handle admin logout FIRST, before any output
if ($uri === '/admin' && isset($_GET['logout'])) {
    session_start();
    $_SESSION = array();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 42000, '/');
    }
    session_destroy();
    header('Location: /admin');
    exit;
}

// Handle admin proxy routes
if (str_starts_with($uri, '/admin-proxy/')) {
    require_once("../endpoints/admin-proxy.php");
    exit;
}

switch ($uri) {
    case "/":
        require_once("../frontend/main.php");
        break;
    case "/admin":
        require_once("../frontend/admin.php");
        break;
    default:
        require_once("../frontend/logview.php");
        break;
}

?>

<script>
    window.MCLOGS_CONFIG = {
        apiBaseUrl: '<?php echo $config["apiBaseUrl"]; ?>'
    };
</script>
