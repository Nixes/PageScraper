<?php
require 'lib.php';

class TestPageScraper extends PHPUnit_Framework_TestCase {
  public function test_findHighestIndex () {
    $test_array = array(0,2,5,7,9,10,50);
    $correct_result = 6;

    $this->assertEquals($correct_result , findHighestIndex($test_array) );
  }

  public function test_convertRelToAbs () {
    $GLOBALS["location"] = "http://www.someweb.com";
    $test_url = "/something/image.jpg";

    $correct_result = "http://www.someweb.com/something/image.jpg";

    $result = convertRelToAbs($test_url);

    echo "Final url was: ".$result."\n";
    echo "Expected url was: ".$correct_result."\n";
    $this->assertEquals($correct_result , $result );
  }

  public function test_realArticles () {
    $urls =  array(
              "http://spectrum.ieee.org/cars-that-think/transportation/self-driving/california-may-be-making-testing-selfdriving-cars-easier",
              "http://feedproxy.google.com/~r/cnx-software/blog/~3/j4GR4BG3ptY/",
              "http://feeds.sciencedaily.com/~r/sciencedaily/top_news/top_science/~3/vGzKzIZOy4E/160930144424.htm", // this test should fail
              "http://arstechnica.com/science/2016/10/hurricane-matthew-may-strike-the-florida-space-coast-threaten-iconic-nasa-buildings/"
            );
    foreach ($urls as $url) {
      echo "Testing against page: ".$url;
      $doc = new DOMDocument;
      $doc->preserveWhiteSpace = FALSE;
      downloadArticle($doc,$url);
      parseArticle($doc);
      if ( isset($GLOBALS["error"]) ) {
        echo "Error: ".$GLOBALS["error"]."\n";
      }
    }
  }
}
?>
