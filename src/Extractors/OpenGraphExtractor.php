<?php

namespace DealNews\Metadata\Extractors;

use \DOMDocument;
use \DOMXPath;

/**
 * Extracts OpenGraph metadata
 *
 * Reads og:* meta tags commonly used by Facebook and other social platforms.
 * Supports multiple images and structured properties.
 */
class OpenGraphExtractor implements ExtractorInterface {

    /**
     * Mapping of OpenGraph properties to Metadata fields
     *
     * @var array<string, string>
     */
    protected const FIELD_MAP = [
        "og:title"              => "title",
        "og:description"        => "description",
        "og:url"                => "url",
        "og:image"              => "image_url",
        "og:image:width"        => "image_width",
        "og:image:height"       => "image_height",
        "og:type"               => "type",
        "og:site_name"          => "site_name",
        "og:article:author"     => "author",
        "og:article:published_time" => "published_time",
        "og:article:modified_time"  => "modified_time",
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

        foreach (self::FIELD_MAP as $og_property => $field_name) {
            $value = $this->getMetaProperty($xpath, $og_property);

            if ($value !== null) {
                if ($field_name === "image_width" || $field_name === "image_height") {
                    $data[$field_name] = (int) $value;
                } else {
                    $data[$field_name] = $value;
                }
            }
        }

        return $data;
    }

    /**
     * Get OpenGraph meta tag content by property name
     *
     * @param DOMXPath $xpath XPath query object
     * @param string $property Property name (e.g., "og:title")
     *
     * @return string|null Property value or null if not found
     */
    protected function getMetaProperty(
        DOMXPath $xpath,
        string $property
    ): ?string {

        $nodes = $xpath->query(
            "//meta[@property='" . $property . "']/@content"
        );

        $value = null;

        if ($nodes && $nodes->length > 0) {
            $value = trim((string) $nodes->item(0)?->textContent);
            if ($value === "") {
                $value = null;
            }
        }

        return $value;
    }
}
