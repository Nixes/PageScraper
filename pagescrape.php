<?php
  // returns index of array that is largest
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
  // TODO: this function should probably check against an external blacklist rather than this inline rubbish.
  function removeJunk($DOMNode) {
    if ($DOMNode->hasChildNodes()) {
      $childNodes = $DOMNode->childNodes;
      //echo "</br>".var_dump($childNodes)."</br>";
      for ($i=0; $i < $childNodes->length; $i++ ) { // todo: optimise by copying to a list and running through that as the original list of child nodes stays the same despite elements being deleted, this results in offsets or elements being checked for being empty
        $childNode = $childNodes->item($i);
        if ($childNode->hasAttributes() ) {
          if (preg_match("/comment/i",$childNode->getAttribute('id')) ) {
            $DOMNode->removeChild($childNode);
            if (isset($GLOBALS["debug"]) && $GLOBALS["debug"]==1) {
              echo "<p>Comments Section ID Detected and Removed at: ".$childNode->getAttribute('id')."</p>";
            }
            break;
          }
          if (preg_match("/comment/i",$childNode->getAttribute('class')) ) {
            $DOMNode->removeChild($childNode);
            if (isset($GLOBALS["debug"]) && $GLOBALS["debug"]==1) {
              echo "<p>Comments Section CLASS Detected and Removed at".$childNode->getAttribute('class')."</p>";
            }
            break;
          }
          // this one is ieee spectrum specific
          if (preg_match("/iso-content/i",$childNode->getAttribute('id')) ) {
            $DOMNode->removeChild($childNode);
            if (isset($GLOBALS["debug"]) && $GLOBALS["debug"]==1) {
              echo "<p>Comments Section CLASS Detected and Removed at".$childNode->getAttribute('id')."</p>";
            }
            break;
          }
        }
        if ( isset($childNode->tagName) ) {
          if ($childNode->tagName =="aside") {
            $DOMNode->removeChild($childNode);
            break;
          }
          else if ($childNode->tagName =="ul") {
            $DOMNode->removeChild($childNode);
            break;
          }
          else if ($childNode->tagName =="ol") {
            $DOMNode->removeChild($childNode);
            break;
          }
        }
        removeJunk($childNode);
      }
    }
  }

  function getParagraphs($DOMNode) {
    $currentTag = $DOMNode->tagName;
    //whitelist
    if ($currentTag =="p" ) {
      $content = "<p>";
      foreach($DOMNode->childNodes as $node) {
        if ($node->tagName =="a") {
          $content .= "<a href='".$node->attributes->getNamedItem("href")->nodeValue."'>".$node->nodeValue."</a>";
        } else {
          $content .= $node->nodeValue;
        }
      }
      $content .= "</p>";
    }
    if ($currentTag =="img" ) {
      $content = "<div class='img_container'><img src='".$DOMNode->attributes->getNamedItem("src")->nodeValue."' style='vertical-align:middle'></img></div>";
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
        $content = $content.getParagraphs($childNode);
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
  function purifyContent ($DOMNode) {
    if (isset($GLOBALS["debug"]) && $GLOBALS["debug"]==1) {
      echo "</br></br><h1>Found from: ".$DOMNode->getNodePath()."</h1><p>".$DOMNode->textContent."</p>";
      // next get only the content of <p> elements found under this branch
      echo "</br></br><h1>Filtered Content (only text from paragraphs kept)</h1>";
    }
    $GLOBALS["content"] = getParagraphs($DOMNode);
  }

  function parseHtmlHeader ($DOMNode) {
    $childNodes = $DOMNode->childNodes;
    for ( $i=0; $i < $childNodes->length; $i++ ) {
      $childNode = $childNodes->item($i);
      if ($childNode->tagName == "title") {
        $GLOBALS["title"] = $childNode->nodeValue;
      }
      if ($childNode->tagName == "meta") {
        if ($childNode->attributes->getNamedItem("name")->nodeValue == "title") {
          $GLOBALS["title"] = $childNode->attributes->getNamedItem("content")->nodeValue;
        }
        if ($childNode->attributes->getNamedItem("name")->nodeValue == "author") {
          $GLOBALS["author"] = $childNode->attributes->getNamedItem("content")->nodeValue;
        }
      }
    }
  }

  // check the nodes at each level and follow the one which had the highest no. of <p> within
  function checkNode($rootDOM,$rootXpath,$lastHighest) {
    if ($rootDOM->hasChildNodes()) {
      removeJunk($rootDOM);
      $childNodes = $rootDOM->childNodes;
      $paragraphCounts = array();
      for ($i =0; $i < $childNodes->length; $i++ ) {
        $childNode = $childNodes->item($i);
        if ($childNode->tagName == "head") {
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
      if (max($paragraphCounts) < (0.5 * $lastHighest) ) { // if more than 50% less paragraphs, send parentNode to be output
        //purifyContent($rootDOM->parentNode); //this works
        purifyContent($rootDOM); //works more reliably, but cuts out header images
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
        checkNode( $childNodes->item(findHighestIndex($paragraphCounts)), $rootXpath, $lastHighest);
      }
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

  if ( isset ( $_GET["targetUrl"]) ) {
    $doc = new DOMDocument;
    $doc->preserveWhiteSpace = FALSE;

    if ( isset ( $_GET["debug"]) ) {
      if ( $_GET["debug"] == true){
        $GLOBALS["debug"] = 1;
      }
    }
    if ( isset ( $_GET["academic"]) ) {
      if ( $_GET["academic"] == true){
        echo "<p>Was an academic source, getting access past pay-wall...</p>";
        //echo "<p>Get Academic Returned: ".getAcademicPage($_GET["targetUrl"])."</p>";
        @$doc->loadHTML( getAcademicPage($_GET["targetUrl"]) ); // we don't want to see every parse fail
      }
    } else {
      $actualpage = file_get_contents($_GET["targetUrl"]);
      @$doc->loadHTML( mb_convert_encoding($actualpage,'HTML-ENTITIES',"auto") );
    }
    $doc->encoding = 'utf-8'; // TODO: implement better website encoding detection
    $xpath = new DOMXpath($doc);
    checkNode($doc,$xpath,0);
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" type="text/css" href="styles/main.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    echo "<title>".$GLOBALS["title"]."</title>";
     ?>
</head>

<body>

<div id="document">
<?php
  echo "<a href='".$_GET["targetUrl"]."' id=origin_page>Original Page</a>";
  echo "<form action='../../private/readinglist/itemQuery.php' method='post'>
                  <input type=hidden name='itemsRequestType' value='add' ></input>
                  <input type=hidden name='item' value='".$_GET["targetUrl"]."' ></input>
                  <button id='read_it_later_button' type='submit' value='Read It Later'>Read It Later</button>
                  </form><div class='clearfloat'></div>";
  if (isset($GLOBALS["title"])) {
    echo "<h1>".$GLOBALS["title"]."</h1>";
    echo "<hr>";
  }
  if (isset($GLOBALS["author"])) {
    echo "<h2>by ".$GLOBALS["author"]."</h2>";
    echo "<hr>";
  }

  echo $GLOBALS["content"];
?>
</div>

</body>
</html>
