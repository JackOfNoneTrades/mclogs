# Admin Dashboard Documentation

## Overview

The admin dashboard provides a simple web interface to manage logs stored in your mclogs instance. It allows you to:
- View all stored logs
- Delete individual logs
- Monitor storage statistics

## Setup

### 1. Configure Admin Token

Add an `ADMIN_TOKEN` to your `.env` file:

```bash
ADMIN_TOKEN=your-secure-random-token-here
```

**Important:** Use a strong, random token for security. You can generate one using:

```bash
openssl rand -base64 32
```

### 2. Access the Dashboard

Navigate to `/admin` on your web interface (not the API):

```
https://yourdomain.com/admin
```

### 3. Login

Enter your admin token from the `.env` file to authenticate.

## Features

### Dashboard Overview
- **Total Logs**: Shows the total number of logs in storage
- **Storage Backend**: Displays which storage backend is in use (MongoDB, Redis, or Filesystem)

### Log Management
- **List Logs**: View all logs with their ID, creation date, and size
- **View Log**: Click "View" to open a log in a new tab
- **Delete Log**: Click "Delete" to remove a log (with confirmation)
- **Refresh**: Manually refresh the log list

## API Endpoints

The dashboard uses the following API endpoints (protected by admin token):

### List Logs
```
GET /admin/logs
Authorization: Bearer YOUR_ADMIN_TOKEN
```

Returns a JSON array of all logs with metadata.

### Delete Log
```
POST /admin/delete/{logId}
Authorization: Bearer YOUR_ADMIN_TOKEN
```

Deletes the specified log.

## Storage Support

### Filesystem Storage (`STORAGE_ID=f`)
- ✅ Full support for listing and deleting logs
- Displays file creation date and size
- Located in `storage/logs/` directory

### Redis Storage (`STORAGE_ID=r`)
- ✅ Full support for listing and deleting logs
- Shows TTL (Time To Live) for each log
- Displays size in bytes

### MongoDB Storage (`STORAGE_ID=m`)
- ✅ Full support for listing and deleting logs
- Displays creation timestamp and size
- Default storage backend

## Security Notes

1. **Protect your admin token**: Never commit your `.env` file to version control
2. **Use HTTPS**: Always access the admin dashboard over HTTPS in production
3. **Session-based auth**: The dashboard uses PHP sessions after initial login
4. **Logout**: Always logout when done to clear the session

## Troubleshooting

### "Admin token not configured"
- Make sure `ADMIN_TOKEN` is set in your `.env` file
- Restart your Docker containers after modifying `.env`

### "Unauthorized"
- Verify you're using the correct admin token
- Clear your browser cookies/session and try again

### "MongoDB listing not implemented"
- Switch to filesystem or Redis storage by setting `STORAGE_ID` in `.env`:
  ```bash
  STORAGE_ID=f  # for filesystem
  # or
  STORAGE_ID=r  # for Redis
  ```

### Logs not appearing
- Ensure your storage backend is properly configured
- Check that logs are being created successfully
- Verify file permissions if using filesystem storage

## Example Configuration

Here's a complete example `.env` configuration:

```bash
# Basic Configuration
BASE_URL=https://mylogs.com
API_BASE_URL=https://api-mylogs.com

# Admin Dashboard
ADMIN_TOKEN=a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6

# Storage (use filesystem for admin dashboard compatibility)
STORAGE_ID=f
FS_PATH=/../storage/logs/
STORAGE_TIME=7776000  # 90 days in seconds

# Other settings...
```

## Development

To develop or extend the admin dashboard:

1. Frontend: `web/frontend/admin.php`
2. API Endpoints: `api/endpoints/admin.php`
3. Routing: 
   - Web: `web/public/index.php`
   - API: `api/public/index.php`

## Support

For issues or feature requests, please refer to the main project repository.
