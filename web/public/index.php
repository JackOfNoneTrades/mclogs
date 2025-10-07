<?php

require_once("../../core/core.php");
require_once("../../core/config/urls.php");

switch ($_SERVER['REQUEST_URI']) {
    case "/":
        require_once("../frontend/main.php");
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
