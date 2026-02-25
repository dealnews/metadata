<?php

namespace DealNews\Metadata;

use \DealNews\Metadata\Extractors\ExtractorInterface;
use \DealNews\Metadata\Extractors\HtmlExtractor;
use \DealNews\Metadata\Extractors\JsonLdExtractor;
use \DealNews\Metadata\Extractors\OembedExtractor;
use \DealNews\Metadata\Extractors\OpenGraphExtractor;
use \DealNews\Metadata\Extractors\TwitterCardExtractor;
use \DOMDocument;
use \GuzzleHttp\Exception\GuzzleException;

/**
 * Main metadata extractor orchestrator
 *
 * Coordinates multiple extraction strategies in priority order,
 * merging results into a unified Metadata object. Supports both
 * fetching URLs directly or processing pre-fetched HTML.
 */
class MetadataExtractor {

    /**
     * HTTP client for fetching URLs
     *
     * @var HttpClient
     */
    protected HttpClient $http_client;

    /**
     * Throw exception on HTTP errors
     *
     * @var bool
     */
    protected bool $throw_on_http_error = false;

    /**
     * Ordered list of extractors (highest to lowest priority)
     *
     * @var array<ExtractorInterface>
     */
    protected array $extractors = [];

    /**
     * Constructor
     *
     * @param array<string, mixed> $options Configuration options:
     *        - throw_on_http_error: bool (default: false)
     *        - http_timeout: int (default: 10)
     *        - user_agent: string (optional)
     */
    public function __construct(array $options = []) {

        $http_options = [];

        if (isset($options["http_timeout"])) {
            $http_options["timeout"] = $options["http_timeout"];
        }

        if (isset($options["user_agent"])) {
            $http_options["headers"]["User-Agent"] = $options["user_agent"];
        }

        $this->http_client = new HttpClient($http_options);

        if (isset($options["throw_on_http_error"])) {
            $this->throw_on_http_error = (bool) $options["throw_on_http_error"];
        }

        $this->initializeExtractors();
    }

    /**
     * Extract metadata from URL or HTML
     *
     * @param string $url_or_html Either a URL to fetch or HTML content
     * @param bool $is_url True if first param is URL, false if HTML
     *
     * @return Metadata Extracted metadata object
     *
     * @throws GuzzleException If URL fetch fails and throw_on_http_error is true
     */
    public function extract(string $url_or_html, bool $is_url = true): Metadata {

        $html = $url_or_html;
        $url = null;

        if ($is_url) {
            $url = $url_or_html;

            try {
                $html = $this->http_client->fetchUrl($url);
            } catch (GuzzleException $e) {
                if ($this->throw_on_http_error) {
                    throw $e;
                }

                return new Metadata();
            }
        }

        $dom = $this->parseHtml($html);

        if ($dom === null) {
            return new Metadata();
        }

        return $this->extractFromDom($dom, $url);
    }

    /**
     * Initialize extractors in priority order
     *
     * Priority: oEmbed > OpenGraph > JSON-LD > Twitter > HTML
     *
     * @return void
     */
    protected function initializeExtractors(): void {

        $this->extractors = [
            new OembedExtractor($this->http_client),
            new OpenGraphExtractor(),
            new JsonLdExtractor(),
            new TwitterCardExtractor(),
            new HtmlExtractor(),
        ];
    }

    /**
     * Parse HTML string into DOMDocument
     *
     * @param string $html HTML content
     *
     * @return DOMDocument|null Parsed document or null on failure
     */
    protected function parseHtml(string $html): ?DOMDocument {

        $dom = new DOMDocument();

        libxml_use_internal_errors(true);

        $success = $dom->loadHTML(
            $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();

        if (!$success) {
            return null;
        }

        return $dom;
    }

    /**
     * Extract metadata from DOM using all extractors
     *
     * Runs extractors in priority order. Later extractors only fill
     * fields that are still null (don't overwrite existing data).
     *
     * @param DOMDocument $dom Parsed HTML document
     * @param string|null $url Original URL
     *
     * @return Metadata Populated metadata object
     */
    protected function extractFromDom(
        DOMDocument $dom,
        ?string $url = null
    ): Metadata {

        $metadata = new Metadata();
        $data = [];

        foreach ($this->extractors as $extractor) {
            try {
                $extracted = $extractor->extract($dom, $url);

                foreach ($extracted as $key => $value) {
                    if (!isset($data[$key])) {
                        $data[$key] = $value;
                    }
                }
            } catch (\Throwable $e) {
                // Continue with other extractors on failure
            }
        }

        foreach ($data as $key => $value) {
            if (property_exists($metadata, $key)) {
                $metadata->$key = $value;
            }
        }

        return $metadata;
    }
}
