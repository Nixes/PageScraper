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
    $test_url = "./something/image.jpg";

    $correct_result = "http://www.someweb.com/something/image.jpg";

    $result = convertRelToAbs($test_url);

    echo "Final url was: ".$result."\n";
    echo "Expected url was: ".$correct_result."\n";
  }
}
?>
