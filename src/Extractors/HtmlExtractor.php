<?php

namespace DealNews\Metadata\Extractors;

use \DOMDocument;
use \DOMXPath;

/**
 * Extracts basic HTML metadata
 *
 * Fallback extractor that reads standard HTML elements:
 * title tag, meta description, canonical URL. Lowest priority.
 */
class HtmlExtractor implements ExtractorInterface {

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

        $title = $this->extractTitle($xpath);
        if ($title !== null) {
            $data["title"] = $title;
        }

        $description = $this->extractDescription($xpath);
        if ($description !== null) {
            $data["description"] = $description;
        }

        $canonical = $this->extractCanonical($xpath, $url);
        if ($canonical !== null) {
            $data["url"] = $canonical;
        }

        return $data;
    }

    /**
     * Extract title from title tag
     *
     * @param DOMXPath $xpath XPath query object
     *
     * @return string|null Title text or null if not found
     */
    protected function extractTitle(DOMXPath $xpath): ?string {

        $nodes = $xpath->query("//title");

        $title = null;

        if ($nodes && $nodes->length > 0) {
            $title = trim((string) $nodes->item(0)?->textContent);
            if ($title === "") {
                $title = null;
            }
        }

        return $title;
    }

    /**
     * Extract description from meta tag
     *
     * @param DOMXPath $xpath XPath query object
     *
     * @return string|null Description text or null if not found
     */
    protected function extractDescription(DOMXPath $xpath): ?string {

        $nodes = $xpath->query(
            "//meta[@name='description']/@content"
        );

        $description = null;

        if ($nodes && $nodes->length > 0) {
            $description = trim((string) $nodes->item(0)?->textContent);
            if ($description === "") {
                $description = null;
            }
        }

        return $description;
    }

    /**
     * Extract canonical URL from link tag
     *
     * @param DOMXPath $xpath XPath query object
     * @param string|null $base_url Base URL for resolving relative paths
     *
     * @return string|null Canonical URL or null if not found
     */
    protected function extractCanonical(
        DOMXPath $xpath,
        ?string $base_url = null
    ): ?string {

        $nodes = $xpath->query(
            "//link[@rel='canonical']/@href"
        );

        $canonical = null;

        if ($nodes && $nodes->length > 0) {
            $href = trim((string) $nodes->item(0)?->textContent);
            if ($href !== "") {
                $canonical = $this->resolveUrl($href, $base_url);
            }
        }

        return $canonical;
    }

    /**
     * Resolve a potentially relative URL against a base URL
     *
     * @param string $url URL to resolve
     * @param string|null $base_url Base URL
     *
     * @return string Resolved absolute URL
     */
    protected function resolveUrl(string $url, ?string $base_url): string {

        if ($base_url === null || parse_url($url, PHP_URL_SCHEME) !== null) {
            return $url;
        }

        $base_parts = parse_url($base_url);
        if ($base_parts === false) {
            return $url;
        }

        $scheme = $base_parts["scheme"] ?? "https";
        $host = $base_parts["host"] ?? "";

        if (str_starts_with($url, "//")) {
            return $scheme . ":" . $url;
        }

        if (str_starts_with($url, "/")) {
            return $scheme . "://" . $host . $url;
        }

        $path = $base_parts["path"] ?? "/";
        $dir = dirname($path);

        return $scheme . "://" . $host . $dir . "/" . $url;
    }
}
