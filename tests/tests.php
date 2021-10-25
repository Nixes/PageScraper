<?php

use Nixes\Pagescraper\Pagescraper;
use PHPUnit\Framework\TestCase;

/**
 * function that uses reflection to get allow executing private methods within an object for testing
 * @param object $obj
 * @param string $method_name
 * @param array $args
 */
function callMethod($obj, $method_name, array $args) {
    $class = new \ReflectionClass($obj);
    $method = $class->getMethod($method_name);
    $method->setAccessible(true);
    return $method->invokeArgs($obj, $args);
}

class TestPageScraper extends TestCase {

    public function test_findHighestIndex () {
        $test_array = array(0,2,5,7,9,10,50);
        $correct_result = 6;

        $pagescraper = new Pagescraper;

        // test private method
        $result = callMethod($pagescraper,'findHighestIndex',array( $test_array ));

        $this->assertEquals($correct_result ,$result );
    }

    public function test_convertRelToAbs () {
        $location = "http://www.someweb.com";
        $test_url = "/something/image.jpg";

        $correct_result = "http://www.someweb.com/something/image.jpg";


        $pagescraper = new Pagescraper;
        $result = callMethod($pagescraper,'convertRelToAbs',array( $test_url,$location ));

        echo "Final url was: ".$result."\n";
        echo "Expected url was: ".$correct_result."\n";
        $this->assertEquals($correct_result , $result );
    }

    public function test_realArticles () {
        $urls =  array(
            "http://spectrum.ieee.org/cars-that-think/transportation/self-driving/california-may-be-making-testing-selfdriving-cars-easier",
            "http://feedproxy.google.com/~r/cnx-software/blog/~3/j4GR4BG3ptY/",
            "http://arstechnica.com/science/2016/10/hurricane-matthew-may-strike-the-florida-space-coast-threaten-iconic-nasa-buildings/",
            "http://arstechnica.com/gadgets/2016/10/galaxy-note-7-recall-part-2-samsung-admits-replacement-units-are-unsafe/"
        );
        foreach ($urls as $url) {
            $pageScraper = new Pagescraper;
            echo "Testing against page: ".$url;
            $article = $pageScraper->getArticle($url);
            if ( $article->getErrors() !== null && count($article->getErrors()) > 0 ) {
                echo "Errors: ";
                foreach ($article->getErrors() as $error) {
                    echo "  $error\n";
                }
                echo "\n";
            }
        }
    }
}
?>
