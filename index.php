<?php
ini_set('memory_limit', -1);
set_time_limit(-1);
/**
 * Created by PhpStorm.
 * User: luuhoa
 * Date: 12/10/18
 * Time: 10:44 PM
 */
include_once "CrawlerData.php";

$time = time();
echo "\n\t Start Time: " . $time . "\n\t";
$crawlerData = new \CrawlerData\CrawlerData();

$total_url = 0;
$data = array();

for ($i = 1; $i <= 3; $i++) {
    $url = "https://www.carlist.my/new-cars-for-sale/malaysia?page_number=$i&page_size=25";

    $html = $crawlerData->crawl($url);
    $response = $crawlerData->getDomHtml($html);
    foreach ($response as $item) {
        $href = $item->getAttribute('href');
        file_put_contents('C:\xampp\htdocs\crawler\danh-sach-href-25.txt', $href . PHP_EOL, FILE_APPEND | LOCK_EX);
        $data[] = $href;
        $total_url++;
    }
}

echo $total_url;
echo "\n\t End Time: " . (time() - $time) . "\n\t";
die;