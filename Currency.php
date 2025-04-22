<?php

class Currency
{
    private $url;
    private $headers;
    private $response;
    private $fileName;
    private $startDate;
    private $endDate;

    public function __construct()
    {
        $this->startDate = '01-01-1990';
        $this->endDate = date('d-m-Y');

        // EVDS API URL'si
        $this->url = "https://evds2.tcmb.gov.tr/service/evds/series=TP.DK.USD.S.YTL&startDate=$this->startDate&endDate=$this->endDate&type=json&frequency=8&aggregation_types=avg";

        // HTTP Header'ı (KEY'inizi buraya girin)
        $this->headers = array(
            'key: r5a4N6NLHT'
        );

        $this->fileName = 'kurlar.json';
    }
    public function fetchData()
    {
        // CURL ile istek gönderme
        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $this->response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Hata: ' . curl_error($ch);
        }

        curl_close($ch);
    }
    public function parseData()
    {
        $kurlar = json_decode($this->response, true);
        $fileContent = [];
        foreach ($kurlar['items'] as $item) {
            $fileContent[] = [
                'date' => $item['Tarih'],
                'value' => $item['TP_DK_USD_S_YTL']
            ];
        }
        return $fileContent;
    }
    public function saveToJson()
    {
        // Json dosyasını oluşturma
        $jsonFile = fopen($this->fileName, 'w');
        if ($jsonFile === false) {
            throw new Exception("Dosya açılamadı: " . $this->fileName);
        }
        $jsonData = json_encode($this->parseData());
        if ($jsonData === false) {
            throw new Exception("Json verisi oluşturulamadı: " . json_last_error_msg());
        }
        $writeResult = fwrite($jsonFile, $jsonData);
        if ($writeResult === false) {
            throw new Exception("Json verisi yazılamadı: " . $this->fileName);
        }
        fclose($jsonFile);
    }
    public function getData()
    {
        $this->fetchData();
        $this->saveToJson($this->fileName);
    }
    public function readData()
    {
        $jsonFile = fopen($this->fileName, 'r');
        $data = fread($jsonFile, filesize($this->fileName));
        fclose($jsonFile);
        return json_decode($data, true);
    }
}