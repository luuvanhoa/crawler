<?php
/**
 * Created by PhpStorm.
 * User: hoalv12
 * Date: 12/11/2018
 * Time: 3:58 PM
 */

namespace CrawlerData;
include_once "vendor/autoload.php";

use Symfony\Component\DomCrawler\Crawler;

class CrawlerData
{
    public function curlUrl($url)
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; Konqueror/4.0; Microsoft Windows) KHTML/4.0.80 (like Gecko)");
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_URL, $url);
            $content = curl_exec($ch);
            curl_close($ch);

            return $content;
        } catch (\Exception $ex) {
            echo $ex->getMessage();
            die;
        }
    }

    public function getDomHtml($html)
    {
        $crawler = new Crawler($html);
        // $crawler = $crawler->filter('body > p');
        // $crawler = $crawler->filterXPath('//section[contains(@id, "classified-listings-result")]');
        // filterXPath('//span[@class="article"]')
        $crawler = $crawler->filterXPath('//section[contains(@id, "classified-listings-result")]')
            ->evaluate('//h2/a[@class="ellipsize  js-ellipsize-text"]');
        return $crawler;
    }

    public function crawl($url)
    {
        $html = $this->curlUrl($url);
        return $html;
    }
}
