<?php

namespace Nixes\Pagescraper;
/**
 * Pagescraper
 */
class Pagescraper {

  /**
   * @var string CACHE_PATH
   */
  const CACHE_PATH = './cache';

  /**
   * time that a page is cached in seconds before retrieving a fresh one
   * @var int CACHE_TIME
   */
  const CACHE_TIME = 1800;

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
    for ( $i=0; $i < count($arr); $i++ ) {
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
        $result = $parsed_url['scheme'].'://'.$parsed_url['host']. $path;
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
   * convert raw http headers to associative array
   * @param string[]  $headers
   * @return array
   */
  private function parseHeaders( $headers ) {
    $head = array();
    foreach( $headers as $k=>$v ) {
      $t = explode( ':', $v, 2 );
      if( isset( $t[1] ) )
        $head[ trim($t[0]) ] = trim( $t[1] );
      else {
        $head[] = $v;
        if( preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#",$v, $out ) )
          $head['reponse_code'] = intval($out[1]);
      }
    }
    return $head;
  }

  /**
   * function shamelessly taken from stackoverflow: https://stackoverflow.com/questions/22469662/fatal-error-call-to-undefined-function-post
   * used due to the lack of curl on this server which I am in no position to fix
   * @param mixed $url
   * @param mixed $data
   * @param mixed $cookie
   * @param null  $headers
   */
  private function httpPostFlds($url, $data, $cookie,$headers=null) {
    $data = http_build_query($data);
    $opts = array('http' => array(
      'method' => 'POST',
      'max_redirects' => '10',
      'cookie' => $cookie,
      'content' => $data
      ));

    if($headers) {
      $opts['http']['header'] = $headers;
    }
    $st = stream_context_create($opts);
    $fp = fopen($url, 'rb', false, $st);

    if(!$fp) {
      return false;
    }
    var_dump( $this->parseHeaders($http_response_header) );
    return stream_get_contents($fp);
  }

  /**
   * @param mixed $url
   * @param mixed $cookie
   */
  private function httpGet($url,$cookie) {
    $opts = array('http' => array(
      'method' => 'GET',
      'max_redirects' => '10',
      'header' => "Accept-language: en\r\n"."Cookie: ".$cookie."\r\n"
      ));
    $st = stream_context_create($opts);
    $fp = fopen($url, 'rb', false, $st);

    if(!$fp) {
      return false;
    }
    var_dump( $this->parseHeaders($http_response_header) );
    return stream_get_contents($fp);
  }

  /**
   * @param string $targetUrl
   * @param mixed $data
   * @return array|null
   */
  private function getCookie($targetUrl, $data) {
    $data = http_build_query($data);
    $opts = array('http' => array(
      'method' => 'POST',
      'max_redirects' => '10',
      'content' => $data
    ));

    if($headers) {
      $opts['http']['header'] = $headers;
    }
    $st = stream_context_create($opts);
    $fp = fopen($targetUrl, 'rb', false, $st);
    if(!$fp) {
      return false;
    }
    $headers = $this->parseHeaders($http_response_header);
    if (isset($headers["Set-Cookie"])) {
      return array(
        $cookie => $headers["Set-Cookie"],
        $redirect => $headers["Location"]
      );
    } else {
      return null;
    }
  }

  /**
   * @param mixed $targetUrl
   */
  private function getAcademicPage($targetUrl) {
    // TODO: ask for user credentials before supplying access to academic content (to prevent abuse)
    // magic code removed for licensing / legal reasons
    $response = $this->httpGet($targetUrl); // should now follow redirects
    //echo "<p>Response was: ".$response."</p>";
    return $response;
  }

  /**
   * download page from $url and load into $doc
   * @param DOMDocument $doc
   * @param string $url
   */
  function downloadArticle(DOMDocument $doc,$url) {
    // validate url is actually a url
    if (filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED | FILTER_FLAG_HOST_REQUIRED) === false) {
      // failed validation
      $this->page->addError("input url failed validation, please make sure it is valid before trying again");
      return;
    }

    $actualpage = file_get_contents($url);
    if (! @$doc->loadHTML(mb_convert_encoding($actualpage,'HTML-ENTITIES',"auto")) ) {
      $this->page->addError("failed to download page");
    }

    // determine current page url
    $location = $this->parseHeaderLocation($http_response_header);
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
 * @return float
 */
  private function calculateReadingTime($content) {
    $reader_words_per_min = 300;
    $num_words = str_word_count( strip_tags( strtolower($content) ), 0);
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
 * @param string   $tagName
 * @param DOMNode $DOMNode
 * @return DOMNode|null
 */
  private function getElementByTagNameInChildNodes($tagName,DOMNode $DOMNode) {
      foreach($DOMNode->childNodes as $childNode) {
          if (isset($childNode->tagName) && $childNode->tagName === $tagName) {
              return $childNode;
          }
      }
  }

/**
 * Returns url string of amp url if found, otherwise returns null
 * @param DOMDocument $rootNode
 * @return string|null
 */
  private function checkAmpVersion(DOMDocument $rootNode) {
      $rootNode->encoding = 'utf-8';

      // search for html header (where amp links are found)
      $html = $this->getElementByTagNameInChildNodes('html',$rootNode);
      if ($html === null) return;
      $head = $this->getElementByTagNameInChildNodes('head',$html);
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
   * @param bool  $is_academic
   * @return Page
   */
  function getNewArticle($url,$is_academic=false) {

    $doc = new DOMDocument;
    $doc->preserveWhiteSpace = false;

    if ( $is_academic == true ) {
      @$doc->loadHTML( $this->getAcademicPage($url) ); // we don't want to see every parse fail
    } else {
      $this->downloadArticle($doc,$url);
    }
    // if there is an amp version of the article use that instead (allows bypassing mulitple page limits)
    $ampLink = $this->checkAmpVersion($doc);
    if ($ampLink !== null) {
        return $this->getNewArticle($ampLink ,$is_academic);
    }

    $this->parseArticle($doc);

    return $this->page;
  }


  /**
   * function to check for cached version of aricle
   * @param string $url
   * @return Page
   */
  public function getArticle($url) {
    $encoded_url = base64_encode($url);
    $cached_path = Pagescraper::CACHE_PATH.'/'.$encoded_url.'.json';

    // check file exists
    if (is_file( $cached_path ) ) {
      // see how old the file is
      $time_lapse = (strtotime("now") - filemtime($cached_path));
      // if it was not too old
      if ($time_lapse < Pagescraper::CACHE_TIME) {
        // return the cache files contents
        $cached_article =  Page::deserialize( file_get_contents($cached_path) );
        return $cached_article;
      }
    }

    // if there was no file or the cache was old, then go get the article
    $new_article = $this->getNewArticle($url);
    // and save it
    file_put_contents($cached_path, json_encode($new_article) );

    return $new_article;
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
