<?php
// Check authentication
session_start();
$authenticated = isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;

if (!$authenticated) {
    header('Location: /admin');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Dashboard</title>
    <link rel="stylesheet" href="/css/mclogs.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
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
        .header-buttons {
            display: flex;
            gap: 10px;
        }
        .btn {
            background-color: #3a87c7;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #2a6aa7;
        }
        .btn-danger {
            background-color: #c73838;
        }
        .btn-danger:hover {
            background-color: #a02020;
        }
        .settings-section {
            background-color: #2a2a2a;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .settings-section h2 {
            margin-top: 0;
            color: #3a87c7;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #999;
            font-size: 14px;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            background-color: #1a1a1a;
            border: 1px solid #444;
            border-radius: 5px;
            color: #e0e0e0;
            box-sizing: border-box;
        }
        .form-group textarea {
            min-height: 80px;
            font-family: monospace;
        }
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 12px;
        }
        .save-btn {
            background-color: #3a87c7;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .save-btn:hover {
            background-color: #2a6aa7;
        }
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .message.success {
            background-color: #2d5016;
            color: #7bc043;
        }
        .message.error {
            background-color: #c73838;
            color: white;
        }
        .loading {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Settings</h1>
        <div class="header-buttons">
            <a href="/admin" class="btn">Back to Dashboard</a>
            <form method="POST" action="/admin" style="display: inline;">
                <input type="hidden" name="logout" value="1">
                <button type="submit" class="btn btn-danger">Logout</button>
            </form>
        </div>
    </div>

    <div id="message-container"></div>
    <div id="settings-container">
        <div class="loading">Loading settings...</div>
    </div>

    <script>
        async function loadSettings() {
            try {
                const response = await fetch('/admin-proxy/settings');
                const data = await response.json();
                
                if (data.success) {
                    displaySettings(data.settings);
                } else {
                    showMessage('Failed to load settings: ' + (data.error || 'Unknown error'), 'error');
                }
            } catch (error) {
                showMessage('Error loading settings: ' + error.message, 'error');
            }
        }

        function displaySettings(settings) {
            let html = '<form onsubmit="saveSettings(event)">';
            
            // Group settings by category
            const categories = {
                'Basic Configuration': ['BASE_URL', 'API_BASE_URL', 'ABUSE_EMAIL'],
                'Branding': ['IMPRINT', 'PRIVACY', 'GITHUB_URL', 'SERVICE_BY', 'SERVICE_BY_URL', 'FOOTER_LINE'],
                'Storage': ['STORAGE_ID', 'STORAGE_TIME', 'MAX_LENGTH', 'MAX_LINES', 'FS_PATH'],
                'Admin': ['MAX_LOGS_PAGE'],
                'Advanced': ['CACHE_ID', 'PRE_FILTERS', 'ID_CHARACTERS', 'ID_LENGTH', 'MONGO_URL']
            };
            
            for (const [category, keys] of Object.entries(categories)) {
                const categorySettings = keys.filter(key => settings[key] !== undefined);
                if (categorySettings.length === 0) continue;
                
                html += '<div class="settings-section">';
                html += '<h2>' + category + '</h2>';
                
                categorySettings.forEach(key => {
                    const value = settings[key];
                    html += '<div class="form-group">';
                    html += '<label for="' + key + '">' + key + '</label>';
                    
                    // Use textarea for long values
                    if (key.includes('FILTER') || key.includes('FOOTER') || value.length > 100) {
                        html += '<textarea id="' + key + '" name="' + key + '">' + escapeHtml(value) + '</textarea>';
                    } else {
                        html += '<input type="text" id="' + key + '" name="' + key + '" value="' + escapeHtml(value) + '">';
                    }
                    
                    html += '<small>' + getFieldDescription(key) + '</small>';
                    html += '</div>';
                });
                
                html += '</div>';
            }
            
            html += '<button type="submit" class="save-btn">Save Settings</button>';
            html += '</form>';
            
            document.getElementById('settings-container').innerHTML = html;
        }

        function getFieldDescription(key) {
            const descriptions = {
                'BASE_URL': 'The base URL of your web interface',
                'API_BASE_URL': 'The base URL of your API',
                'ABUSE_EMAIL': 'Email address for abuse reports',
                'IMPRINT': 'Imprint/Legal information text',
                'PRIVACY': 'Privacy policy URL or text',
                'GITHUB_URL': 'GitHub repository URL',
                'SERVICE_BY': 'Your organization name',
                'SERVICE_BY_URL': 'Your organization URL',
                'FOOTER_LINE': 'Custom footer HTML',
                'STORAGE_ID': 'Storage backend: m (MongoDB), r (Redis), f (Filesystem)',
                'STORAGE_TIME': 'How long to store logs (in seconds)',
                'MAX_LENGTH': 'Maximum log file size (in bytes)',
                'MAX_LINES': 'Maximum number of lines per log',
                'MAX_LOGS_PAGE': 'Number of logs per page in admin panel',
                'FS_PATH': 'Filesystem storage path (relative to core)',
                'CACHE_ID': 'Cache driver class name',
                'PRE_FILTERS': 'Comma-separated list of pre-filter classes',
                'ID_CHARACTERS': 'Characters allowed in log IDs',
                'ID_LENGTH': 'Length of generated log IDs',
                'MONGO_URL': 'MongoDB connection URL'
            };
            return descriptions[key] || '';
        }

        async function saveSettings(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const settings = {};
            for (const [key, value] of formData.entries()) {
                settings[key] = value;
            }
            
            try {
                const response = await fetch('/admin-proxy/settings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(settings)
                });
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Settings saved successfully! Changes will take effect on next restart.', 'success');
                } else {
                    showMessage('Failed to save settings: ' + (data.error || 'Unknown error'), 'error');
                }
            } catch (error) {
                showMessage('Error saving settings: ' + error.message, 'error');
            }
        }

        function showMessage(text, type) {
            const container = document.getElementById('message-container');
            container.innerHTML = '<div class="message ' + type + '">' + escapeHtml(text) + '</div>';
            setTimeout(() => {
                container.innerHTML = '';
            }, 5000);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Load settings on page load
        loadSettings();
    </script>
</body>
</html>
