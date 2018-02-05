<?php
require 'lib/Page.php';
require 'lib/Pagescraper.php';

$pageScraper = new Pagescraper;

  if ( isset ( $_GET["debug"]) ) {
    if ( $_GET["debug"] == true){
      $pageScraper->setDebug(true);
    }
  }
  $article = $pageScraper->getArticle($_GET["targetUrl"] ,$_GET["academic"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" type="text/css" href="styles/main.css">
    <script src="js/articleSpeechSynthesis.js" charset="utf-8"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    echo "<title>".$article->getTitle()."</title>";
     ?>
</head>

<body>

    <dialog id="favDialog" open>
        <form action="javascript:void(0);">
            <input type="text" class="txt">
            <div>
                <label for="rate">Rate</label><input type="range" min="0.5" max="2" value="1" step="0.1" id="rate">
                <div class="rate-value">1</div>
                <div class="clearfix"></div>
            </div>
            <div>
                <label for="pitch">Pitch</label><input type="range" min="0" max="2" value="1" step="0.1" id="pitch">
                <div class="pitch-value">1</div>
                <div class="clearfix"></div>
            </div>
            <select>

            </select>
            <div class="controls">
                <button id="play" type="submit">Play</button>
            </div>
        </form>
    </dialog>


<div id="document">
<?php
  echo "<a href='".$_GET["targetUrl"]."' id=origin_page>Original Page</a>";
  echo "<form action='../../private/readinglist/itemQuery.php' method='post'>
                  <input type=hidden name='itemsRequestType' value='add' ></input>
                  <input type=hidden name='item' value='".$article->getLocation()."' ></input>
                  <button id='read_it_later_button' type='submit' value='Read It Later'>Read It Later</button>
                  </form><div class='clearfloat'></div>";

  if ( $article->getReadingMins() !== null ) {
    echo "<h2>".round($article->getReadingMins(),1)." minutes read</h2>";
    echo "<hr>";
  }
  if ( $article->getTitle() !== null ) {
    echo "<h1>".$article->getTitle()."</h1>";
    echo "<hr>";
  }
  if ( $article->getAuthor() !== null ) {
    echo "<h2>by ".$article->getAuthor()."</h2>";
    echo "<hr>";
  }

  if(count($article->getErrors()) > 0 ) {
    echo "<div class='error'>
            <h1>Error</h1>";
    foreach ($article->getErrors() as $error) {
      echo "<p>$error</p>";
    }
    echo  "</div>";
  } else {
    echo $article->getContent();
  }
?>
</div>

</body>
</html>
