<?php
// Check authentication
session_start();
$authenticated = isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;

if (!$authenticated && isset($_POST['admin_token'])) {
    $adminToken = getenv('ADMIN_TOKEN');
    if ($adminToken && $_POST['admin_token'] === $adminToken) {
        $_SESSION['admin_authenticated'] = true;
        $authenticated = true;
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /admin');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - mclo.gs</title>
    <link rel="stylesheet" href="/css/mclogs.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #1a1a1a;
            color: #e0e0e0;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }
        .header h1 {
            margin: 0;
            color: #3a87c7;
        }
        .logout-btn {
            background-color: #c73838;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }
        .logout-btn:hover {
            background-color: #a02020;
        }
        .login-form {
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            background-color: #2a2a2a;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }
        .login-form h2 {
            margin-top: 0;
            color: #3a87c7;
            text-align: center;
        }
        .login-form input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #444;
            border-radius: 5px;
            background-color: #1a1a1a;
            color: #e0e0e0;
            box-sizing: border-box;
        }
        .login-form button {
            width: 100%;
            padding: 12px;
            background-color: #3a87c7;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .login-form button:hover {
            background-color: #2a6aa7;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background-color: #2a2a2a;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #999;
            font-size: 14px;
            text-transform: uppercase;
        }
        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
            color: #3a87c7;
        }
        .logs-section {
            background-color: #2a2a2a;
            padding: 20px;
            border-radius: 10px;
        }
        .logs-section h2 {
            margin-top: 0;
            color: #3a87c7;
        }
        .controls {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .refresh-btn {
            background-color: #3a87c7;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .refresh-btn:hover {
            background-color: #2a6aa7;
        }
        .logs-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .logs-table th {
            background-color: #333;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #444;
        }
        .logs-table td {
            padding: 12px;
            border-bottom: 1px solid #333;
        }
        .logs-table tr:hover {
            background-color: #333;
        }
        .delete-btn {
            background-color: #c73838;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }
        .delete-btn:hover {
            background-color: #a02020;
        }
        .view-btn {
            background-color: #3a87c7;
            color: white;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 3px;
            font-size: 12px;
            display: inline-block;
        }
        .view-btn:hover {
            background-color: #2a6aa7;
        }
        .loading {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        .error {
            background-color: #c73838;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php if (!$authenticated): ?>
        <div class="login-form">
            <h2>Admin Login</h2>
            <?php if (isset($_POST['admin_token']) && !$authenticated): ?>
                <div class="error">Invalid admin token</div>
            <?php endif; ?>
            <form method="POST">
                <input type="password" name="admin_token" placeholder="Admin Token" required>
                <button type="submit">Login</button>
            </form>
        </div>
    <?php else: ?>
        <div class="header">
            <h1>Admin Dashboard</h1>
            <a href="/admin?logout=1" class="logout-btn">Logout</a>
        </div>

        <div class="stats">
            <div class="stat-card">
                <h3>Total Logs</h3>
                <div class="value" id="total-logs">-</div>
            </div>
            <div class="stat-card">
                <h3>Storage Backend</h3>
                <div class="value" id="storage-type" style="font-size: 24px;">-</div>
            </div>
        </div>

        <div class="logs-section">
            <h2>Logs Management</h2>
            <div class="controls">
                <button class="refresh-btn" onclick="loadLogs()">Refresh</button>
                <span id="status" style="color: #999;"></span>
            </div>
            <div id="logs-container">
                <div class="loading">Loading logs...</div>
            </div>
        </div>

        <script>
            const apiBaseUrl = '<?php echo $config["apiBaseUrl"]; ?>';

            async function loadLogs() {
                document.getElementById('status').textContent = 'Loading...';
                try {
                    const response = await fetch(apiBaseUrl + '/admin/logs');
                    const data = await response.json();
                    
                    if (data.success) {
                        document.getElementById('total-logs').textContent = data.logs.length;
                        document.getElementById('storage-type').textContent = data.storage_type;
                        displayLogs(data.logs);
                        document.getElementById('status').textContent = 'Last updated: ' + new Date().toLocaleTimeString();
                    } else {
                        document.getElementById('logs-container').innerHTML = 
                            '<div class="error">' + (data.error || 'Failed to load logs') + '</div>';
                    }
                } catch (error) {
                    document.getElementById('logs-container').innerHTML = 
                        '<div class="error">Error loading logs: ' + error.message + '</div>';
                }
            }

            function displayLogs(logs) {
                if (logs.length === 0) {
                    document.getElementById('logs-container').innerHTML = 
                        '<p style="text-align: center; color: #999; padding: 40px;">No logs found</p>';
                    return;
                }

                let html = '<table class="logs-table"><thead><tr>';
                html += '<th>ID</th><th>Created</th><th>Size</th><th>Actions</th>';
                html += '</tr></thead><tbody>';

                logs.forEach(log => {
                    html += '<tr>';
                    html += '<td><code>' + log.id + '</code></td>';
                    html += '<td>' + (log.created || 'N/A') + '</td>';
                    html += '<td>' + formatBytes(log.size || 0) + '</td>';
                    html += '<td>';
                    html += '<a href="/' + log.id + '" target="_blank" class="view-btn">View</a> ';
                    html += '<button class="delete-btn" onclick="deleteLog(\'' + log.id + '\')">Delete</button>';
                    html += '</td>';
                    html += '</tr>';
                });

                html += '</tbody></table>';
                document.getElementById('logs-container').innerHTML = html;
            }

            async function deleteLog(logId) {
                if (!confirm('Are you sure you want to delete log ' + logId + '?')) {
                    return;
                }

                try {
                    const response = await fetch(apiBaseUrl + '/admin/delete/' + logId, {
                        method: 'POST'
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        loadLogs();
                    } else {
                        alert('Failed to delete log: ' + (data.error || 'Unknown error'));
                    }
                } catch (error) {
                    alert('Error deleting log: ' + error.message);
                }
            }

            function formatBytes(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
            }

            // Load logs on page load
            loadLogs();
        </script>
    <?php endif; ?>
</body>
</html>
