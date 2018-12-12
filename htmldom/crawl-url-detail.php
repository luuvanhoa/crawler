<?php
/**
 * Created by PhpStorm.
 * User: hoalv12
 * Date: 12/12/2018
 * Time: 10:24 AM
 */
ini_set('memory_limit', -1);
set_time_limit(-1);

require_once('lib/woocommerce-api.php');
require_once('lib/simplehtmldom/simple_html_dom.php');
require_once __DIR__ . '/vendor/autoload.php';
use Automattic\WooCommerce\Client;


$i = 1; //$_SERVER['argv'][1];
$urlPageCate = "https://www.carlist.my/new-cars-for-sale/malaysia?page_number=$i&page_size=50";;
//$response = getListUrlFromCate($urlPageCate);

$url = 'https://www.carlist.my/new-cars/perodua-axia-1-0g-auto-high-loan-otr-price-rm33400-only/5428538';
parseContentDetail($url);

function getListUrlFromCate($urlPageCate)
{
    $contentHTML = curlHTMLContent($urlPageCate);
    $contentList = str_get_html($contentHTML)->find('a.js-ellipsize-text');
    $articles = array();
    foreach ($contentList as $article) {
        $href = trim($article->href);
        $articles[] = $href;
        file_put_contents('danh-sach-href.txt', $href . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    unset($contentList, $contentHTML);
    return $articles;
}

function _e($data)
{
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    die;
}

function getKeyDetail($objectHtml, $selector, $type = 'plaintext')
{
    $text = $objectHtml->find($selector, 0)->{$type};
    return $text;
}

function keyDetail($position)
{
    $data = array(
        0 => 'car_type',
        1 => 'make',
        2 => 'model',
        3 => 'variant',
        4 => 'year',
        5 => 'engine',
        6 => 'transmission',
        7 => 'seat_capacity',
        8 => 'mileage',
        9 => 'colour'
    );
    return isset($data[$position]) ? $data[$position] : false;
}

function cleanContent($content, $type = 'string')
{
    if ($type == 'string') {
        return trim($content);
    } else {
        return intval($content);
    }
}

function parseContentDetail($urlDetail)
{
    $username = 'nginx';
    $html = curlHTMLContent($urlDetail);
    $objectHtml = str_get_html($html);
    if ($objectHtml) {
        $product = array();
        $keyDetail = $objectHtml->find('div.listing__key-listing__list', 0);
        for ($i = 0; $i < 10; $i++) {
            if (keyDetail($i)) {
                $product[keyDetail($i)] = cleanContent($keyDetail->find('div.list-item', $i)->find('span', 1)->plaintext);
            }
        }

        $product['source_url'] = $urlDetail;
        $product['title'] = cleanContent($objectHtml->find('h1.headline', 0)->plaintext);

        $sku = explode('/', $urlDetail);
        $product['sku'] = cleanContent(end($sku));
        $product['description'] = '';
        $product['status'] = 'draft';

        // Slug
        $slug = $product['title'] . " " . $product["sku"];
        $slug = str_replace(array('®', '™'), array('', ''), $slug);
        $slug = remove_accents($slug);
        $slug = sanitize_title_with_dashes($slug);
        $slug = hyphenize($slug);
        $product['slug'] = $slug;

        // Price
        $price = str_replace(',', '', $objectHtml->find('div.listing__price', 0)->plaintext);
        $price = str_replace('RM', '', $price);
        $product['price'] = cleanContent($price, 'int');
        $product["enable_html_description"] = true;

        $images = array();
        $htmlImages = $objectHtml->find('div.cycle-slideshow', 1)->find('div.gallery__image');
        foreach ($htmlImages as $key => $image) {
            $urlImage = $image->find('img', 0)->getAttribute('data-src');
            $urlImage = explode('?', $urlImage);
            $images[] = array('src' => $urlImage[0], 'position' => $key);
        }
        $product['images'] = $images;
        $product['categories'] = array(array('id' => 15), array('id' => 148));

        try {
            $woocommerce = new Client(
                'http://wordpress.local',
                'ck_eb13f3c3186bb03b645b4f88793bffc3c06d2286',
                'cs_591ffff8e6459c3b31cf1ec702ef0f31803265a6',
                [
                    'wp_api' => true,
                    'version' => 'wc/v2',
                ]
            );

            $rs = $woocommerce->post('products', $product);

            if ($rs) {
                echo 'Ok';
                var_dump($rs);
                die;
            } else {
                die('sadfaslkdfjasldfjsalkdfjsaldkfjasdf');
            }

            $lastRequest = $woocommerce->http->getRequest();
            $lastRequest->getUrl(); // Requested URL (string).
            $lastRequest->getMethod(); // Request method (string).
            $lastRequest->getParameters(); // Request parameters (array).
            $lastRequest->getHeaders(); // Request headers (array).
            $lastRequest->getBody(); // Request body (JSON).

            // Last response data.
            $lastResponse = $woocommerce->http->getResponse();
            $lastResponse->getCode(); // Response code (int).
            $lastResponse->getHeaders(); // Response headers (array).
            $lastResponse->getBody(); // Response body (JSON).

        } catch (\Automattic\WooCommerce\HttpClient\HttpClientException $e) {
            var_dump($e->getMessage());
            $e->getRequest(); // Last request data.
            echo $e->getResponse(); // Last response data.
        }
    }
    echo 'ok';
}

function curlHTMLContent($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; Konqueror/4.0; Microsoft Windows) KHTML/4.0.80 (like Gecko)');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function sanitize_title_with_dashes($title, $raw_title = '', $context = 'display')
{
    $title = strip_tags($title);
    // Preserve escaped octets.
    $title = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title);
    // Remove percent signs that are not part of an octet.
    $title = str_replace('%', '', $title);
    // Restore octets.
    $title = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title);

    if (seems_utf8($title)) {
        if (function_exists('mb_strtolower')) {
            $title = mb_strtolower($title, 'UTF-8');
        }
        $title = utf8_uri_encode($title, 200);
    }

    $title = strtolower($title);

    if ('save' == $context) {
        // Convert nbsp, ndash and mdash to hyphens
        $title = str_replace(array('%c2%a0', '%e2%80%93', '%e2%80%94'), '-', $title);
        // Convert nbsp, ndash and mdash HTML entities to hyphens
        $title = str_replace(array('&nbsp;', '&#160;', '&ndash;', '&#8211;', '&mdash;', '&#8212;'), '-', $title);
        // Convert forward slash to hyphen
        $title = str_replace('/', '-', $title);

        // Strip these characters entirely
        $title = str_replace(array(
            // iexcl and iquest
            '%c2%a1', '%c2%bf',
            // angle quotes
            '%c2%ab', '%c2%bb', '%e2%80%b9', '%e2%80%ba',
            // curly quotes
            '%e2%80%98', '%e2%80%99', '%e2%80%9c', '%e2%80%9d',
            '%e2%80%9a', '%e2%80%9b', '%e2%80%9e', '%e2%80%9f',
            // copy, reg, deg, hellip and trade
            '%c2%a9', '%c2%ae', '%c2%b0', '%e2%80%a6', '%e2%84%a2',
            // acute accents
            '%c2%b4', '%cb%8a', '%cc%81', '%cd%81',
            // grave accent, macron, caron
            '%cc%80', '%cc%84', '%cc%8c',
        ), '', $title);

        // Convert times to x
        $title = str_replace('%c3%97', 'x', $title);
    }

    $title = preg_replace('/&.+?;/', '', $title); // kill entities
    $title = str_replace('.', '-', $title);

    $title = preg_replace('/[^%a-z0-9 _-]/', '', $title);
    $title = preg_replace('/\s+/', '-', $title);
    $title = preg_replace('|-+|', '-', $title);
    $title = trim($title, '-');

    return $title;
}

function remove_accents($string)
{
    if (!preg_match('/[\x80-\xff]/', $string))
        return $string;

    if (seems_utf8($string)) {
        $chars = array(
            // Decompositions for Latin-1 Supplement
            'ª' => 'a', 'º' => 'o',
            'À' => 'A', 'Á' => 'A',
            'Â' => 'A', 'Ã' => 'A',
            'Ä' => 'A', 'Å' => 'A',
            'Æ' => 'AE', 'Ç' => 'C',
            'È' => 'E', 'É' => 'E',
            'Ê' => 'E', 'Ë' => 'E',
            'Ì' => 'I', 'Í' => 'I',
            'Î' => 'I', 'Ï' => 'I',
            'Ð' => 'D', 'Ñ' => 'N',
            'Ò' => 'O', 'Ó' => 'O',
            'Ô' => 'O', 'Õ' => 'O',
            'Ö' => 'O', 'Ù' => 'U',
            'Ú' => 'U', 'Û' => 'U',
            'Ü' => 'U', 'Ý' => 'Y',
            'Þ' => 'TH', 'ß' => 's',
            'à' => 'a', 'á' => 'a',
            'â' => 'a', 'ã' => 'a',
            'ä' => 'a', 'å' => 'a',
            'æ' => 'ae', 'ç' => 'c',
            'è' => 'e', 'é' => 'e',
            'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i',
            'î' => 'i', 'ï' => 'i',
            'ð' => 'd', 'ñ' => 'n',
            'ò' => 'o', 'ó' => 'o',
            'ô' => 'o', 'õ' => 'o',
            'ö' => 'o', 'ø' => 'o',
            'ù' => 'u', 'ú' => 'u',
            'û' => 'u', 'ü' => 'u',
            'ý' => 'y', 'þ' => 'th',
            'ÿ' => 'y', 'Ø' => 'O',
            // Decompositions for Latin Extended-A
            'Ā' => 'A', 'ā' => 'a',
            'Ă' => 'A', 'ă' => 'a',
            'Ą' => 'A', 'ą' => 'a',
            'Ć' => 'C', 'ć' => 'c',
            'Ĉ' => 'C', 'ĉ' => 'c',
            'Ċ' => 'C', 'ċ' => 'c',
            'Č' => 'C', 'č' => 'c',
            'Ď' => 'D', 'ď' => 'd',
            'Đ' => 'D', 'đ' => 'd',
            'Ē' => 'E', 'ē' => 'e',
            'Ĕ' => 'E', 'ĕ' => 'e',
            'Ė' => 'E', 'ė' => 'e',
            'Ę' => 'E', 'ę' => 'e',
            'Ě' => 'E', 'ě' => 'e',
            'Ĝ' => 'G', 'ĝ' => 'g',
            'Ğ' => 'G', 'ğ' => 'g',
            'Ġ' => 'G', 'ġ' => 'g',
            'Ģ' => 'G', 'ģ' => 'g',
            'Ĥ' => 'H', 'ĥ' => 'h',
            'Ħ' => 'H', 'ħ' => 'h',
            'Ĩ' => 'I', 'ĩ' => 'i',
            'Ī' => 'I', 'ī' => 'i',
            'Ĭ' => 'I', 'ĭ' => 'i',
            'Į' => 'I', 'į' => 'i',
            'İ' => 'I', 'ı' => 'i',
            'Ĳ' => 'IJ', 'ĳ' => 'ij',
            'Ĵ' => 'J', 'ĵ' => 'j',
            'Ķ' => 'K', 'ķ' => 'k',
            'ĸ' => 'k', 'Ĺ' => 'L',
            'ĺ' => 'l', 'Ļ' => 'L',
            'ļ' => 'l', 'Ľ' => 'L',
            'ľ' => 'l', 'Ŀ' => 'L',
            'ŀ' => 'l', 'Ł' => 'L',
            'ł' => 'l', 'Ń' => 'N',
            'ń' => 'n', 'Ņ' => 'N',
            'ņ' => 'n', 'Ň' => 'N',
            'ň' => 'n', 'ŉ' => 'n',
            'Ŋ' => 'N', 'ŋ' => 'n',
            'Ō' => 'O', 'ō' => 'o',
            'Ŏ' => 'O', 'ŏ' => 'o',
            'Ő' => 'O', 'ő' => 'o',
            'Œ' => 'OE', 'œ' => 'oe',
            'Ŕ' => 'R', 'ŕ' => 'r',
            'Ŗ' => 'R', 'ŗ' => 'r',
            'Ř' => 'R', 'ř' => 'r',
            'Ś' => 'S', 'ś' => 's',
            'Ŝ' => 'S', 'ŝ' => 's',
            'Ş' => 'S', 'ş' => 's',
            'Š' => 'S', 'š' => 's',
            'Ţ' => 'T', 'ţ' => 't',
            'Ť' => 'T', 'ť' => 't',
            'Ŧ' => 'T', 'ŧ' => 't',
            'Ũ' => 'U', 'ũ' => 'u',
            'Ū' => 'U', 'ū' => 'u',
            'Ŭ' => 'U', 'ŭ' => 'u',
            'Ů' => 'U', 'ů' => 'u',
            'Ű' => 'U', 'ű' => 'u',
            'Ų' => 'U', 'ų' => 'u',
            'Ŵ' => 'W', 'ŵ' => 'w',
            'Ŷ' => 'Y', 'ŷ' => 'y',
            'Ÿ' => 'Y', 'Ź' => 'Z',
            'ź' => 'z', 'Ż' => 'Z',
            'ż' => 'z', 'Ž' => 'Z',
            'ž' => 'z', 'ſ' => 's',
            // Decompositions for Latin Extended-B
            'Ș' => 'S', 'ș' => 's',
            'Ț' => 'T', 'ț' => 't',
            // Euro Sign
            '€' => 'E',
            // GBP (Pound) Sign
            '£' => '',
            // Vowels with diacritic (Vietnamese)
            // unmarked
            'Ơ' => 'O', 'ơ' => 'o',
            'Ư' => 'U', 'ư' => 'u',
            // grave accent
            'Ầ' => 'A', 'ầ' => 'a',
            'Ằ' => 'A', 'ằ' => 'a',
            'Ề' => 'E', 'ề' => 'e',
            'Ồ' => 'O', 'ồ' => 'o',
            'Ờ' => 'O', 'ờ' => 'o',
            'Ừ' => 'U', 'ừ' => 'u',
            'Ỳ' => 'Y', 'ỳ' => 'y',
            // hook
            'Ả' => 'A', 'ả' => 'a',
            'Ẩ' => 'A', 'ẩ' => 'a',
            'Ẳ' => 'A', 'ẳ' => 'a',
            'Ẻ' => 'E', 'ẻ' => 'e',
            'Ể' => 'E', 'ể' => 'e',
            'Ỉ' => 'I', 'ỉ' => 'i',
            'Ỏ' => 'O', 'ỏ' => 'o',
            'Ổ' => 'O', 'ổ' => 'o',
            'Ở' => 'O', 'ở' => 'o',
            'Ủ' => 'U', 'ủ' => 'u',
            'Ử' => 'U', 'ử' => 'u',
            'Ỷ' => 'Y', 'ỷ' => 'y',
            // tilde
            'Ẫ' => 'A', 'ẫ' => 'a',
            'Ẵ' => 'A', 'ẵ' => 'a',
            'Ẽ' => 'E', 'ẽ' => 'e',
            'Ễ' => 'E', 'ễ' => 'e',
            'Ỗ' => 'O', 'ỗ' => 'o',
            'Ỡ' => 'O', 'ỡ' => 'o',
            'Ữ' => 'U', 'ữ' => 'u',
            'Ỹ' => 'Y', 'ỹ' => 'y',
            // acute accent
            'Ấ' => 'A', 'ấ' => 'a',
            'Ắ' => 'A', 'ắ' => 'a',
            'Ế' => 'E', 'ế' => 'e',
            'Ố' => 'O', 'ố' => 'o',
            'Ớ' => 'O', 'ớ' => 'o',
            'Ứ' => 'U', 'ứ' => 'u',
            // dot below
            'Ạ' => 'A', 'ạ' => 'a',
            'Ậ' => 'A', 'ậ' => 'a',
            'Ặ' => 'A', 'ặ' => 'a',
            'Ẹ' => 'E', 'ẹ' => 'e',
            'Ệ' => 'E', 'ệ' => 'e',
            'Ị' => 'I', 'ị' => 'i',
            'Ọ' => 'O', 'ọ' => 'o',
            'Ộ' => 'O', 'ộ' => 'o',
            'Ợ' => 'O', 'ợ' => 'o',
            'Ụ' => 'U', 'ụ' => 'u',
            'Ự' => 'U', 'ự' => 'u',
            'Ỵ' => 'Y', 'ỵ' => 'y',
            // Vowels with diacritic (Chinese, Hanyu Pinyin)
            'ɑ' => 'a',
            // macron
            'Ǖ' => 'U', 'ǖ' => 'u',
            // acute accent
            'Ǘ' => 'U', 'ǘ' => 'u',
            // caron
            'Ǎ' => 'A', 'ǎ' => 'a',
            'Ǐ' => 'I', 'ǐ' => 'i',
            'Ǒ' => 'O', 'ǒ' => 'o',
            'Ǔ' => 'U', 'ǔ' => 'u',
            'Ǚ' => 'U', 'ǚ' => 'u',
            // grave accent
            'Ǜ' => 'U', 'ǜ' => 'u',
        );

        // Used for locale-specific rules
        $locale = 'en_US';

        if ('de_DE' == $locale || 'de_DE_formal' == $locale || 'de_CH' == $locale || 'de_CH_informal' == $locale) {
            $chars['Ä'] = 'Ae';
            $chars['ä'] = 'ae';
            $chars['Ö'] = 'Oe';
            $chars['ö'] = 'oe';
            $chars['Ü'] = 'Ue';
            $chars['ü'] = 'ue';
            $chars['ß'] = 'ss';
        } elseif ('da_DK' === $locale) {
            $chars['Æ'] = 'Ae';
            $chars['æ'] = 'ae';
            $chars['Ø'] = 'Oe';
            $chars['ø'] = 'oe';
            $chars['Å'] = 'Aa';
            $chars['å'] = 'aa';
        } elseif ('ca' === $locale) {
            $chars['l·l'] = 'll';
        } elseif ('sr_RS' === $locale || 'bs_BA' === $locale) {
            $chars['Đ'] = 'DJ';
            $chars['đ'] = 'dj';
        }

        $string = strtr($string, $chars);
    } else {
        $chars = array();
        // Assume ISO-8859-1 if not UTF-8
        $chars['in'] = "\x80\x83\x8a\x8e\x9a\x9e"
            . "\x9f\xa2\xa5\xb5\xc0\xc1\xc2"
            . "\xc3\xc4\xc5\xc7\xc8\xc9\xca"
            . "\xcb\xcc\xcd\xce\xcf\xd1\xd2"
            . "\xd3\xd4\xd5\xd6\xd8\xd9\xda"
            . "\xdb\xdc\xdd\xe0\xe1\xe2\xe3"
            . "\xe4\xe5\xe7\xe8\xe9\xea\xeb"
            . "\xec\xed\xee\xef\xf1\xf2\xf3"
            . "\xf4\xf5\xf6\xf8\xf9\xfa\xfb"
            . "\xfc\xfd\xff";

        $chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";

        $string = strtr($string, $chars['in'], $chars['out']);
        $double_chars = array();
        $double_chars['in'] = array("\x8c", "\x9c", "\xc6", "\xd0", "\xde", "\xdf", "\xe6", "\xf0", "\xfe");
        $double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
        $string = str_replace($double_chars['in'], $double_chars['out'], $string);
    }

    return $string;
}

function seems_utf8($str)
{
    mbstring_binary_safe_encoding();
    $length = strlen($str);
    reset_mbstring_encoding();
    for ($i = 0; $i < $length; $i++) {
        $c = ord($str[$i]);
        if ($c < 0x80) $n = 0; // 0bbbbbbb
        elseif (($c & 0xE0) == 0xC0) $n = 1; // 110bbbbb
        elseif (($c & 0xF0) == 0xE0) $n = 2; // 1110bbbb
        elseif (($c & 0xF8) == 0xF0) $n = 3; // 11110bbb
        elseif (($c & 0xFC) == 0xF8) $n = 4; // 111110bb
        elseif (($c & 0xFE) == 0xFC) $n = 5; // 1111110b
        else return false; // Does not match any model
        for ($j = 0; $j < $n; $j++) { // n bytes matching 10bbbbbb follow ?
            if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80))
                return false;
        }
    }
    return true;
}

function mbstring_binary_safe_encoding($reset = false)
{
    static $encodings = array();
    static $overloaded = null;

    if (is_null($overloaded))
        $overloaded = function_exists('mb_internal_encoding') && (ini_get('mbstring.func_overload') & 2);

    if (false === $overloaded)
        return;

    if (!$reset) {
        $encoding = mb_internal_encoding();
        array_push($encodings, $encoding);
        mb_internal_encoding('ISO-8859-1');
    }

    if ($reset && $encodings) {
        $encoding = array_pop($encodings);
        mb_internal_encoding($encoding);
    }
}

function reset_mbstring_encoding()
{
    mbstring_binary_safe_encoding(true);
}

/**
 * @description remove ký tự đặc biệt
 * @param $string
 * @return string
 * /san-giao-dich/khai-truong-khu-nha-mau-“an-tuong-cua-chau-a”-1000284.html
 * /san-giao-dich/khai-truong-khu-nha-mau-an-tuong-cua-chau-a-1000284.html
 */
function hyphenize($string)
{
    $dict = array(
        "I'm" => "I am",
        "thier" => "their",
        // Add your own replacements here
    );
    return strtolower(
        preg_replace(
            array('#[\\s-]+#', '#[^A-Za-z0-9/\. -]+#'),
            array('-', ''),
            // the full cleanString() can be downloaded from http://www.unexpectedit.com/php/php-clean-string-of-utf8-chars-convert-to-similar-ascii-char
            cleanString(
                str_replace( // preg_replace can be used to support more complicated replacements
                    array_keys($dict),
                    array_values($dict),
                    urldecode($string)
                )
            )
        )
    );
}

function cleanString($text)
{
    $utf8 = array(
        '/[áàâãªä]/u' => 'a',
        '/[ÁÀÂÃÄ]/u' => 'A',
        '/[ÍÌÎÏ]/u' => 'I',
        '/[íìîï]/u' => 'i',
        '/[éèêë]/u' => 'e',
        '/[ÉÈÊË]/u' => 'E',
        '/[óòôõºö]/u' => 'o',
        '/[ÓÒÔÕÖ]/u' => 'O',
        '/[úùûü]/u' => 'u',
        '/[ÚÙÛÜ]/u' => 'U',
        '/ç/' => 'c',
        '/Ç/' => 'C',
        '/ñ/' => 'n',
        '/Ñ/' => 'N',
        '/–/' => '-', // UTF-8 hyphen to "normal" hyphen
        '/[’‘‹›‚]/u' => ' ', // Literally a single quote
        '/[“”«»„]/u' => ' ', // Double quote
        '/ /' => ' ', // nonbreaking space (equiv. to 0x160)
    );
    return preg_replace(array_keys($utf8), array_values($utf8), $text);
}

function utf8_uri_encode($utf8_string, $length = 0)
{
    $unicode = '';
    $values = array();
    $num_octets = 1;
    $unicode_length = 0;

    mbstring_binary_safe_encoding();
    $string_length = strlen($utf8_string);
    reset_mbstring_encoding();

    for ($i = 0; $i < $string_length; $i++) {

        $value = ord($utf8_string[$i]);

        if ($value < 128) {
            if ($length && ($unicode_length >= $length))
                break;
            $unicode .= chr($value);
            $unicode_length++;
        } else {
            if (count($values) == 0) {
                if ($value < 224) {
                    $num_octets = 2;
                } elseif ($value < 240) {
                    $num_octets = 3;
                } else {
                    $num_octets = 4;
                }
            }

            $values[] = $value;

            if ($length && ($unicode_length + ($num_octets * 3)) > $length)
                break;
            if (count($values) == $num_octets) {
                for ($j = 0; $j < $num_octets; $j++) {
                    $unicode .= '%' . dechex($values[$j]);
                }

                $unicode_length += $num_octets * 3;

                $values = array();
                $num_octets = 1;
            }
        }
    }

    return $unicode;
}

