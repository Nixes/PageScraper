<?php
require_once 'PHPUnit/Autoload.php';
require 'lib.php';

use PHPUnit\Framework\TestCase;

class TestPageScraper extends TestCase {
  public function Test_findHighestIndex () {
    $test_array = array(0,2,5,7,9,10,50);
    $correct_result = 6;

    $this->assertEquals($correct_result , findHighestIndex($test_array) );
  }
}
?>
