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
                    <div class="col">

                        <a class="item" href="blog.php">&laquo; back to blog</a>
                    </div>
                    <div class="col-8">
                        <div class="row align-items-center">
                            <div class="col borderB" style="padding-bottom: 10px">
                                <img src="img/username.gif" />
                            </div>
                        </div>
                        <div class="row align-items-center">
                            <div class="col-8">
                                <div class="row align-items-center">
                                    <div class="col borderR"
                                        style="padding: 12px; text-align: left; max-height: 500px; overflow-y: auto;">

                                        <?php if (!empty($result['image'])): ?>
                                            <img src="<?= htmlspecialchars($result['image']) ?>"
                                                style="max-width: 100%; margin-bottom: 8px;" />
                                        <?php endif; ?>

                                        <h3><?= htmlspecialchars($result['title'] ?? 'untitled') ?></h3>
                                        <div class="text-muted" style="font-size: 0.85em; margin-bottom: 8px;">
                                            <?= htmlspecialchars($postDate) ?> —
                                            <?= htmlspecialchars($result['author'] ?? 'unknown') ?>
                                            <?php if (!empty($result['genre'])): ?>
                                                — <?= htmlspecialchars($result['genre']) ?>
                                            <?php endif; ?>
                                        </div>

                                        <div>
                                            <?= $result['content'] ?? '' ?>
                                        </div>

                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <nav>
                                    <a href="index.html" class="item">home<br /></a>
                                    <a href="blog.php" class="active">blog<br /></a>
                                    <a href="articles.html" class="item">articles<br /></a>
                                    <a href="about.html" class="item">about<br /></a>
                                    <a href="contact.html" class="item">contact<br /></a>
                                    <a href="https://lu.tiny-universes.net/indiewebmanifesto.html" class="item">web
                                        manifesto<br /></a>
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