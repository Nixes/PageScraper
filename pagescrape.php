<?php
require 'lib.php';

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
      if (! @$doc->loadHTML(mb_convert_encoding($actualpage,'HTML-ENTITIES',"auto")) ) {
        $GLOBALS["error"] = "failed to download page";
      }

      // determine current page url
      $location = parseHeaderLocation($http_response_header);
      if ($location) {
        $GLOBALS["location"] =  $location;
      } else {
        // did not end up following redirects, so just set to original request location
        $GLOBALS["location"] = $_GET["targetUrl"];
      }
    }
    $doc->encoding = 'utf-8'; // TODO: implement better website encoding detection
    $xpath = new DOMXpath($doc);
    checkNode($doc,$xpath,0);

    if (strlen($GLOBALS["content"]) == 0) {
      $GLOBALS["error"] = "failed to obtain article";
    }
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
                  <input type=hidden name='item' value='".$GLOBALS["location"]."' ></input>
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

  if( isset($GLOBALS["error"]) ) {
    echo "<div class='error'>
            <h1>Error</h1>
            <p>".$GLOBALS["error"]."</p>
          </div>";
  } else {
    echo $GLOBALS["content"];
  }
?>
</div>

</body>
</html>
