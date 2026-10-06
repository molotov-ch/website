<?php
const DATA_DIR = __DIR__ . '/../data';
const DOWNLOADS_DIR = DATA_DIR . '/downloads';

function parseJsonTitles(string $filePath, string $postsKey = 'posts'): array
{
  $jsonData = file_get_contents($filePath);
  $data = json_decode($jsonData, true);

  if (isset($data[$postsKey]) && is_array($data[$postsKey])) {
    $posts = $data[$postsKey];
  } elseif (is_array($data) && array_values($data) === $data) {
    $posts = $data;
  } else {
    return [];
  }

  $filtered = [];
  foreach ($posts as $index => $item) {
    if (isset($item['title'], $item['timestamp'])) {
      $filtered[] = [
        'index' => $index,
        'title' => $item['title'],
        'timestamp' => $item['timestamp'],
        'genre' => $item['genre'] ?? null,
        'summary' => $item['summary'] ?? null,
        'file' => $item['file'] ?? $item['link'] ?? null,
      ];
    }
  }

  usort($filtered, function ($a, $b) {
    $format = 'd/m/Y His';
    $dateA = DateTime::createFromFormat($format, $a['timestamp']);
    $dateB = DateTime::createFromFormat($format, $b['timestamp']);
    return $dateB <=> $dateA;
  });

  return $filtered;
}

// Resolves a filename from articles.json to a real path inside data/downloads.
// Returns null if the file is missing or tries to escape the directory.
function resolveDownload(?string $name): ?string
{
  if (!$name) {
    return null;
  }
  $base = realpath(DOWNLOADS_DIR);
  $path = realpath(DOWNLOADS_DIR . '/' . $name);
  if ($base === false || $path === false) {
    return null;
  }
  if (strncmp($path, $base . DIRECTORY_SEPARATOR, strlen($base) + 1) !== 0) {
    return null;
  }
  return is_file($path) ? $path : null;
}

$dataFile = DATA_DIR . '/articles.json';
$archivePosts = parseJsonTitles($dataFile);

// Serve a file: articles.php?download=<index>
if (isset($_GET['download'])) {
  $path = null;
  foreach ($archivePosts as $post) {
    if ((string) $post['index'] === (string) $_GET['download']) {
      $path = resolveDownload($post['file']);
      break;
    }
  }
  if ($path === null) {
    http_response_code(404);
    exit('File not found');
  }
  $mime = mime_content_type($path) ?: 'application/octet-stream';
  header('Content-Type: ' . $mime);
  header('Content-Length: ' . filesize($path));
  header('Content-Disposition: inline; filename="' . addslashes(basename($path)) . '"');
  header('X-Content-Type-Options: nosniff');
  readfile($path);
  exit;
}
?>


<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="ie=edge" />
  <title>molotovs website</title>
    <link rel="icon" type="image/x-icon" href="/img/favicon.ico">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
    crossorigin="anonymous"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous" />
  <link rel="stylesheet" href="stylesheet.css" />
</head>

<body style="background-color: antiquewhite">
  <div class="container text-center">
    <div class="row min-vh-100 align-items-center">
      <div class="col"></div>
      <div class="col-8">
        <div class="row align-items-center">
          <div class="col"></div>
          <div class="col-8">
            <div class="row align-items-center">
              <div class="col borderB" style="padding-bottom: 10px">
                <img src="img/username.gif" />
              </div>
            </div>
            <div class="row align-items-center">
              <div class="col-8">
                <div class="row align-items-center">
                  <div class="col borderR" style="padding: 12px; text-align: left;">
                    <?php foreach ($archivePosts as $post):
                      $dt = DateTime::createFromFormat('d/m/Y His', $post['timestamp']);
                      $daysAgo = $dt ? (new DateTime())->diff($dt)->days : null;
                      ?>
                      <a href="post.php?id=<?= $post['index'] ?>" style="text-decoration: none; color: inherit;">
                        <div class="borderB" style="margin-bottom: 14px; padding-bottom: 10px;">
                          <div style="display: flex; justify-content: space-between; align-items: baseline;">
                            <strong><?= htmlspecialchars($post['title']) ?></strong>
                            <span class="text-muted" style="font-size: 0.8em;">
                              <?= $daysAgo !== null ? "{$daysAgo}d ago" : '—' ?>
                            </span>
                          </div>
                          <div class="text-muted" style="font-size: 0.8em; margin-bottom: 4px;">
                            <?= htmlspecialchars($post['genre'] ?? '[genre]') ?>
                          </div>
                          <div style="font-size: 0.9em;">
                            <?= htmlspecialchars($post['summary'] ??'[summary]') ?>
                          </div>
                        </div>
                      </a>
                    <?php endforeach; ?>
                  </div>

                </div>
              </div>
              <div class="col-4">
                <nav>
                    <a href="index.html" class="item">&raquo;home<br /></a>
                    <a href="blog.php" class="item">&raquo;blog<br /></a>
                    <a href="articles.html" class="active">&laquo;articles<br /></a>
                    <a href="about.html" class="item">&raquo;about<br /></a>
                    <a href="contact.html" class="item">&raquo;contact<br /></a>
                    <a href="https://lu.tiny-universes.net/indiewebmanifesto.html" class="item">&raquo;web manifesto<br /></a>
                  </nav>
              </div>
            </div>
            <div class="row align-items-center">
              <div class="col borderT" style="padding: 4px">
                <img src="img/badges/acab2.gif" />
                <img src="img/badges/antinazi.gif" />
                <img src="img/badges/armed.gif" />
                <img src="img/badges/nft.gif" />
                <img src="img/badges/bestview.gif" />
                <img src="img/badges/internetprivacy.gif" />
                <img src="img/badges/github-check.gif" />
                <img src="img/badges/twitter.gif" />
                <img src="img/badges/steam.gif" />
                <img src="img/badges/iww.gif" />
              </div>
            </div>
          </div>
          <div class="col"></div>
        </div>
      </div>
      <div class="col"></div>
    </div>
  </div>
</body>

</html>