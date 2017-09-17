<?php
require 'lib.php';

  if ( isset ( $_GET["debug"]) ) {
    if ( $_GET["debug"] == true){
      $GLOBALS["debug"] = 1;
    }
  }
  $article = getArticle($_GET["targetUrl"] ,$_GET["academic"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" type="text/css" href="styles/main.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    echo "<title>".$article["title"]."</title>";
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

  if ( $article["reading_mins"] != null ) {
    echo "<h2>".round($article["reading_mins"],1)." minutes read</h2>";
    echo "<hr>";
  }
  if ( $article["title"] != null ) {
    echo "<h1>".$article["title"]."</h1>";
    echo "<hr>";
  }
  if ( $article["author"] != null ) {
    echo "<h2>by ".$article["author"]."</h2>";
    echo "<hr>";
  }

  if(count($article["error"]) > 0 ) {
    echo "<div class='error'>
            <h1>Error</h1>";
    foreach ($article["error"] as $error) {
      echo "<p>$error</p>";
    }
    echo  "</div>";
  } else {
    echo $article["content"];
  }
?>
</div>

</body>
</html>
