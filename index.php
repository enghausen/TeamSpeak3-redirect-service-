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
