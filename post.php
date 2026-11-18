<?php
function parseJsonBlog(string $filePath, $id, string $postsKey = 'posts'): array
{
    $json = file_get_contents($filePath);
    $data = json_decode($json, true);

    if (isset($data[$postsKey]) && is_array($data[$postsKey])) {
        $posts = $data[$postsKey];
    } elseif (is_array($data) && array_values($data) === $data) {
        $posts = $data;
    } else {
        throw new Exception("No posts array found in JSON (expected '{$postsKey}' or a root array).");
    }

    uasort($posts, function ($a, $b) {
        $format = 'd/m/Y His';
        $dateA = DateTime::createFromFormat($format, $a['timestamp']);
        $dateB = DateTime::createFromFormat($format, $b['timestamp']);
        return $dateB <=> $dateA;
    });

    $post = $posts[$id] ?? null;

    if (!$post) {
        return [];
    }

    return [
        'title' => $post['title'] ?? null,
        'content' => $post['content'] ?? null,
        'author' => $post['author'] ?? null,
        'timestamp' => $post['timestamp'] ?? null,
        'image' => $post['image'] ?? null,
        'genre' => $post['genre'] ?? null,
    ];
}

$dataFile = __DIR__ . '/../data/blogs.json';

if (!isset($_GET['id']) || !ctype_digit((string) $_GET['id'])) {
    header('Location: blog.php');
    exit;
}

$id = (int) $_GET['id'];
$result = parseJsonBlog($dataFile, $id);

if (empty($result)) {
    http_response_code(404);
}

$dtObj = DateTime::createFromFormat('d/m/Y His', $result['timestamp'] ?? '');
$postDate = $dtObj ? $dtObj->format('d/m/Y H:i:s') : '—';
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>molotovs website</title>
    <script defer src="https://cloud.umami.is/script.js" data-website-id="ef6442e7-d841-4017-a8a6-c3d54ce8c3a5"></script>
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

      <!-- post + nav -->
      <div class="row g-0">
        <div class="col-12 col-md-8 blog-col">
          <div class="blog-head">
            <a class="back" href="blog.php">&laquo; back to list</a>
          </div>

          <article class="article">
            <?php if (!empty($result['image'])): ?>
              <img class="article-image" src="<?= htmlspecialchars($result['image']) ?>" alt="" />
            <?php endif; ?>

            <div class="article-meta">
              <?php if (!empty($result['genre'])): ?>
                <span class="post-genre"><?= htmlspecialchars($result['genre']) ?></span>
              <?php endif; ?>
              <span><?= htmlspecialchars($postDate) ?></span>
              <span class="article-author"><?= htmlspecialchars($result['author'] ?? 'unknown') ?></span>
            </div>

            <h3 class="article-title"><?= htmlspecialchars($result['title'] ?? 'untitled') ?></h3>

            <div class="article-content">
              <?= $result['content'] ?? '' ?>
            </div>
          </article>
        </div>

        <div class="col-12 col-md-4">
          <nav class="nav-block">
            <div class=""></div>
            <a href="index.html" class="item"><span>home</span><span class="num">01</span></a>
            <a href="blog.php" class="active"><span>blog</span><span class="num">02</span></a>
            <a href="articles.php" class="item"><span>articles</span><span class="num">03</span></a>
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