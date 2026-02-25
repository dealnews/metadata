<?php

namespace DealNews\Metadata\Extractors;

use \DealNews\Metadata\HttpClient;
use \DealNews\Metadata\Oembed\DiscoveryService;
use \DealNews\Metadata\Oembed\ProviderRegistry;
use \DOMDocument;

/**
 * Extracts oEmbed metadata
 *
 * Attempts to fetch oEmbed data using:
 * 1. Known provider registry (fast path)
 * 2. oEmbed discovery via link tags
 *
 * Requires HttpClient for fetching oEmbed endpoints.
 */
class OembedExtractor implements ExtractorInterface {

    /**
     * HTTP client for fetching oEmbed endpoints
     *
     * @var HttpClient
     */
    protected HttpClient $http_client;

    /**
     * Provider registry
     *
     * @var ProviderRegistry
     */
    protected ProviderRegistry $registry;

    /**
     * Discovery service
     *
     * @var DiscoveryService
     */
    protected DiscoveryService $discovery;

    /**
     * Constructor
     *
     * @param HttpClient $http_client HTTP client instance
     */
    public function __construct(HttpClient $http_client) {
        $this->http_client = $http_client;
        $this->registry = new ProviderRegistry();
        $this->discovery = new DiscoveryService();
    }

    /**
     * Extract metadata from HTML document
     *
     * @param DOMDocument $dom Parsed HTML document
     * @param string|null $url Original URL (required for oEmbed)
     *
     * @return array<string, mixed> Extracted metadata fields
     */
    public function extract(DOMDocument $dom, ?string $url = null): array {

        if ($url === null) {
            return [];
        }

        $endpoint = $this->registry->getEndpoint($url);

        if ($endpoint === null) {
            $endpoint = $this->discovery->discover($dom);
        }

        if ($endpoint === null) {
            return [];
        }

        return $this->fetchOembed($endpoint, $url);
    }

    /**
     * Fetch and parse oEmbed data
     *
     * @param string $endpoint oEmbed API endpoint
     * @param string $url URL to get oEmbed data for
     *
     * @return array<string, mixed> Extracted metadata fields
     */
    protected function fetchOembed(string $endpoint, string $url): array {

        $data = [];

        try {
            $oembed_url = $endpoint . "?url=" . urlencode($url) . "&format=json";
            $response = $this->http_client->fetchUrl($oembed_url);

            $oembed = json_decode($response, true);

            if (!is_array($oembed)) {
                return $data;
            }

            if (isset($oembed["title"]) && is_string($oembed["title"])) {
                $data["title"] = $oembed["title"];
            }

            if (
                isset($oembed["author_name"]) &&
                is_string($oembed["author_name"])
            ) {
                $data["author"] = $oembed["author_name"];
            }

            if (
                isset($oembed["thumbnail_url"]) &&
                is_string($oembed["thumbnail_url"])
            ) {
                $data["image_url"] = $oembed["thumbnail_url"];
            }

            if (
                isset($oembed["thumbnail_width"]) &&
                is_numeric($oembed["thumbnail_width"])
            ) {
                $data["image_width"] = (int) $oembed["thumbnail_width"];
            }

            if (
                isset($oembed["thumbnail_height"]) &&
                is_numeric($oembed["thumbnail_height"])
            ) {
                $data["image_height"] = (int) $oembed["thumbnail_height"];
            }

            if (isset($oembed["html"]) && is_string($oembed["html"])) {
                $data["oembed_html"] = $oembed["html"];
            }

            if (isset($oembed["type"]) && is_string($oembed["type"])) {
                $data["oembed_type"] = $oembed["type"];
            }

            if (
                isset($oembed["provider_name"]) &&
                is_string($oembed["provider_name"])
            ) {
                $data["site_name"] = $oembed["provider_name"];
            }
        } catch (\Throwable $e) {
            // Silently fail - this is expected for many URLs
        }

        return $data;
    }
}
