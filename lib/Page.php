<?php

namespace Nixes\Pagescraper;
use JsonSerializable;

/**
 * Page
 */
class Page implements JsonSerializable {
    /**
     * @var string $location
     */
    private $location;
    /**
     * @var string $author
     */
    private $author;
    /**
     * @var string $content
     */
    private $content;
    /**
     * @var string $title
     */
    private $title;

    /**
     * @var int|null $readingMins
     */
    private $readingMins;

    /**
     * @var string[] $errors
     */
    private $errors;

    /**
     * @return string
     */
    public function getLocation() {
        return $this->location;
    }

    /**
     * @param string $location
     *
     * @return static
     */
    public function setLocation($location) {
        $this->location = $location;
        return $this;
    }

    /**
     * @return string
     */
    public function getAuthor() {
        return $this->author;
    }

    /**
     * @param string $author
     *
     * @return static
     */
    public function setAuthor($author) {
        $this->author = $author;
        return $this;
    }

    /**
     * @return string
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return static
     */
    public function setContent($content) {
        $this->content = $content;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return static
     */
    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }

    /**
     * @return int
     */
    public function getReadingMins() {
        return $this->readingMins;
    }

    /**
     * @param int $readingMins
     *
     * @return static
     */
    public function setReadingMins(?int $readingMins){
        $this->readingMins = $readingMins;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * @param string[] $errors
     *
     * @return static
     */
    public function setErrors(array $errors) {
        $this->errors = $errors;
        return $this;
    }

    /**
     * @param string $error
     */
    public function addError($error) {
        $this->errors[] = $error;
    }

    /**
     * returns array of Page object properties ready for json encoding
     * @return array
     */
    public function jsonSerialize() {
        $result = get_object_vars($this);
        // since content is html we need to encode with bas64 so we don't need to worry about complex escaping
        $encoded_content = base64_encode($result['content']);
        $result['content'] = $encoded_content;
        return $result;
    }

    /**
     * Takes in raw json string and returns an instance of this object
     * @param string|array $json
     * @return Page
     */
    public static function deserialize($json) {
        $className = get_called_class();
        $classInstance = new $className();
        if (is_string($json))
            $json = json_decode($json);

        foreach ($json as $key => $value) {
            if (!property_exists($classInstance, $key)) continue;
            if ($key === 'content') {
                $value = base64_decode($value);
            }
            $classInstance->$key = $value;
        }

        return $classInstance;
    }
}

?>
