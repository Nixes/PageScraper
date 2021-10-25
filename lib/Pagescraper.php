<?php

namespace Nixes\Pagescraper;
use DOMDocument;
use DOMNode;
use DOMXPath;

/**
 * Pagescraper
 */
class Pagescraper {
    /**
     * @var Page $page
     */
    private $page;
    /**
     * @var bool $debug
     */
    private $debug;

    /**
     * Constructor
     */
    function __construct() {
        $this->page = new Page;
        $this->setDebug(false);
    }

    /**
     * @param bool $debug
     *
     * @return static
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
        return $this;
    }

    /**
     * @return bool
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * returns index of array element that contains the largest value
     * @param int[] $arr
     * @return int
     */
    private function findHighestIndex(array $arr) {
        $highestNo = 0;
        $indexHighestNo = 0;
        $arrCount = count($arr);
        for ( $i=0; $i < $arrCount; $i++ ) {
            if ($arr[$i] > $highestNo) {
                $highestNo=$arr[$i];
                $indexHighestNo=$i;
            }
        }
        return $indexHighestNo;
    }

    /**
     * this function differs from other recursive functions below in that it actually remove nodes that fit a certain criteria
     * @param DOMNode $DOMNode
     */
    private function removeJunk(DOMNode $DOMNode) {
        if ($DOMNode->hasChildNodes()) {
            $childNodes = $DOMNode->childNodes;
            for ($i=0; $i < $childNodes->length; $i++ ) { // todo: optimise by copying to a list and running through that as the original list of child nodes stays the same despite elements being deleted, this results in offsets or elements being checked for being empty
                $childNode = $childNodes->item($i);
                if ($childNode->hasAttributes() && Blacklist::containsJunk($childNode) ) {
                    $DOMNode->removeChild($childNode);
                    break;
                }
                if ( isset($childNode->tagName) && Blacklist::containsBadTag($childNode) ) {
                    $DOMNode->removeChild($childNode);
                    break;
                }
                $this->removeJunk($childNode);
            }
        }
    }

    /**
     * a function that converts a relative style url to an absolute one, works on resources and urls
     * @param string $url
     * @param string $location
     */
    private function convertRelToAbs($url, $location) {
        $path = $url;
        if (!empty($path)) {
            if ((substr($url, 0, 7) == 'http://') || (substr($url, 0, 8) == 'https://')) {
                // url is absolute
                return $url;
            } else {
                // url is relative
                $parsed_url = parse_url( $location );
                if (isset($parsed_url['scheme'])) {
                    $result = $parsed_url['scheme'].'://'.$parsed_url['host']. $path;
                } else {
                    $result = $url;
                }
                return $result;
            }
        }
    }

    /**
     * @param DOMNode $DOMNode
     * @return string
     */
    private function getParagraphs(DOMNode $DOMNode) {
        $currentTag = $DOMNode->tagName;
        $content = "";

        //whitelist
        if ($currentTag =="p" ) {
            $content = "<p>";
            // this loop here check for any other inline formating within the paragraph
            foreach($DOMNode->childNodes as $node) {
                if (isset($node->tagName) && $node->tagName =="a") {
                    $content .= "<a href='". $this->convertRelToAbs( $node->attributes->getNamedItem("href")->nodeValue,$this->page->getLocation() ) ."'>".$node->nodeValue."</a>";
                } else {
                    $content .= $node->nodeValue;
                }
            }
            $content .= "</p>";
        }
        if ($currentTag =="img" ) {
            $content = "<div class='img_container'><img src='". $this->convertRelToAbs( $DOMNode->attributes->getNamedItem("src")->nodeValue,$this->page->getLocation() ) ."' style='vertical-align:middle'></img></div>";
        }
        //blacklist, if one of these elements are found, stop here as any further processing is a waste of time
        if ($currentTag =="aside" ) {
            //echo "<p>Aside Detected and removed</p>";
            return "";
        }
        if ($currentTag =="noscript" ) { // should remove many kinds of browser plugin warnings
            return "";
        }
        if ($DOMNode->hasChildNodes()) {
            if ($currentTag =="blockquote" ) {
                $content .= "<blockquote>";
            }

            $childNodes = $DOMNode->childNodes;
            for ( $i=0; $i < $childNodes->length; $i++ ) {
                $childNode = $childNodes->item($i);
                if (isset($childNode->tagName) ) {
                    $content = $content.$this->getParagraphs($childNode);
                }
            }
            if ($currentTag =="blockquote" ) {
                $content .= "</blockquote>";
            }
        } else {

            // if there is a blockquote but it does not contain any further nodes, then do this simply
            if ($currentTag =="blockquote" ) {
                $content .= "<blockquote>";
                $content .= $DOMNode->nodeValue;
                $content .= "</blockquote>";
            }
        }
        return $content;
    }

    /**
     * will remove junk (injected javascript, small images) from input element
     * @param DOMNode $DOMNode
     */
    private function processContent (DOMNode $DOMNode) {
        if ($this->getDebug()) {
            echo "</br></br><h1>Found from: ".$DOMNode->getNodePath()."</h1><p>".$DOMNode->textContent."</p>";
            // next get only the content of <p> elements found under this branch
            echo "</br></br><h1>Filtered Content (only text from paragraphs kept)</h1>";
        }
        if ( isset($DOMNode->tagName) ) {
            $this->page->setContent( $this->getParagraphs($DOMNode) );
        }
    }

    /**
     * @param DOMNode $DOMNode
     */
    private function parseHtmlHeader (DOMNode $DOMNode) {
        $childNodes = $DOMNode->childNodes;
        foreach ( $childNodes as $childNode) {
            if ( isset($childNode->tagName) ) {
                if ($childNode->tagName == "title") {
                    $this->page->setTitle( $childNode->nodeValue );
                }
                if ($childNode->tagName == "meta" && $childNode->hasAttributes() && null !== $childNode->attributes->getNamedItem("name") ) {
                    if ($childNode->attributes->getNamedItem("name")->nodeValue == "title") {
                        $this->page->setTitle( $childNode->attributes->getNamedItem("content")->nodeValue );
                    }
                    if ($childNode->attributes->getNamedItem("name")->nodeValue == "author") {
                        $this->page->setAuthor( $childNode->attributes->getNamedItem("content")->nodeValue );
                    }
                }
            }
        }
    }

    /**
     * @param DOMNode $rootDOM
     * @param DOMXPath    $rootXpath
     * @return array
     */
    private function countParagraphs(DOMNode $rootDOM,DOMXPath $rootXpath) {
        $paragraphCounts = array();
        foreach ($rootDOM->childNodes as $childNode) {
            if (isset($childNode->tagName) && $childNode->tagName == "head") {
                $this->parseHtmlHeader($childNode);
            }
            $childNodeLocation = $childNode->getNodePath();
            $childNodeParagraphs = $rootXpath->query('.//p', $childNode)->length;
            if ($this->getDebug()) {
                echo "<p>No of sub elements: ".$childNodeParagraphs."</p>";
                echo "<p> Location: ".$childNodeLocation."</p>";
            }
            array_push($paragraphCounts, $childNodeParagraphs);
        }
        if ($this->getDebug()) {
            echo "</br>";
        }
        return $paragraphCounts;
    }

    /**
     * check the nodes at each level and follow the one which had the highest no. of <p> within
     * @param DOMNode $rootDOM
     * @param DOMXPath    $rootXpath
     * @param int       $lastHighest
     */
    private function checkNode(DOMNode $rootDOM, DOMXPath $rootXpath,$lastHighest) {
        $paragraphCounts = $this->countParagraphs($rootDOM,$rootXpath);

        // if more than 50% less paragraphs, send parentNode to be output
        if (max($paragraphCounts) < (0.5 * $lastHighest) ) {
            // WE HAVE FOUND THE ELEMENT CONTAINING CONTENT
            $this->processContent($rootDOM);
        } else {
            $lastHighest = max($paragraphCounts);
            if ($this->getDebug()) {
                echo "<p>From Above. The highest no of p were found in index no:".($this->findHighestIndex($paragraphCounts)+1).". With a total of ".$lastHighest." paragraphs.</p>";
                echo "<p>Paragraph counts: ";
                foreach($paragraphCounts as $pCount) {
                    echo $pCount, ', ';
                }
                echo "</p><br></br>";
            }
            $index_highest_pcount = $this->findHighestIndex($paragraphCounts);
            $this->removeJunk( $rootDOM->childNodes->item($index_highest_pcount) );
            if ( $rootDOM->childNodes->item($index_highest_pcount)->hasChildNodes() ) {
                $this->checkNode( $rootDOM->childNodes->item($index_highest_pcount), $rootXpath, $lastHighest);
            }
        }
    }

    /**
     * @param string $header
     * @return string|null
     */
    private function parseHeaderLocation($header) {
        $pattern = "/^Location:\s*(.*)$/i";
        $location_headers = preg_grep($pattern, $header);
        $array_values_location = array_values($location_headers);

        if (!empty($location_headers) && preg_match($pattern, $array_values_location[0], $matches)){
            return $matches[1];
        }
    }

    /**
     * file_get_contents but includes real browser like user agent
     * @param string $url
     * @return Response
     */
    private static function fileGetContentsHeaders(string $url): Response {
        // Create a stream
        $opts = array(
            'http'=>array(
                'method'=>"GET",
                'header'=>"Accept-language: en\r\n" .
                    "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36\r\n"
            )
        );

        $context = stream_context_create($opts);

        $body = file_get_contents($url,false, $context);
        if ($body === false) {
            $body = null;
        }
        $header = [];
        // file_get_contents populates $http_response_header on response, this is one of the parts of php that really sucks
        if (!empty($http_response_header)) {
            $header = $http_response_header;
        }
        return new Response($body,$header);
    }

    /**
     * download page from $url and load into $doc
     * @param DOMDocument $doc
     * @param string $url
     */
    private function downloadArticle(DOMDocument $doc,$url) {
        // validate url is actually a url
        if (filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED | FILTER_FLAG_HOST_REQUIRED) === false) {
            // failed validation
            $this->page->addError("input url failed validation, please make sure it is valid before trying again");
            return;
        }

        $response = self::fileGetContentsHeaders($url);
        $actualpage = $response->body;
        if (! @$doc->loadHTML(mb_convert_encoding($actualpage,'HTML-ENTITIES',"auto")) ) {
            $this->page->addError("failed to download page");
        }

        // determine current page url
        $location = $this->parseHeaderLocation($response->header);
        if ($location) {
            $this->location =  $location;
        } else {
            // did not end up following redirects, so just set to original request location
            $this->location = $url;
        }
    }

    /**
     * determine how long it will take to read the article in minutes
     * @param string $content
     * @return float|null
     */
    private function calculateReadingTime($content): ?float {
        $reader_words_per_min = 300;
        $num_words = str_word_count( strip_tags( strtolower($content) ), 0);
        $reading_time = null;
        if ($num_words > 0) {
            $reading_time = $num_words / $reader_words_per_min;
        }
        return $reading_time;
    }

    /**
     * parse the article in doc
     * @param DOMDocument $doc
     */
    private function parseArticle (DOMDocument $doc) {
        $doc->encoding = 'utf-8'; // TODO: implement better website encoding detection
        $xpath = new DOMXPath($doc);

        $tags = $this->getTags($doc);
        if ($tags !== null) {
            $this->page->setTags($tags);
        }

        $this->removeJunk($doc);
        if ($doc->hasChildNodes()) {
            $this->checkNode($doc,$xpath,0);
        }

        if (strlen($this->page->getContent()) == 0) {
            $this->page->addError("failed to find article content");
        }

        // determine reading time
        $this->page->setReadingMins( $this->calculateReadingTime($this->page->getContent()) );
    }

    /**
     * Returns an DOMNode that has the tag specified from children directly under, not recursive
     * @param string $tagName
     * @param DOMNode $DOMNode
     * @return DOMNode|null
     */
    private static function getElementByTagNameInChildNodes($tagName, DOMNode $DOMNode) {
        foreach($DOMNode->childNodes as $childNode) {
            if (isset($childNode->tagName) && $childNode->tagName === $tagName) {
                return $childNode;
            }
        }
    }

    private function getTags(DOMDocument $rootNode): ?array {
        // example: <meta property="article:tag" content="flight attendant,Alitalia">
        $tags = $this->getTagsFromMeta($rootNode, 'article:tag');
        if ($tags !== null) return $tags;

        // example: <meta name="keywords" content="flight attendant,Alitalia">
        $tags = $this->getTagsFromMeta($rootNode, 'keywords');
        if ($tags !== null) return $tags;

        return null;
    }

    private function getTagsFromMeta(DOMDocument $rootNode, string $attribute): ?array {
        $rootNode->encoding = 'utf-8';

        $tags = [];
        // search for html header (where amp links are found)
        $html = Pagescraper::getElementByTagNameInChildNodes('html', $rootNode);
        if ($html === null) return $tags;
        $head = Pagescraper::getElementByTagNameInChildNodes('head', $html);
        if ($head === null) return $tags;
        // next search for amp link
        foreach ($head->childNodes as $metadata) {
            if (isset($metadata->tagName) && $metadata->tagName === 'meta' ) {
                if ($metadata->getAttribute('name') === $attribute) {
                    $rawTags = $metadata->getAttribute('content');
                    if ($this->getDebug()) {
                        echo "Found ".$attribute.": ".$rawTags."\n";
                    }
                    if (!empty($rawTags)) {
                        return explode(',',$rawTags);
                    }
                }
            }
        }


        return null;
    }

    /**
     * Returns url string of amp url if found, otherwise returns null
     * @param DOMDocument $rootNode
     * @return string|null
     */
    private function checkAmpVersion(DOMDocument $rootNode) {
        $rootNode->encoding = 'utf-8';

        // search for html header (where amp links are found)
        $html = Pagescraper::getElementByTagNameInChildNodes('html', $rootNode);
        if ($html === null) return;
        $head = Pagescraper::getElementByTagNameInChildNodes('head', $html);
        if ($head === null) return;
        // next search for amp link
        foreach ($head->childNodes as $metadata) {
            if (isset($metadata->tagName) && $metadata->tagName === 'link' ) {
                if ($metadata->getAttribute('rel') === 'amphtml') {
                    $ampLink = $metadata->getAttribute('href');
                    if ($this->getDebug()) {
                        echo "Found amp link: ".$ampLink."\n";
                    }
                    return $ampLink;
                }
            }
        }


        return null;
    }


    /**
     * @param string $url
     * @return Page
     */
    public function getArticle(string $url): Page {
        $doc = new DOMDocument;
        $doc->preserveWhiteSpace = false;

        $this->downloadArticle($doc,$url);
        // if there is an amp version of the article use that instead (allows bypassing multiple page limits)
        $ampLink = $this->checkAmpVersion($doc);
        if ($ampLink !== null) {
            return $this->getArticle($ampLink);
        }

        $this->parseArticle($doc);

        return $this->page;
    }

    /**
     * @param mixed $url
     * @param bool  $is_academic
     */
    public function getJson($url,$is_academic=false) {
        $page = $this->getArticle($url,$is_academic);
        return json_encode($page->jsonSerialize());
    }
}

?>
