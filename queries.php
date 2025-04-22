<?php
$queriesFile = 'queries.json';
$queries = [];

if (file_exists($queriesFile)) {
    $queries = json_decode(file_get_contents($queriesFile), true) ?? [];
}

// Sorguları tarihe göre ters sırala (en yeni en üstte)
usort($queries, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Para Değeri Hesaplama Sorguları</title>
    <style>
        :root {
            --primary-color: #2563eb;
            --background-color: #f1f5f9;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--background-color);
            color: #1f2937;
            margin: 0;
            padding: 40px 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }

        h1 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1.875rem;
            font-weight: 700;
        }

        .query-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .query-item {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            transition: background-color 0.2s;
        }

        .query-item:hover {
            background-color: #f9fafb;
        }

        .query-item:last-child {
            border-bottom: none;
        }

        .query-date {
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .query-content {
            font-size: 1.125rem;
            line-height: 1.5;
        }

        .query-value {
            font-weight: 600;
            color: var(--primary-color);
        }

        .back-link {
            display: inline-block;
            margin-top: 2rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 640px) {
            .container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Para Değeri Hesaplama Sorguları</h1>
        
        <?php if (empty($queries)): ?>
            <p style="text-align: center; color: #6b7280;">Henüz hiç sorgu yapılmamış.</p>
        <?php else: ?>
            <ul class="query-list">
                <?php foreach ($queries as $query): ?>
                    <li class="query-item">
                        <div class="query-date">
                            <?= date('d.m.Y H:i', strtotime($query['date'])) ?>
                        </div>
                        <div class="query-content">
                            <?= $query['year'] ?> yılındaki 
                            <span class="query-value"><?= number_format(round($query['amount']), 0, ',', '.') ?> TL</span>'nin 
                            bugünkü değeri: 
                            <span class="query-value"><?= number_format(round($query['present_value']), 0, ',', '.') ?> TL</span>
                            <br>
                            <i><a href="/?amount=<?= $query['amoun'] ?>year=<?= $query['year'] ?>">Tekrar sorgula</a></a></i>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <a href="index.php" class="back-link">← Ana Sayfaya Dön</a>
    </div>
</body>
</html> 