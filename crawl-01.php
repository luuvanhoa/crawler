<?php
/**
 * Created by PhpStorm.
 * User: luuhoa
 * Date: 12/10/18
 * Time: 11:06 PM
 */

include_once "vendor/autoload.php";

use Symfony\Component\DomCrawler\Crawler;

$html = '<html>
<body>
    <span id="article-100" class="article">Article 1</span>
    <span id="article-101" class="article">Article 2</span>
    <span id="article-102" class="article">Article 3</span>
</body>
</html>';

$crawler = new Crawler();
$crawler->addHtmlContent($html);

$tag = $crawler->filterXPath('//body/*');
foreach ($tag as $t) {
    echo '<pre>'; print_r($t->textContent); echo '</pre>';
}