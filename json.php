<?php
require 'lib.php';

  if ( isset ( $_GET["targetUrl"]) ) {
    $article_results = getJson($_GET["targetUrl"]);
  }

  echo $article_results;
?>
