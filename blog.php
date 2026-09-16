<?php
function getPostByIndex(string $filePath, int $index, string $postsKey = 'posts')
{
    $jsonData = file_get_contents($filePath);
    $data = json_decode($jsonData, true);

    if (!isset($data[$postsKey]) || !is_array($data[$postsKey])) {
        return null;
    }

    return $data[$postsKey][$index] ?? null;
}

function parseJsonBlog(string $filePath, $id, string $postsKey = 'posts'): array
{
    $json  = file_get_contents($filePath);
    $data  = json_decode($json, true);

    if (isset($data[$postsKey]) && is_array($data[$postsKey])) {
        $posts = $data[$postsKey];
    } elseif (is_array($data) && array_values($data) === $data) {
        $posts = $data;
    } else {
        throw new Exception("No posts array found in JSON (expected '{$postsKey}' or a root array).");
    }

    usort($posts, function ($a, $b) {
        $format = 'd/m/Y His';
        $dateA  = DateTime::createFromFormat($format, $a['timestamp']);
        $dateB  = DateTime::createFromFormat($format, $b['timestamp']);
        return $dateB <=> $dateA;
    });

    $post = $posts[$id] ?? null;

    if (!$post) {
        return [];
    }

    return [
        'title'     => $post['title']     ?? null,
        'content'   => $post['content']   ?? null,
        'author'    => $post['author']     ?? null,
        'timestamp' => $post['timestamp'] ?? null,
        'image'     => $post['image']     ?? null,
    ];
}

function parseRecentJsonBlog(string $filePath, string $postsKey = 'posts'): array
{
    $json = file_get_contents($filePath);
    $data = json_decode($json, true);

    if (isset($data[$postsKey]) && is_array($data[$postsKey])) {
        $posts = $data[$postsKey];
    } elseif (is_array($data) && array_values($data) === $data) {
        $posts = $data;
    } else {
        throw new Exception("No posts array found in JSON.");
    }

    $post = null;
    $timestamp = null;

    foreach ($posts as $post) {
        if (!is_array($post) || !isset($post['timestamp'])) {
            continue;
        }
        $dt = DateTime::createFromFormat('d/m/Y His', $post['timestamp']);
        if (!$dt) {
            continue;
        }
        $ts = $dt->getTimestamp();
        if ($post === null || $ts > $timestamp) {
            $post = $post;
            $timestamp   = $ts;
        }
    }

    return [
        'title'     => $post['title']     ?? null,
        'content'   => $post['content']   ?? null,
        'author'    => $post['author']     ?? null,
        'timestamp' => $post['timestamp'] ?? null,
        'image'     => $post['image']     ?? null,
    ];
}

function parseJsonTitles(string $filePath, string $postsKey = 'posts'): array
{
    $jsonData = file_get_contents($filePath);
    $data     = json_decode($jsonData, true);

    if (isset($data[$postsKey]) && is_array($data[$postsKey])) {
        $posts = $data[$postsKey];
    } elseif (is_array($data) && array_values($data) === $data) {
        $posts = $data;
    } else {
        return [];
    }

    $filtered = [];
    foreach ($posts as $item) {
        if (isset($item['title'], $item['timestamp'])) {
            $filtered[] = [
                'title'     => $item['title'],
                'timestamp' => $item['timestamp'],
            ];
        }
    }

    usort($filtered, function ($a, $b) {
        $format = 'd/m/Y His';
        $dateA  = DateTime::createFromFormat($format, $a['timestamp']);
        $dateB  = DateTime::createFromFormat($format, $b['timestamp']);
        return $dateB <=> $dateA;
    });

    return $filtered;
}

$dataFile = __DIR__ . '/../data/blogs.json';

if (isset($_GET['id'])) {
    $result = parseJsonBlog($dataFile, (int) $_GET['id']);
} else {
    $result = parseRecentJsonBlog($dataFile);
}

$dtObj       = DateTime::createFromFormat('d/m/Y His', $result['timestamp'] ?? '');
$postDate    = $dtObj ? $dtObj->format('d/m/Y H:i:s') : '—';
$archivePosts = parseJsonTitles($dataFile);
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>molotovs website</title>
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
                  here i write blogs. i have some from my previous page, which i will move over to here once i have the backend setup
                </div>
                <div class="col-4 borderL">
                  <nav>
                    <a href="index.html">home<br /></a>
                    <a href="blog.php" class="active">blog<br /></a>
                    <a href="articles.html">articles<br /></a>
                    <a href="about.html">about<br /></a>
                    <a href="contact.html">contact<br /></a>
                    <a href="https://lu.tiny-universes.net/indiewebmanifesto.html">web manifesto<br /></a>
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
