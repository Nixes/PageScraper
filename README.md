# PageScraper
This project aims to be able to robustly grab the main article contents from any content heavy page. This was integrated with my other RSS reader PHP script.

The basic assumption behind the algorithm is that the article/content heavy portion of any site is likely to also be the largest cluster of <p> tags in the document.

The algorithm can be sumarised as follows:

Count total number of paragraphs contained within a page

Go through each of the DOM elements of the current level and count total paragraphs within.

If any DOM element contains greater than 50% of the paragraphs compared to the total, follow it.

Continue in same fashion until all elements counts contain less then 50% paragraphs.

Extract paragraphs and images from this Element and present to suit.
