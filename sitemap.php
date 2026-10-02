<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=UTF-8');

$baseUrl = rtrim(appBaseUrl(), '/');
$today = date('Y-m-d');

$urls = [];

$addUrl = static function (array &$urls, string $path, string $lastmod, string $changefreq = 'weekly', string $priority = '0.7') use ($baseUrl) {
    if ($path === 'index.php') {
        $path = '';
    } elseif ($path === 'about-us.php') {
        $path = 'about';
    } else {
        $path = preg_replace('/\.php(\?|$)/', '$1', $path);
    }
    $urls[] = [
        'loc' => $baseUrl . '/' . ltrim($path, '/'),
        'lastmod' => $lastmod,
        'changefreq' => $changefreq,
        'priority' => $priority,
    ];
};

$staticPages = [
    ['index.php', $today, 'daily', '1.0'],
    ['about-us.php', $today, 'monthly', '0.9'],
    ['management.php', $today, 'monthly', '0.8'],
    ['objectives.php', $today, 'monthly', '0.8'],
    ['projects.php', $today, 'weekly', '0.9'],
    ['events.php', $today, 'weekly', '0.8'],
    ['training.php', $today, 'weekly', '0.8'],
    ['documents.php', $today, 'weekly', '0.8'],
    ['certificates.php', $today, 'weekly', '0.7'],
    ['team.php', $today, 'weekly', '0.7'],
    ['volunteer-register.php', $today, 'weekly', '0.8'],
    ['member-register.php', $today, 'weekly', '0.8'],
    ['donate.php', $today, 'weekly', '0.9'],
    ['contact.php', $today, 'monthly', '0.8'],
    ['news.php', $today, 'daily', '0.8'],
    ['gallery.php', $today, 'weekly', '0.7'],
    ['crowdfunding.php', $today, 'weekly', '0.8'],
    ['inquiry.php', $today, 'monthly', '0.6'],
    ['awards.php', $today, 'monthly', '0.6'],
];

foreach ($staticPages as [$path, $lastmod, $changefreq, $priority]) {
    $addUrl($urls, $path, $lastmod, $changefreq, $priority);
}

try {
    $projects = $pdo->query("SELECT id, updated_at, created_at FROM projects WHERE status = 'Active' ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($projects as $project) {
        $addUrl(
            $urls,
            'project-details.php?id=' . urlencode((string)$project['id']),
            !empty($project['updated_at']) ? date('Y-m-d', strtotime((string)$project['updated_at'])) : date('Y-m-d', strtotime((string)($project['created_at'] ?? $today))),
            'weekly',
            '0.8'
        );
    }
} catch (Throwable $e) {
}

try {
    $events = $pdo->query("SELECT id, updated_at, created_at FROM events ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($events as $event) {
        $addUrl(
            $urls,
            'event-details.php?id=' . urlencode((string)$event['id']),
            !empty($event['updated_at']) ? date('Y-m-d', strtotime((string)$event['updated_at'])) : date('Y-m-d', strtotime((string)($event['created_at'] ?? $today))),
            'weekly',
            '0.7'
        );
    }
} catch (Throwable $e) {
}

try {
    $newsItems = $pdo->query("SELECT id, updated_at, created_at FROM news ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($newsItems as $news) {
        $addUrl(
            $urls,
            'news-details.php?id=' . urlencode((string)$news['id']),
            !empty($news['updated_at']) ? date('Y-m-d', strtotime((string)$news['updated_at'])) : date('Y-m-d', strtotime((string)($news['created_at'] ?? $today))),
            'weekly',
            '0.7'
        );
    }
} catch (Throwable $e) {
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $url): ?>
  <url>
    <loc><?php echo htmlspecialchars($url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8'); ?></loc>
    <lastmod><?php echo htmlspecialchars($url['lastmod'], ENT_XML1 | ENT_QUOTES, 'UTF-8'); ?></lastmod>
    <changefreq><?php echo htmlspecialchars($url['changefreq'], ENT_XML1 | ENT_QUOTES, 'UTF-8'); ?></changefreq>
    <priority><?php echo htmlspecialchars($url['priority'], ENT_XML1 | ENT_QUOTES, 'UTF-8'); ?></priority>
  </url>
<?php endforeach; ?>
</urlset>
