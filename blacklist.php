<?php

// format [regex,attribute]

$junk_attribues = [
  array(regex => "/comment/i", attribute => "id"),
  array(regex => "/comment/i", attribute => "class"),
  array(regex => "/iso-content/i", attribute => "id") // this is ieee spectrum specific
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


$bad_tags = [
  "aside",
  "ul",
  "ol"
]

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
