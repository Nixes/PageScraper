<?php
require 'lib.php';

  // init globals on each run
  $GLOBALS["content"] = "";
  $GLOBALS["error"] = array();

  if ( isset ( $_GET["targetUrl"]) ) {

    $doc = new DOMDocument;
    $doc->preserveWhiteSpace = FALSE;

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

  $article_results = array(
    'readings_mins' => $GLOBALS["reading_mins"],
    'title' => $GLOBALS["title"],
    'author' => $GLOBALS["author"],
    'error' => $GLOBALS["error"],
    'content' => $GLOBALS["content"],
  );

  echo json_encode($article_results);
?>
