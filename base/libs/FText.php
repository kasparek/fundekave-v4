<?php
class FText
{

    public static function options($value, $text, $compare = 0)
    {
        return '<option value="' . $value . '"' . (($value == $compare) ? (' selected="selected"') : ('')) . '>' . (empty($text) ? $value : $text) . '</option>';
    }

    /**
     * transliterate czech diacritic to ascii
     *
     * @param UTF-8 $text
     * @return ASCII string
     */
    public static function safeText($str)
    {
        $str = preg_replace('~[^\\pL0-9_]+~u', '-', $str);
        $str = trim($str, "-");
        $str = iconv("UTF-8", "ASCII//TRANSLIT", $str);
        $str = strtolower($str);
        return preg_replace('~[^-a-z0-9_]+~', '', $str);
    }

    public static function strReplace($textSource, $offset, $length, $textReplace)
    {
        return substr($textSource, 0, $offset) . $textReplace . substr($textSource, $offset + $length);
    }
    public static function wrap($strParam, $i = 70, $wrap = "\n")
    {
        $str = strip_tags($strParam);
        $str = str_replace("\n", ' ', $str);
        $arr = explode(' ', $str);
        foreach ($arr as $word) {
            $word = trim($word);
            if (strlen($word) > $i && strpos($word, 'http') === false) {
                $arrRep[$word] = wordwrap($word, $i, $wrap, 1);
            }
        }
        if (!empty($arrRep)) {
            foreach ($arrRep as $k => $v) {
                $strParam = str_replace($k, $v, $strParam);
            }
        }
        return $strParam;
    }

    /**
     * function implement before any text is inserted into textarea
     **/
    public static function textToTextarea($text)
    {
        if (strpos($text, '<p>') === false) {
            $text = str_replace("\n", "<br />\n", $text);
        }

        return $text;
    }

    /**
     * option - 0 - remove html, safe text 1 - remove html, safe text, parse bb, 2- nothing - just trim
     *
     * @param input string $text
     * @param numeric $option
     * @param boolean $wrap - wrap long words
     */
    public static function preProcess($text, $paramsArr = array())
    {
        if (!is_array($paramsArr)) {
            $paramsArr = array();
        }

        if (!isset($paramsArr['formatOption'])) {
            $paramsArr['formatOption'] = 1;
        }

        if (isset($paramsArr['plainText'])) {
            $paramsArr['formatOption'] = 0;
        }

        $text = stripslashes(trim($text));
        if ($text == '') {
            return '';
        }

        $user = FUser::getInstance();

        if ($paramsArr['formatOption'] == 0 || !$user->idkontrol) {
            $text = strip_tags($text);
        }

        if ($paramsArr['formatOption'] < 2) {
            $config   = HTMLPurifier_Config::createDefault();
            $purifier = new HTMLPurifier($config);
/*
if($user->idkontrol && $paramsArr['formatOption']>0) {
$safe->deleteTags = array('applet', 'base', 'basefont', 'bgsound', 'blink', 'body', 'frame', 'frameset', 'head', 'html', 'ilayer', 'layer', 'link', 'meta', 'style', 'title', 'script');
$safe->attributes = array('dynsrc');
$safe->protocolFiltering = 'black';
}
 */
            $text = $purifier->purify($text);
        }

        $text = FText::wrap($text);

        if (isset($paramsArr['lengthLimit'])) {
            if ($paramsArr['lengthLimit'] > 0) {
                if (mb_strlen($text) > $paramsArr['lengthLimit']) {
                    $text = mb_substr($text, 0, $paramsArr['lengthLimit']);
                    if (isset($paramsArr['lengthLimitAddOnEnd'])) {
                        $text .= $paramsArr['lengthLimitAddOnEnd'];
                    }

                }
            }
        }

        return $text;
    }

    public static function replace_url($arr)
    {

        if (strpos($arr[1], '</a>')) {
            return $arr[1] . $arr[2] . $arr[3];
        }
        if (false !== $pos = strpos($arr[1], '<')) {
            $arr[2] = substr($arr[1], $pos);
            $arr[1] = substr($arr[1], 0, $pos);
        }
        if (strncmp("http", $arr[1], 4) == 0) {
            return "<a href=$arr[1]>$arr[1]</a>$arr[2]$arr[3]";
        } else {
            return "<a href=" . "http://" . $arr[1] . ">$arr[1]</a>$arr[2]$arr[3]";
        }

    }
    public static function auto_link_text($str, $attributes = array())
    {
        $str = str_replace('&nbsp;', ' &nbsp; ', $str);
        return preg_replace_callback('/\b((?:http|www).+?)((?!\/)[\p{P}]+)?(\s|$)/x', array('FText', 'replace_url'), $str);
    }

    public static function postProcess($text)
    {
        if (empty($text)) {
            return $text;
        }
        $text = self::auto_link_text($text);
        //replace all plain text URLs with html
        libxml_use_internal_errors(true);
        $dom                     = new DOMDocument();
        $dom->preserveWhiteSpace = false;

        $dom->loadHTML('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><div id="posttextprocessing">' . $text . '</div></body></html>');

        $x     = new DOMXPath($dom);
        $nodes = $x->query("//text()[not(ancestor::a)]");

        $linkSeen = array();
        /*
        foreach ($nodes as $node) {
        $nodeText = $node->nodeValue;

        if (preg_match_all("#\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#i", $nodeText, $matches, PREG_OFFSET_CAPTURE)) {
        //remove old node
        $lastPos = 0;
        foreach ($matches[0] as $url) {
        if (!isset($linkSeen[$url[0]])) {
        if ($url[1] > $lastPos) {
        $pretext = $dom->createTextNode(mb_substr($nodeText, $lastPos, $url[1] - $lastPos));
        $node->parentNode->insertBefore($pretext, $node);
        }
        $a            = $dom->createElement("a");
        $a->nodeValue = $url[0];
        $a->setAttribute('href', $url[0]);
        $node->parentNode->insertBefore($a, $node);
        $lastPos           = $url[1] + mb_strlen($url[0]);
        $linkSeen[$url[0]] = 1;
        }
        }
        if ($lastPos < mb_strlen($nodeText)) {
        $aftertext = $dom->createTextNode(mb_substr($nodeText, $lastPos));
        $node->parentNode->insertBefore($aftertext, $node);
        }
        $node->parentNode->removeChild($node);
        }
        }
         */
        $tags    = array();
        $results = $dom->getElementsByTagName('img');
        foreach ($results as $result) {
            $tags[] = $result;
        }

        $results = $dom->getElementsByTagName('a');
        foreach ($results as $result) {
            $tags[] = $result;
        }

        if (!empty($tags)) {
            foreach ($tags as $tag) {
                $try = false;

                if ($tag->tagName == 'img') {
                    $url = $tag->getAttribute('src');
                    if ($tag->parentNode->tagName != 'a') {
                        $try = true;
                    }
                } else {
                    $url = $tag->getAttribute('href');
                    if ((bool) $tag->nodeValue && strpos($tag->nodeValue, $url) !== false) {
                        $try = true;
                    }
                }
                if ($try) {
                    $local = false;
                    //check if url is internal or external
                    //page
                    if (preg_match("/.*[&?|]k=([a-zA-Z0-9]{5}).*/i", $url, $matches)) {
                        $local = true;
                        //it's local page
                        if ($pageVO = FactoryVO::get('PageVO', $matches[1], true)) {
                            $tag->nodeValue = $pageVO->get('name');
                            if ($pageVO->typeId === 'galery') {
                                $fItems = new FItems('galery');
                                $fItems->addWhere("pageId = '" . $pageVO->pageId . "'");
                                $fItems->addWhere("typeId = 'galery'");
                                $items = $fItems->getList();
                                $d     = $dom->createElement("div");
                                $gs    = $dom->createElement("div");
                                $gs->setAttribute('class', 'grid-sizer');
                                $d->appendChild($gs);
                                $first = true;
                                $total = count($items);
                                $i     = 0;
                                foreach ($items as $itemVO) {
                                    $a = $dom->createElement("a");
                                    $a->setAttribute('href', '?i=' . $itemVO->itemId);
                                    $a->setAttribute('class', 'grid-item');
                                    $img = $dom->createElement("img");
                                    $img->setAttribute('src', $itemVO->thumbUrl);
                                    if ($i < $total - 6 && $i > 3) {
                                        if (rand(1, 100) > 55) {
                                            $a->setAttribute('style', 'width: 200px;');
                                        }
                                    }
                                    if ($first) {
                                        $a->setAttribute('style', 'width: 300px;');
                                    }
                                    $first = false;
                                    $a->appendChild($img);
                                    $d->appendChild($a);
                                    $i++;
                                }
                                $d->setAttribute('class', 'blog-gallery-thumbs');
                                $tag->parentNode->insertBefore($d, $tag->nextSibling);
                            }
                        }
                    } else {
                        if (preg_match("/.*[&?|]i=([0-9]{1,7}).*/i", $url, $matches)) {
                            //we have itemId
                            $itemVO = FactoryVO::get('ItemVO', $matches[1], true);
                            if ($itemVO->loaded) {
                                $local = true;
                                if ($itemVO->typeId == 'galery') {
                                    while ($tag->firstChild) {
                                        $tag->removeChild($tag->firstChild);
                                    }

                                    $img = $dom->createElement("img");
                                    $tag->appendChild($img);
                                    $img->setAttribute('src', $itemVO->thumbUrl);
                                } else {
                                    $tag->nodeValue = $itemVO->get('addon');
                                }
                            }
                        } else {
                            //item
                            $regItemList = array(
                                "/.*_([a-zA-Z0-9]{5})\/([0-9a-zA-Z._-]+\.[jpeg|jpg|png|gif]+)$/i" //http://fundekave.net/image/170x170/crop/dany/20120303_pangaimotu-island_zAV4q/dsc07869.jpg
                                , "/.*obr\/.+([a-zA-Z0-9]{5})\/([0-9a-zA-Z._-]+\.[jpeg|jpg|png|gif]+)/i"
                                , "/.*data\/cache\/img\/([a-zA-Z0-9]{5})-.*\/([0-9a-zA-Z-_.]+\.[jpeg|jpg|png|gif]+)/i", //sample http://fundekave.net/data/cache/img/cKFbk-awake-work-in-progress/img_0111.jpg
                            );
                            $i = 0;
                            foreach ($regItemList as $regex) {
                                if (preg_match($regex, $url, $matches)) {
                                    //we have pageId and enclosure
                                    $itemVO            = new ItemVO();
                                    $itemVO->pageId    = $matches[1];
                                    $itemVO->enclosure = $matches[2];
                                    //TODO: instead of lightbox link to gallery detail?
                                    if ($itemVO->load()) {
                                        $local = true;
                                        //item load suceess
                                        $img = $dom->createElement("img");
                                        $img->setAttribute('src', $i == 0 ? $url : $itemVO->thumbUrl);
                                        $img->setAttribute('class', 'hentryimage');
                                        if ($tag->tagName == 'a') {
                                            $tag->setAttribute('rel', 'lightbox-page');
                                            $tag->setAttribute('title', $itemVO->pageVO->get('name'));
                                            $tag->setAttribute('href', $itemVO->getImageUrl(null, '800x800/prop'));
                                            while ($tag->firstChild) {
                                                $tag->removeChild($tag->firstChild);
                                            }

                                            $tag->appendChild($img);
                                        } else {
                                            //img
                                            //if data/cache - replace with correct thumb url
                                            if (strpos($url, 'data/cache') !== false) {
                                                $img->setAttribute('src', $itemVO->thumbUrl);
                                            }
                                            $a = $dom->createElement("a");
                                            $tag->parentNode->replaceChild($a, $tag);
                                            $a->setAttribute('href', $itemVO->getImageUrl(null, '800x800/prop'));
                                            $a->setAttribute('title', $itemVO->pageVO->get('name'));
                                            $a->setAttribute('rel', 'lightbox-page');
                                            $a->appendChild($img);
                                        }
                                    }
                                    $i++;
                                    break;
                                }
                            }
                        }
                    }

                    if (!$local) {
                        //external images with thumb
                        //if image make thumb
                        if ($tag->tagName == 'img' || preg_match("/(?i)\.(jpeg|jpg|png)$/i", $url, $matches)) {
                            $domain = parse_url($url, PHP_URL_HOST);
                            if ($domain) {
                                $domain = explode('.', $domain);
                                $domain = $domain[count($domain) - 2];
                            }
                            if (strpos($url, 'norender') === false && !empty($domain) && strpos(DOMAINS_LOCAL, $domain) === false) {
                                $urlEncoded = base64_encode($url);
                                $imgSrc     = FConf::get("galery", "targetUrlBase") . FConf::get("galery", "forum_thumbCut") . '/remote/' . md5(ImageConfig::$salt . $urlEncoded) . $urlEncoded;

                                $img = $dom->createElement("img");
                                $img->setAttribute('src', $imgSrc);
                                //$img->setAttribute('class','hentryimage');
                                if ($tag->tagName == 'img') {
                                    $a = $dom->createElement("a");
                                    $tag->parentNode->replaceChild($a, $tag);
                                    $a->setAttribute('href', $url);
                                    $a->setAttribute('rel', 'lightbox-page');
                                    $a->appendChild($img);
                                } else {
                                    $tag->setAttribute('rel', 'lightbox-page');
                                    while ($tag->firstChild) {
                                        $tag->removeChild($tag->firstChild);
                                    }

                                    $tag->appendChild($img);
                                }
                            } else {
                                //don't modify images from local server
                            }
                        } else {
                            //try get title
                            $youtubeId = false;
                            $vimeoId   = false;
                            if (preg_match("/http.*www.youtube.com.*v=([A-Za-z0-9-_]+)/i", $url, $matches)) {
                                $youtubeId = $matches[1];
                            }

                            if (preg_match("/.*youtu.be\/([A-Za-z0-9-_]+)/i", $url, $matches)) {
                                $youtubeId = $matches[1];
                            }

                            if (preg_match("/.*vimeo.com\/([0-9]+)$/i", $url, $matches)) {
                                $vimeoId = $matches[1];
                            }

                            if ($youtubeId || $vimeoId) {
                                //<iframe width="420" height="315" src="//www.youtube.com/embed/GHwXPDY_xhk" frameborder="0" allowfullscreen></iframe>
                                $iframe = $dom->createElement("iframe");
                                $iframe->setAttribute('allowfullscreen', 'allowfullscreen');
                                $iframe->setAttribute('frameborder', '0');
                                $iframe->setAttribute('width', '560');
                                $iframe->setAttribute('height', '315');
                                $iframe->setAttribute('src', $vimeoId ? 'http://player.vimeo.com/video/' . $vimeoId . '?title=0&amp;byline=0&amp;portrait=0' : '//www.youtube.com/embed/' . $youtubeId);
                                $tag->parentNode->replaceChild($iframe, $tag);
                            } else {
                                //$pageContent = FSystem::curl_get_file_contents($url);
                                $pageContent = false;
                                if ($pageContent !== false) {
                                    if (preg_match("/\<title\>(.*)\<\/title\>/i", $pageContent, $matches)) {
                                        $title = trim($matches[1]);
                                        if (!preg_match("/^[0-9]{3} .*/i", $title)) {
                                            $tag->nodeValue = $matches[1];
                                        }

                                    }
                                }

                            }
                        }
                    }
                }
            }
        }

        $textElement = $dom->getElementById('posttextprocessing');
        $text        = $dom->saveXML($textElement);
        $text        = substr($text, 29, strlen($text) - 35);

        //add line breaks
        if (strpos($text, '<p>') === false) {
            $textArr    = explode("\n", $text);
            $textArrLen = count($textArr);

            for ($i = 0; $i < $textArrLen; $i++) {
                $thisWord   = trim($textArr[$i]);
                $nextWord   = $i + 1 < $textArrLen ? trim($textArr[$i + 1]) : false;
                $addBr      = true;
                $regexBegin = "/^(<p|<div|<img)/i";
                $regexEnd   = "/(\/p>|div>|<br>|<br \/>)$/i";
                if ($nextWord !== false) {
                    if (empty($nextWord)) {
                        $addBr = false;
                    } else if (preg_match($regexBegin, $nextWord)) {
                        $addBr = false;
                    }
                    if ($addBr && preg_match($regexEnd, $thisWord)) {
                        $addBr = false;
                    }
                    if ($addBr) {
                        $textArr[$i] .= "<br>";
                        //$textArr[$i] = "<p>".$textArr[$i].'</p>';
                        //$textArr[$i] .= "\n";
                    }
                }
            }
            $text = implode("\n", $textArr);
        }

        return trim($text);
    }
}
