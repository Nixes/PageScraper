# PageScraper [![Build Status](https://travis-ci.org/Nixes/PageScraper.svg?branch=master)](https://travis-ci.org/Nixes/PageScraper)
This project aims to be able to robustly grab the main article contents from any content heavy page.

It generates a very minimal reading page with only; one highly compressed repeating background image, no fonts, no js, one shared css file. It also has mobile view activated automatically based on screen width (using a css media query). All this in less then 30KB uncompressed.


Basic usage:

```
pagescrape.php?targetUrl=http://www.somenewssitehere.com/somearticle
```

## Common Issues and Fixes

### NGINX Error: "upstream sent too big header while reading response header from upstream"
This signifies that the page that was requested for processing was larger than allowed by your fastCGI buffer size.

To fix this increase fastcgi_buffer_size in your nginx config
