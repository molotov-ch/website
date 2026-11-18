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
    <link rel="icon" type="image/x-icon" href="/img/favicon.ico" />
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
      crossorigin="anonymous"
    ></script>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
      crossorigin="anonymous"
    />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=VT323&family=PT+Sans:ital,wght@0,400;0,700;1,400&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="/stylesheet.css" />
  </head>

  <body>
    <div class="container sheet p-0 my-3 my-md-5">
      <!-- now playing strip -->
      <div class="row g-0">
        <div class="col-12">
          <div id="now-playing" class="now-playing"></div>
        </div>
      </div>

      <!-- masthead: wedge + circle + name -->
      <div class="row g-0">
        <div class="col-12 masthead">
          <h1>molotv.dev</h1>
        </div>
      </div>

      <!-- articles + nav -->
      <div class="row g-0">
        <div class="col-12 col-md-8 blog-col">
          <div class="posts posts--wide">
            <?php foreach ($archivePosts as $post):
              $dt = DateTime::createFromFormat('d/m/Y His', $post['timestamp']);
              $daysAgo = $dt ? (new DateTime())->diff($dt)->days : null;
              $hasFile = resolveDownload($post['file']) !== null;
              ?>
              <?php if ($hasFile): ?>
                <a href="?download=<?= urlencode((string) $post['index']) ?>"
                  download="<?= htmlspecialchars(basename($post['file'])) ?>"
                  class="post">
              <?php else: ?>
                <div class="post">
              <?php endif; ?>
                <div class="post-top">
                  <span class="post-genre"><?= htmlspecialchars($post['genre'] ?? '[genre]') ?></span>
                  <span class="post-ago"><?= $daysAgo !== null ? "{$daysAgo}d ago" : '—' ?></span>
                </div>
                <div class="post-body">
                  <strong class="post-title"><?= htmlspecialchars($post['title']) ?></strong>
                  <div class="post-summary">
                    <?= htmlspecialchars($post['summary'] ?? '[summary]') ?>
                  </div>
                  <?php if ($hasFile): ?>
                    <span class="post-more">
                      download <?= htmlspecialchars(strtolower(pathinfo($post['file'], PATHINFO_EXTENSION))) ?> &raquo;
                    </span>
                  <?php endif; ?>
                </div>
              <?php if ($hasFile): ?>
                </a>
              <?php else: ?>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="col-12 col-md-4">
          <nav class="nav-block">
            <div class=""></div>
            <a href="index.html" class="item"><span>home</span><span class="num">01</span></a>
            <a href="blog.php" class="item"><span>blog</span><span class="num">02</span></a>
            <a href="articles.php" class="active"><span>articles</span><span class="num">03</span></a>
            <a href="about.html" class="item"><span>about</span><span class="num">04</span></a>
            <a href="contact.html" class="item"><span>contact</span><span class="num">05</span></a>
            <a href="https://lu.tiny-universes.net/indiewebmanifesto.html" class="item"
              ><span>web manifesto</span><span class="num">&nearr;</span></a>
          </nav>
        </div>
      </div>

      <!-- badges -->
      <div class="row g-0">
        <div class="col-12 badges">
          <img src="img/badges/acab2.gif" alt="" />
          <img src="img/badges/antinazi.gif" alt="" />
          <img src="img/badges/armed.gif" alt="" />
          <img src="img/badges/nft.gif" alt="" />
          <img src="img/badges/bestview.gif" alt="" />
          <img src="img/badges/internetprivacy.gif" alt="" />
          <img src="img/badges/github-check.gif" alt="" />
          <img src="img/badges/twitter.gif" alt="" />
          <img src="img/badges/steam.gif" alt="" />
          <img src="img/badges/iww.gif" alt="" />
        </div>
      </div>
    </div>

    <script>
      async function updateNowPlaying() {
        try {
          const res = await fetch("spotify.php");
          const data = await res.json();
          const el = document.getElementById("now-playing");
          if (data.playing) {
            el.innerHTML = `<span class="now-playing-inner">♪ ${data.track} — ${data.artist}</span>`;
          } else {
            el.innerHTML = `<span class="now-playing-inner">♪ not listening rn</span>`;
          }
        } catch (e) {
          // fail silently, don't clutter the page over a Spotify hiccup
        }
      }
      updateNowPlaying();
      setInterval(updateNowPlaying, 20000);
    </script>
  </body>
</html>