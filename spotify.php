<?php
header('Content-Type: application/json');

$cacheFile = __DIR__ . '/cache/spotify.json';
if (file_exists($cacheFile) && time() - filemtime($cacheFile) < 15) {
    echo file_get_contents($cacheFile);
    exit;
}

foreach (file('/srv/molotov-site/var.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    [$key, $value] = explode('=', $line, 2);
    putenv(trim($key) . '=' . trim($value));
}

$clientId     = getenv('SPOTIFY_CLIENT_ID');
$clientSecret = getenv('SPOTIFY_CLIENT_SECRET');
$refreshToken = getenv('SPOTIFY_REFRESH_TOKEN');

// refresh access token
$ch = curl_init('https://accounts.spotify.com/api/token');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'grant_type'    => 'refresh_token',
        'refresh_token' => $refreshToken,
    ]),
    CURLOPT_HTTPHEADER => [
        'Authorization: Basic ' . base64_encode("$clientId:$clientSecret"),
    ],
]);
$tokenResp = json_decode(curl_exec($ch), true);

// TEMP DEBUG — remove once diagnosed
if (empty($tokenResp['access_token'])) {
    echo json_encode(['playing' => false, 'debug' => $tokenResp]);
    exit;
}

$output = json_encode(['playing' => false]);

if (!empty($tokenResp['access_token'])) {
    $ch = curl_init('https://api.spotify.com/v1/me/player/currently-playing');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $tokenResp['access_token']],
    ]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // TEMP DEBUG — remove once diagnosed
    if ($status !== 200) {
        echo json_encode(['playing' => false, 'debug_status' => $status, 'debug_body' => $body]);
        exit;
    }

    if ($status === 200) {
        $data = json_decode($body, true);
        if (!empty($data['is_playing']) && !empty($data['item'])) {
            $output = json_encode([
                'playing'  => true,
                'track'    => $data['item']['name'],
                'artist'   => implode(', ', array_column($data['item']['artists'], 'name')),
                'albumArt' => $data['item']['album']['images'][2]['url'] ?? $data['item']['album']['images'][0]['url'],
            ]);
        } else {
            // TEMP DEBUG — remove once diagnosed
            $output = json_encode(['playing' => false, 'debug_data' => $data]);
        }
    }
}

file_put_contents($cacheFile, $output);
echo $output;