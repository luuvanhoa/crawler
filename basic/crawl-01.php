<?php
/**
 * Created by PhpStorm.
 * User: luuhoa
 * Date: 12/10/18
 * Time: 11:06 PM
 */

include_once "vendor/autoload.php";

use Symfony\Component\DomCrawler\Crawler;


$html = file_get_contents('http://monngonquetui.com/');
$crawler = new Crawler();
//$crawler->addHtmlContent($html);
$crawler->addHtmlContent($html);

$tag = $crawler->filterXPath('//body/*');
foreach ($tag as $t) {
    echo '<pre>'; print_r($t->textContent); echo '</pre>';
}