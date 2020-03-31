<?php

namespace Nixes\Pagescraper;

class Blacklist {
  public static function containsJunk($childNode): bool {
    $return = false;

    $junk_attribues = array(
      // these two remove most comment sections
        array('regex' => "/comment/i", 'attribute' => "id"),
        array('regex' => "/comment/i", 'attribute' => "class"),

      // this is an ieee spectrum specific regex
        array('regex' => "/iso-content/i", 'attribute' => "id"),

      // phys org related / recommended stories remover
        array('regex' => "/news-holder/i", 'attribute' => "id")
    );


    foreach($junk_attribues as $junk_attribute) {
      if ( preg_match($junk_attribute["regex"],$childNode->getAttribute($junk_attribute["attribute"])) ) {
        if (isset($GLOBALS["debug"]) && $GLOBALS["debug"]==1) {
          echo "<p>Regex: ".$junk_attribute["regex"]." For Attribute: ".$junk_attribute["attribute"]."  Detected and Removed Element</p>";
        }
        $return = true;
        break;
      }
    }
    return $return;
  }

  public static function containsBadTag ($childNode): bool {
    $return = false;

    $bad_tags = array(
      // asides very often contain js metadata
        'aside',
        'script',

      // we don't really support lists, and they are often a very large source of irrelevant <p> tags
        'ul',
        'ol'
    );

    foreach($bad_tags as $bad_tag) {
      if ( $childNode->tagName == $bad_tag ) {
        $return = true;
        break;
      }
    }
    return $return;
  }
}
?>
