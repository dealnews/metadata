<?php

namespace DealNews\Metadata\Extractors;

use \DOMDocument;

/**
 * Interface for metadata extractors
 *
 * Each extractor implements a strategy for pulling metadata from
 * a specific source (OpenGraph, Twitter Cards, JSON-LD, etc.).
 */
interface ExtractorInterface {

    /**
     * Extract metadata from HTML document
     *
     * Returns an associative array of key-value pairs that will be
     * merged into the Metadata object. Only return keys that have
     * values - null/empty values should be omitted.
     *
     * @param DOMDocument $dom Parsed HTML document
     * @param string|null $url Original URL (for resolving relative paths)
     *
     * @return array<string, mixed> Extracted metadata fields
     */
    public function extract(DOMDocument $dom, ?string $url = null): array;
}
