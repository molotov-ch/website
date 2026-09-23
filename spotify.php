<?php
header('Content-Type: application/json');

$cacheFile = __DIR__ . '/cache/spotify.json';
if (file_exists($cacheFile) && time() - filemtime($cacheFile) < 15) {
    echo file_get_contents($cacheFile);
    exit;
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
curl_close($ch);

$output = json_encode(['playing' => false]);

if (!empty($tokenResp['access_token'])) {
    $ch = curl_init('https://api.spotify.com/v1/me/player/currently-playing');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $tokenResp['access_token']],
    ]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status === 200) {
        $data = json_decode($body, true);
        if (!empty($data['is_playing']) && !empty($data['item'])) {
            $output = json_encode([
                'playing'  => true,
                'track'    => $data['item']['name'],
                'artist'   => implode(', ', array_column($data['item']['artists'], 'name')),
                'albumArt' => $data['item']['album']['images'][2]['url'] ?? $data['item']['album']['images'][0]['url'],
            ]);
        }
    }
}

if (!is_dir(__DIR__ . '/cache')) mkdir(__DIR__ . '/cache');
file_put_contents($cacheFile, $output);
echo $output;