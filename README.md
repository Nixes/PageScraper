# PageScraper [![Build Status](https://travis-ci.org/Nixes/PageScraper.svg?branch=master)](https://travis-ci.org/Nixes/PageScraper)
This project aims to be able to robustly grab the main article contents from any content heavy page.

It generates a very minimal reading page with only; one highly compressed repeating background image, no fonts, no js, one shared css file. It also has mobile view activated automatically based on screen width (using a css media query). All this in less then 30KB uncompressed.


Basic usage:

```
pagescrape.php?targetUrl=http://www.somenewssitehere.com/somearticle
```

## Algorithm
The basic assumption behind the algorithm is that the article/content heavy portion of any site is likely to also be the largest cluster of paragraph tags in the document.

The algorithm can be sumarised as follows:
<ul>
<li>Count total number of paragraphs contained within a page.</li>
<li>Iterate through each of the DOM elements of the current level and count total paragraphs within.</li>
<li>If any DOM element contains greater than 50% of the paragraphs compared to the total, follow it.</li>
<li>Continue in same fashion until all node counts contain less then 50% paragraphs.</li>
<li>Extract paragraphs and images from this node and present to suit.</li>
</ul>


## Common Issues and Fixes

### NGINX Error: "upstream sent too big header while reading response header from upstream"
This signifies that the page that was requested for processing was larger than allowed by your fastCGI buffer size.

To fix this increase fastcgi_buffer_size in your nginx config
