<?php
require 'lib/Page.php';
require 'lib/Pagescraper.php';

  $pageScraper = new Pagescraper;

  if ( isset ( $_GET["targetUrl"]) ) {
    $article_results = $pageScraper->getJson($_GET["targetUrl"]);
  }

  echo $article_results;
?>
