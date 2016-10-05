<?php
require 'blacklist.php';

  // returns index of array element that contains the largest value
  function findHighestIndex($arr) {
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

  // this function differs from other recursive functions below in that it actually remove nodes that fit a certain criteria
  function removeJunk($DOMNode) {
    if ($DOMNode->hasChildNodes()) {
      $childNodes = $DOMNode->childNodes;
      for ($i=0; $i < $childNodes->length; $i++ ) { // todo: optimise by copying to a list and running through that as the original list of child nodes stays the same despite elements being deleted, this results in offsets or elements being checked for being empty
        $childNode = $childNodes->item($i);
        if ($childNode->hasAttributes() && containsJunk($childNode) ) {
          $DOMNode->removeChild($childNode);
          break;
        }
        if ( isset($childNode->tagName) && containsBadTag($childNode) ) {
          $DOMNode->removeChild($childNode);
          break;
        }
        removeJunk($childNode);
      }
    }
  }

  // a function that converts a relative style url to an absolute one, works on resources and urls
  function convertRelToAbs($url) {
    $path = $url;
    if (!empty($path)) {
      if ((substr($url, 0, 7) == 'http://') || (substr($url, 0, 8) == 'https://')) {
        // url is absolute
        return $url;
      } else {
        // url is relative
        $parsed_url = parse_url( $GLOBALS["location"] );
        return $parsed_url['scheme'].'://'.$parsed_url['host']. $path;
      }
    }
  }

  function getParagraphs($DOMNode) {
    $currentTag = $DOMNode->tagName;
    $content = "";

    //whitelist
    if ($currentTag =="p" ) {
      $content = "<p>";
      // this loop here check for any other inline formating within the paragraph
      foreach($DOMNode->childNodes as $node) {
        if (isset($node->tagName) && $node->tagName =="a") {
          $content .= "<a href='". convertRelToAbs( $node->attributes->getNamedItem("href")->nodeValue ) ."'>".$node->nodeValue."</a>";
        } else {
          $content .= $node->nodeValue;
        }
      }
      $content .= "</p>";
    }
    if ($currentTag =="img" ) {
      $content = "<div class='img_container'><img src='". convertRelToAbs( $DOMNode->attributes->getNamedItem("src")->nodeValue ) ."' style='vertical-align:middle'></img></div>";
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
          $content = $content.getParagraphs($childNode);
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

  // will remove junk (injected javascript, small images) from input element
  function processContent ($DOMNode) {
    if (isset($GLOBALS["debug"]) && $GLOBALS["debug"]==1) {
      echo "</br></br><h1>Found from: ".$DOMNode->getNodePath()."</h1><p>".$DOMNode->textContent."</p>";
      // next get only the content of <p> elements found under this branch
      echo "</br></br><h1>Filtered Content (only text from paragraphs kept)</h1>";
    }
    if ( isset($DOMNode->tagName) ) {
      $GLOBALS["content"] = getParagraphs($DOMNode);
    }
  }

  function parseHtmlHeader ($DOMNode) {
    $childNodes = $DOMNode->childNodes;
    foreach ( $childNodes as $childNode) {
      if ( isset($childNode->tagName) ) {
        if ($childNode->tagName == "title") {
          $GLOBALS["title"] = $childNode->nodeValue;
        }
        if ($childNode->tagName == "meta" && $childNode->hasAttributes()) {
          if ($childNode->attributes->getNamedItem("name")->nodeValue == "title") {
            $GLOBALS["title"] = $childNode->attributes->getNamedItem("content")->nodeValue;
          }
          if ($childNode->attributes->getNamedItem("name")->nodeValue == "author") {
            $GLOBALS["author"] = $childNode->attributes->getNamedItem("content")->nodeValue;
          }
        }
      }
    }
  }

function countParagraphs($rootDOM,$rootXpath) {
  $paragraphCounts = array();
  try {
    foreach ($rootDOM->childNodes as $childNode) {
      if (isset($childNode->tagName) && $childNode->tagName == "head") {
        parseHtmlHeader($childNode);
      }
      $childNodeLocation = $childNode->getNodePath();
      $childNodeParagraphs = $rootXpath->query('.//p', $childNode)->length;
      if (isset($GLOBALS["debug"]) && $GLOBALS["debug"]==1) {
        echo "<p>No of sub elements: ".$childNodeParagraphs."</p>";
        echo "<p> Location: ".$childNodeLocation."</p>";
      }
      array_push($paragraphCounts, $childNodeParagraphs);
    }
    if (isset($GLOBALS["debug"]) && $GLOBALS["debug"]==1) {
      echo "</br>";
    }
    return $paragraphCounts;
  } catch(Exception $e) {
    echo 'Paragraph count exception: ',  $e->getMessage(), "\n";
    if ($rootDOM->hasChildNodes()) {
      echo '$rootDOM had child nodes';
    } else {
      echo '$rootDOM DID NOT HAVE child nodes';
    }
    echo 'Dumping $rootDOM';
    var_dump($rootDOM);
  }
}

  // check the nodes at each level and follow the one which had the highest no. of <p> within
  function checkNode($rootDOM,$rootXpath,$lastHighest) {
      $paragraphCounts = countParagraphs($rootDOM,$rootXpath);

      // if more than 50% less paragraphs, send parentNode to be output
      if (max($paragraphCounts) < (0.5 * $lastHighest) ) {
        // WE HAVE FOUND THE ELEMENT CONTAINING CONTENT
        processContent($rootDOM);
      } else {
        $lastHighest = max($paragraphCounts);
        if (isset($GLOBALS["debug"]) && $GLOBALS["debug"]==1) {
          echo "<p>From Above. The highest no of p were found in index no:".(findHighestIndex($paragraphCounts)+1).". With a total of ".$lastHighest." paragraphs.</p>";
          echo "<p>Paragraph counts: ";
          foreach($paragraphCounts as $pCount) {
            echo $pCount, ', ';
          }
          echo "</p><br></br>";
        }
        $index_highest_pcount = findHighestIndex($paragraphCounts);
        removeJunk( $rootDOM->childNodes->item($index_highest_pcount) );
        if ( $rootDOM->childNodes->item($index_highest_pcount)->hasChildNodes() ) {
          checkNode( $rootDOM->childNodes->item($index_highest_pcount), $rootXpath, $lastHighest);
        }
      }
  }

  function parseHeaderLocation($header) {
    $pattern = "/^Location:\s*(.*)$/i";
    $location_headers = preg_grep($pattern, $header);
    $array_values_location = array_values($location_headers);

    if (!empty($location_headers) && preg_match($pattern, $array_values_location[0], $matches)){
      return $matches[1];
    }
  }

  // convert raw http headers to associative array
  function parseHeaders( $headers ) {
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

  // function shamelessly taken from stackoverflow: https://stackoverflow.com/questions/22469662/fatal-error-call-to-undefined-function-post
  // used due to the lack of curl on this server which I am in no position to fix
  function http_post_flds($url, $data, $cookie,$headers=null) {
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
    var_dump( parseHeaders($http_response_header) );
    return stream_get_contents($fp);
  }

  function http_get($url,$cookie) {
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
    var_dump( parseHeaders($http_response_header) );
    return stream_get_contents($fp);
  }

  function getCookie($targetUrl, $data) {
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
    $headers = parseHeaders($http_response_header);
    if (isset($headers["Set-Cookie"])) {
      return array(
        cookie => $headers["Set-Cookie"],
        redirect => $headers["Location"]
      );
    } else {
      return null;
    }
  }

  function getAcademicPage ($targetUrl) {
    // TODO: ask for user credentials before supplying access to academic content (to prevent abuse)
    // magic code removed for licensing / legal reasons
    $response = http_get($targetUrl); // should now follow redirects
    //echo "<p>Response was: ".$response."</p>";
    return $response;
  }

  // download page from $url and load into $doc
  function downloadArticle ($doc,$url) {
    $actualpage = file_get_contents($url);
    if (! @$doc->loadHTML(mb_convert_encoding($actualpage,'HTML-ENTITIES',"auto")) ) {
      $GLOBALS["error"] = "failed to download page";
    }

    // determine current page url
    $location = parseHeaderLocation($http_response_header);
    if ($location) {
      $GLOBALS["location"] =  $location;
    } else {
      // did not end up following redirects, so just set to original request location
      $GLOBALS["location"] = $url;
    }
  }

  // parse the article in doc
  function parseArticle ($doc) {
    $doc->encoding = 'utf-8'; // TODO: implement better website encoding detection
    $xpath = new DOMXpath($doc);

    removeJunk($doc);
    if ($doc->hasChildNodes()) {
      checkNode($doc,$xpath,0);
    }

    if (strlen($GLOBALS["content"]) == 0) {
      $GLOBALS["error"] = "failed to find article content";
    }
  }
?>
