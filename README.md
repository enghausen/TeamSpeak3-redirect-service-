# TeamSpeak3 Redirect Service (PHP + NGINX)

This service allows the creation of **clickable TeamSpeak connect links** for platforms like **Discord**, where clickable `ts3server://` links are required for quick connections. It is particularly useful for **game servers** or **TeamSpeak hosting providers** to make server access simpler.

---

## Features

- **TeamSpeak Connect Links**: Generates clickable links for connecting to TeamSpeak servers using `ts3server://`.
- **Default Port Support**: Defaults to port **9987** if not specified.
- **Optional Parameters**: Supports additional parameters such as nickname, password, and channel.
- **Error Handling**: Provides user-friendly error messages for invalid input.
- **HTTPS with Certbot**: Ensures secure connections using Let's Encrypt certificates.

---

## Requirements

- **Alma Linux 9.5** (or compatible).
- **PHP 8.4 or higher** with PHP-FPM enabled.
- **NGINX** web server.
- **Certbot** for SSL.

---

## Installation Steps

### 1. Install Required Packages

```bash
sudo dnf install -y epel-release
sudo dnf install -y https://rpms.remirepo.net/enterprise/remi-release-9.rpm
sudo dnf module reset php -y
sudo dnf module enable php:remi-8.4 -y
sudo dnf install -y php php-fpm php-json php-mbstring php-opcache nginx certbot python3-certbot-nginx
sudo dnf -y update
```

### 2. Configure NGINX

#### Option 1: Non-SSL Configuration (before Certbot)
```nginx
server {
    listen 80;
    server_name ts3.example.com;

    root /usr/share/nginx/teamspeak;
    index index.php;

    # Route everything to index.php
    location / {
        try_files $uri /index.php?$args;
    }

    # Handle PHP files
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php-fpm/www.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Suppress favicon.ico errors
    location = /favicon.ico {
        log_not_found off;
        access_log off;
    }
}
```

You can use this configuration to test the service before enabling HTTPS.

#### Option 2: SSL Configuration (after Certbot)
```nginx
server {
    server_name ts3.example.com;

    root /usr/share/nginx/teamspeak;
    index index.php;

    # Route everything to index.php
    location / {
        try_files $uri /index.php?$args; # Always fallback to index.php
    }

    # Handle PHP files
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php-fpm/www.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Suppress favicon.ico errors
    location = /favicon.ico {
        log_not_found off;
        access_log off;
    }

    listen 443 ssl; # managed by Certbot
    ssl_certificate /etc/letsencrypt/live/ts3.example.com/fullchain.pem; # managed by Certbot
    ssl_certificate_key /etc/letsencrypt/live/ts3.example.com/privkey.pem; # managed by Certbot
    include /etc/letsencrypt/options-ssl-nginx.conf; # managed by Certbot
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem; # managed by Certbot
}

server {
    if ($host = ts3.example.com) {
        return 301 https://$host$request_uri;
    } # managed by Certbot

    listen 80;
    server_name ts3.example.com;
    return 404; # managed by Certbot
}
```

### 3. Obtain SSL Certificate

```bash
sudo certbot --nginx -d ts3.example.com
```

### 4. Create PHP Script

```bash
sudo mkdir -p /usr/share/nginx/teamspeak
sudo nano /usr/share/nginx/teamspeak/index.php
```
Paste the following code:

```php
<?php
// Get the full request URI
$request_uri = $_SERVER['REQUEST_URI'];

// Remove the leading slash
$ts3_path = ltrim($request_uri, '/');

// Extract hostname, optional port, and optional parameters
if (preg_match('/^([^:\/?]+)(?::(\d+))?(?:\?(.*))?$/', $ts3_path, $matches)) {
    $host = $matches[1];            // Domain or IP
    $port = $matches[2] ?? '9987'; // Default port 9987 if missing
    $options = $matches[3] ?? '';   // Optional parameters

    // Construct the TS3 URL
    $ts3_url = "ts3server://" . $host;

    // Build query parameters
    $query_params = [];
    if (strpos($options, 'port=') === false) {
        $query_params[] = "port=" . $port; // Add port if not specified
    }

    if (!empty($options)) {
        $query_params[] = $options; // Add other options
    }

    // Append query string if parameters exist
    if (!empty($query_params)) {
        $ts3_url .= "?" . implode('&', $query_params);
    }

    // Redirect to the TS3 URL
    header("Location: " . $ts3_url);
    exit();
} else {
    // Invalid URL format
    http_response_code(400);
    echo "Error: Invalid request format. Use the following format:<br>";
    echo "<strong>https://yourdomain.com/hostname:port?options</strong><br>";
    echo "Examples:<br>";
    echo "https://ts3.example.com/ts3.hoster.com:9987?nickname=UserNickname&password=serverPassword<br>";
    echo "https://ts3.example.com/ts3.hoster.com:9987<br>";
    echo "https://ts3.example.com/ts3.hoster.com?port=9987<br>";
    echo "https://ts3.example.com/ts3.hoster.com?port=9987&nickname=UserNickname&password=serverPassword&channel=MyDefaultChannel&cid=channelID&channelpassword=defaultChannelPassword&token=TokenKey&addbookmark=MyBookMarkLabel";
    exit();
}
?>
```

### 5. Restart Services

```bash
sudo systemctl restart nginx php-fpm
```

---

## Usage

### Example Links:
- `https://ts3.example.com/teamspeak.datho.st:9987?password=test`
- `https://ts3.example.com/teamspeak.datho.st?port=9987&nickname=test`
- `https://ts3.example.com/teamspeak.datho.st`

### Supported Formats:
- **Default Port 9987** if not specified.
- **Optional Parameters**: Nickname, password, and channel options supported.

---

## License
This project is licensed under the MIT License - see the LICENSE file for details.

---

