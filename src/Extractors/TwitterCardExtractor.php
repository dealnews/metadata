<?php

namespace DealNews\Metadata\Extractors;

use \DOMDocument;
use \DOMXPath;

/**
 * Extracts Twitter Card metadata
 *
 * Reads twitter:* meta tags used for Twitter sharing previews.
 * Maps Twitter-specific fields to standard Metadata properties.
 */
class TwitterCardExtractor implements ExtractorInterface {

    /**
     * Mapping of Twitter Card properties to Metadata fields
     *
     * @var array<string, string>
     */
    protected const FIELD_MAP = [
        "twitter:title"       => "title",
        "twitter:description" => "description",
        "twitter:image"       => "image_url",
        "twitter:card"        => "type",
        "twitter:site"        => "site_name",
        "twitter:creator"     => "author",
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

        foreach (self::FIELD_MAP as $twitter_name => $field_name) {
            $value = $this->getMetaName($xpath, $twitter_name);

            if ($value !== null) {
                $data[$field_name] = $value;
            }
        }

        return $data;
    }

    /**
     * Get Twitter Card meta tag content by name
     *
     * @param DOMXPath $xpath XPath query object
     * @param string $name Meta name attribute (e.g., "twitter:title")
     *
     * @return string|null Meta content or null if not found
     */
    protected function getMetaName(DOMXPath $xpath, string $name): ?string {

        $nodes = $xpath->query(
            "//meta[@name='" . $name . "']/@content"
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
