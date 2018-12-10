<?php
/**
 * Created by PhpStorm.
 * User: luuhoa
 * Date: 12/10/18
 * Time: 10:44 PM
 */

namespace Crawler;
include_once "vendor/autoload.php";

use Symfony\Component\DomCrawler\Crawler;


class CrawlerData
{
    public function crawl()
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
    <body>
        <p class="message">Hello World!</p>
        <p>Hello Crawler!</p>
        <div id="test">ID test</div>
        <div class="test">Class test</div>
    </body>
</html>
HTML;

        $crawler = new Crawler($html);
        $crawler = $crawler->filter('body > p');
        var_dump($crawler);
    }
}

$objectCrawl = new CrawlerData();
$objectCrawl->crawl();