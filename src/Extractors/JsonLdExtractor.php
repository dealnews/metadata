<?php

namespace DealNews\Metadata\Extractors;

use \DOMDocument;
use \DOMXPath;

/**
 * Extracts JSON-LD structured data
 *
 * Parses JSON-LD script blocks and extracts metadata from
 * schema.org types like Article, NewsArticle, WebPage, etc.
 */
class JsonLdExtractor implements ExtractorInterface {

    /**
     * Schema.org types we recognize and extract from
     *
     * @var array<string>
     */
    protected const RECOGNIZED_TYPES = [
        "Article",
        "NewsArticle",
        "BlogPosting",
        "WebPage",
        "VideoObject",
        "ImageObject",
    ];

    /**
     * Extract metadata from HTML document
     *
     * @param DOMDocument $dom Parsed HTML document
     * @param string|null $url Original URL (for resolving relative paths)
     *
     * @return array<string, mixed> Extracted metadata fields
     */
    public function extract(DOMDocument $dom, ?string $url = null): array {

        $xpath = new DOMXPath($dom);
        $data = [];

        $nodes = $xpath->query(
            "//script[@type='application/ld+json']"
        );

        if ($nodes === false || $nodes->length === 0) {
            return $data;
        }

        foreach ($nodes as $node) {
            $json = trim((string) $node->textContent);
            if ($json === "") {
                continue;
            }

            $decoded = json_decode($json, true);
            if (!is_array($decoded)) {
                continue;
            }

            $extracted = $this->extractFromSchema($decoded);
            $data = array_merge($data, $extracted);
        }

        return $data;
    }

    /**
     * Extract metadata from a JSON-LD schema object
     *
     * @param array<string, mixed> $schema Decoded JSON-LD data
     *
     * @return array<string, mixed> Extracted metadata fields
     */
    protected function extractFromSchema(array $schema): array {

        $data = [];

        if (isset($schema["@graph"]) && is_array($schema["@graph"])) {
            foreach ($schema["@graph"] as $item) {
                if (is_array($item)) {
                    $extracted = $this->extractFromSchema($item);
                    $data = array_merge($data, $extracted);
                }
            }
            return $data;
        }

        $type = $schema["@type"] ?? null;
        if ($type === null || !in_array($type, self::RECOGNIZED_TYPES)) {
            return $data;
        }

        if (isset($schema["headline"]) && is_string($schema["headline"])) {
            $data["title"] = $schema["headline"];
        }

        if (
            isset($schema["description"]) &&
            is_string($schema["description"])
        ) {
            $data["description"] = $schema["description"];
        }

        if (isset($schema["url"]) && is_string($schema["url"])) {
            $data["url"] = $schema["url"];
        }

        if (isset($schema["image"])) {
            $image_url = $this->extractImageUrl($schema["image"]);
            if ($image_url !== null) {
                $data["image_url"] = $image_url;
            }
        }

        if (isset($schema["author"])) {
            $author = $this->extractAuthor($schema["author"]);
            if ($author !== null) {
                $data["author"] = $author;
            }
        }

        if (
            isset($schema["datePublished"]) &&
            is_string($schema["datePublished"])
        ) {
            $data["published_time"] = $schema["datePublished"];
        }

        if (
            isset($schema["dateModified"]) &&
            is_string($schema["dateModified"])
        ) {
            $data["modified_time"] = $schema["dateModified"];
        }

        return $data;
    }

    /**
     * Extract image URL from various image formats
     *
     * @param mixed $image Image data (string, array, or object)
     *
     * @return string|null Image URL or null
     */
    protected function extractImageUrl(mixed $image): ?string {

        if (is_string($image)) {
            return $image;
        }

        if (is_array($image)) {
            if (isset($image["url"]) && is_string($image["url"])) {
                return $image["url"];
            }

            if (isset($image[0])) {
                return $this->extractImageUrl($image[0]);
            }
        }

        return null;
    }

    /**
     * Extract author name from author field
     *
     * @param mixed $author Author data (string, array, or object)
     *
     * @return string|null Author name or null
     */
    protected function extractAuthor(mixed $author): ?string {

        if (is_string($author)) {
            return $author;
        }

        if (is_array($author)) {
            if (isset($author["name"]) && is_string($author["name"])) {
                return $author["name"];
            }

            if (isset($author[0])) {
                return $this->extractAuthor($author[0]);
            }
        }

        return null;
    }
}
