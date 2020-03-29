<?php


namespace Nixes\Pagescraper;


class CachedPagescraper
{
    /**
     * @var string CACHE_PATH
     */
    const CACHE_PATH = './cache';

    /**
     * time that a page is cached in seconds before retrieving a fresh one
     * @var int CACHE_TIME
     */
    const CACHE_TIME = 1800;

    /**
     * @var Pagescraper
     */
    private $pagescraper;

    public function __construct(Pagescraper $pagescraper)
    {
        $this->pagescraper = $pagescraper;
    }

    public function getPage(string $url) {
        $encoded_url = base64_encode($url);
        $cached_path = self::CACHE_PATH.'/'.$encoded_url.'.json';

        // check file exists
        if (is_file( $cached_path ) ) {
            // see how old the file is
            $time_lapse = (strtotime("now") - filemtime($cached_path));
            // if it was not too old
            if ($time_lapse < self::CACHE_TIME) {
                // return the cache files contents
                $cached_article =  Page::deserialize( file_get_contents($cached_path) );
                return $cached_article;
            }
        }

        $new_article = $this->pagescraper->getArticle($url);

        // and save it
        file_put_contents($cached_path, json_encode($new_article) );
        return $new_article;
    }
}