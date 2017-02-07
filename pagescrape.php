<?php
require 'lib.php';

  if ( isset ( $_GET["targetUrl"]) ) {
    // init globals on each run
    $GLOBALS["content"] = "";
    $GLOBALS["error"] = array();

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
      downloadArticle($doc,$_GET["targetUrl"]);
    }
      parseArticle($doc);
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

  if(count($GLOBALS["error"]) > 0 ) {
    echo "<div class='error'>
            <h1>Error</h1>";
    foreach ($GLOBALS["error"] as $error) {
      echo "<p>$error</p>";
    }
    echo  "</div>";
  } else {
    echo $GLOBALS["content"];
  }
?>
</div>

</body>
</html>
