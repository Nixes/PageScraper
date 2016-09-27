<?php
require 'lib.php';

class TestPageScraper extends PHPUnit_Framework_TestCase {
  public function test_findHighestIndex () {
    $test_array = array(0,2,5,7,9,10,50);
    $correct_result = 6;

    $this->assertEquals($correct_result , findHighestIndex($test_array) );
  }
}
?>
