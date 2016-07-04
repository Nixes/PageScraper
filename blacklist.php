<?php

$junk_attribues = [
  // these two remove most comment sections
  array(regex => "/comment/i", attribute => "id"),
  array(regex => "/comment/i", attribute => "class"),

   // this is an ieee spectrum specific regex
  array(regex => "/iso-content/i", attribute => "id")
]

$bad_tags = [
  "aside",
  "ul",
  "ol"
]

function containsJunk($childNode) {
  foreach($junk_attribues as $junk_attribute) {
    if ( preg_match($junk_attribute["regex"],$childNode->getAttribute($junk_attribute["attribute"])) ) {
      if (isset($GLOBALS["debug"]) && $GLOBALS["debug"]==1) {
        echo "<p>Regex: ".$junk_attribute["regex"]." For Attribute: ".$junk_attribute["attribute"])."  Detected and Removed Element</p>";
      }
      return true;
    } else {
      return false;
    }
  }
}

function containsBadTag ($childNode) {
  foreach($bad_tags as $bad_tag) {
    if ( $childNode->tagName == $bad_tag ) {
      return true;
      break;
    } else {
      return false;
    }
  }
}
?>
