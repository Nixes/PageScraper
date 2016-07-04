<?php

function containsJunk($childNode) {
  $return = false;

  $junk_attribues = [
    // these two remove most comment sections
    array('regex' => "/comment/i", 'attribute' => "id"),
    array('regex' => "/comment/i", 'attribute' => "class"),

     // this is an ieee spectrum specific regex
    array('regex' => "/iso-content/i", 'attribute' => "id")
  ];


  foreach($junk_attribues as $junk_attribute) {
    if ( preg_match($junk_attribute["regex"],$childNode->getAttribute($junk_attribute["attribute"])) ) {
      if (isset($GLOBALS["debug"]) && $GLOBALS["debug"]==1) {
        echo "<p>Regex: ".$junk_attribute["regex"]." For Attribute: ".$junk_attribute["attribute"]."  Detected and Removed Element</p>";
      }
      $return = true;
    }
  }
  return $return;
}

function containsBadTag ($childNode) {
  $return = false;

  $bad_tags = [
    // asides very often contain js metadata
    'aside',

    // we don't really support lists, and they are often a very large source of irrelevant <p> tags
    'ul',
    'ol'
  ];

  foreach($bad_tags as $bad_tag) {
    if ( $childNode->tagName == $bad_tag ) {
      $return = true;
    }
  }
  return $return;
}
?>
