<?php

require_once("../../core/core.php");
require_once("../../core/config/urls.php");

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

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
