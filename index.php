<?php
require_once 'Currency.php';

$resultMessage = ''; // Sonuç mesajını tutacak değişken
$presentValue = null; // Hesaplanan değeri tutacak değişken
$amount = ''; // Formdaki miktarı tutmak için
$selectedYear = ''; // Formdaki yılı tutmak için

// Form gönderilmiş mi kontrol et
if (isset($_GET['amount']) && isset($_GET['year'])) {
    $amount = filter_input(INPUT_GET, 'amount', FILTER_VALIDATE_FLOAT); // Güvenlik için filtrele
    $selectedYear = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT); // Güvenlik için filtrele

    if ($amount !== false && $amount > 0 && $selectedYear !== false) {
        try {
            $currency = new Currency();
            $kurlar = $currency->readData(); // Veriyi JSON dosyasından oku

            if (empty($kurlar)) {
                 throw new Exception("Kur verisi bulunamadı veya dosya boş.");
            }

            // Seçilen yılın ortalama kurunu bul
            $yearlyData = array_filter($kurlar, function ($item) use ($selectedYear) {
                // Tarihin yıl kısmını al ve karşılaştır
                return substr($item['date'], 0, 4) == $selectedYear && !empty($item['value']);
            });

            if (empty($yearlyData)) {
                throw new Exception("$selectedYear yılı için geçerli kur verisi bulunamadı.");
            }

            // Yılın ortalama kurunu hesapla
            $yearlyValues = array_column($yearlyData, 'value');
            $pastRate = array_sum($yearlyValues) / count($yearlyValues);

            // En güncel (son) kuru bul
            $latestData = end($kurlar); // Dizinin son elemanını al
             if (!$latestData || empty($latestData['value'])) {
                 // Eğer son veri geçerli değilse, sondan bir öncekini dene veya hata ver
                 // Basitlik adına, geçerli bir değer bulana kadar geri gidebiliriz veya hata verebiliriz.
                 // Şimdilik sadece sonuncuyu kontrol edelim.
                 throw new Exception("En güncel kur verisi alınamadı.");
             }
            $currentRate = $latestData['value'];


            if ($pastRate > 0) {
                // TL'yi USD'ye çevir: Miktar / Geçmiş Kur
                $amountInUSD = $amount / $pastRate;
                // USD'yi güncel TL'ye çevir: USD Miktarı * Güncel Kur
                $presentValue = $amountInUSD * $currentRate;
                $resultMessage = sprintf(
                    "%d yılındaki %.2f TL'nin bugünkü yaklaşık değeri: %.2f TL",
                    $selectedYear,
                    $amount,
                    $presentValue
                );

                // Sorguyu kaydet
                $queryData = [
                    'year' => $selectedYear,
                    'amount' => $amount,
                    'present_value' => $presentValue,
                    'date' => date('Y-m-d H:i:s')
                ];
                
                // queries.json dosyasını oku veya oluştur
                $queriesFile = 'queries.json';
                $queries = [];
                if (file_exists($queriesFile)) {
                    $queries = json_decode(file_get_contents($queriesFile), true) ?? [];
                }
                
                // Yeni sorguyu ekle
                $queries[] = $queryData;
                
                // Son 1000 sorguyu tut
                if (count($queries) > 1000) {
                    $queries = array_slice($queries, -100);
                }
                
                // Dosyaya kaydet
                file_put_contents($queriesFile, json_encode($queries, JSON_PRETTY_PRINT));

            } else {
                 throw new Exception("$selectedYear yılı için hesaplama yapılamadı (geçmiş kur sıfır veya geçersiz).");
            }

        } catch (Exception $e) {
            // Hata yakalama
            $resultMessage = "Hata: " . $e->getMessage();
        }
    } else {
        $resultMessage = "Lütfen geçerli bir miktar ve yıl giriniz.";
    }
}

// Yıl seçimi için başlangıç ve bitiş yılları
$startYear = 1990;
$currentYear = date('Y');

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geçmiş Para Değeri Hesaplama</title>
    <style>
        :root {
            --primary-color: #2563eb;
            --success-color: #16a34a;
            --error-color: #dc2626;
            --background-color: #f1f5f9;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--background-color);
            color: #1f2937;
            margin: 0;
            padding: 40px 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .container {
            background-color: #ffffff;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            max-width: 32rem;
            width: 100%;
        }

        h1 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1.875rem;
            font-weight: 700;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        label {
            font-weight: 500;
            color: #4b5563;
            display: block;
            margin-bottom: 0.5rem;
        }

        input[type="number"],
        select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.2s;
            background-color: #fff;
            box-sizing: border-box;
            -moz-appearance: textfield;
        }

        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .form-group {
            width: 100%;
            box-sizing: border-box;
        }

        input[type="number"]:focus,
        select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        input[type="submit"] {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.875rem 1.5rem;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
        }

        input[type="submit"]:hover {
            background-color: #1d4ed8;
            transform: translateY(-1px);
        }

        input[type="submit"]:active {
            transform: translateY(0);
        }

        .result {
            margin-top: 2rem;
            padding: 1.5rem;
            border-radius: 1rem;
            font-size: 1.125rem;
            line-height: 1.5;
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: var(--success-color);
            text-align: center;
        }

        .result.error {
            background-color: #fef2f2;
            border-color: #fecaca;
            color: var(--error-color);
        }

        .result-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 1rem 0;
            display: block;
        }

        .result-details {
            font-size: 1rem;
            color: #4b5563;
            margin-top: 0.5rem;
        }

        .queries-link {
            display: block;
            text-align: center;
            margin-top: 2rem;
            padding: 1rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            border-top: 1px solid #e5e7eb;
        }

        .queries-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 640px) {
            .container {
                padding: 1.5rem;
            }
            .result-value {
                font-size: 2rem;
            }
        }
        .clear {
            clear: both;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><a href=".">Para Değeri Hesaplama</a></h1>
        <form method="GET" action="">
            <div class="form-group">
                <label for="amount">Para Miktarı (TL):</label>
                <input type="number" id="amount" name="amount" step="0.01" required value="<?= htmlspecialchars((string)$amount) ?>">
            </div>
            <div class="form-group">
                <label for="year">Yıl Seçin:</label>
                <select id="year" name="year" required>
                    <option value="">-- Yıl Seçiniz --</option>
                    <?php
                    for ($i = $currentYear; $i >= $startYear; $i--) {
                        // Seçili yılı işaretle
                        $selectedAttr = ($i == $selectedYear) ? 'selected' : '';
                        echo "<option value='$i' $selectedAttr>$i</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <input type="submit" value="Hesapla">
            </div>
        </form>

        <?php if ($resultMessage): ?>
            <div class="result <?= ($presentValue === null && $resultMessage) ? 'error' : '' ?>">
                <?php if ($presentValue !== null): ?>
                    <div class="result-details">
                        <?= htmlspecialchars(sprintf("%d yılındaki %.2f TL'nin bugünkü değeri:", $selectedYear, $amount)) ?>
                    </div>
                    <span class="result-value"><?= number_format(round($presentValue), 0, ',', '.') ?> TL</span>
                <?php else: ?>
                    <?= htmlspecialchars($resultMessage) ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <a href="queries.php" class="queries-link">Tüm Sorguları Görüntüle</a>
    </div>
    <div class="clear"> </div>
</body>
</html>
