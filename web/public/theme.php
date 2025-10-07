<?php
// web/public/css/theme.php


header("Content-Type: text/css");

$primary = $_ENV['PRIMARY_COLOR'] ?? '#2d3943';
$secondary = $_ENV['SECONDARY_COLOR'] ?? '#f0f0f0';
$tertiary = $_ENV['TERTIARY_COLOR'] ?? '#2d87d3';
$color4 = $_ENV['COLOR_4'] ?? '#1FD78D';
$color5 = $_ENV['COLOR_5'] ?? '';
$color6 = $_ENV['COLOR_6'] ?? '';
?>

:root {
    --primary-color: <?= htmlspecialchars($primary) ?>;
    --secondary-color: <?= htmlspecialchars($secondary) ?>;
    --tertiary-color: <?= htmlspecialchars($tertiary) ?>;
    --color-4: <?= htmlspecialchars($color4) ?>;
    --color-5: <?= htmlspecialchars($color5) ?>;
    --color-6: <?= htmlspecialchars($color6) ?>;
}

.btn {
    background: var(--primary-color);
    color: #fff;
}

body {
    background-color: var(--primary-color);
}

