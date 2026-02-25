<?php

namespace DealNews\Metadata;

use \GuzzleHttp\Client;
use \GuzzleHttp\Exception\GuzzleException;

/**
 * HTTP client wrapper for fetching web pages
 *
 * Wraps Guzzle with sensible defaults and configuration options
 * for metadata extraction use cases.
 */
class HttpClient {

    /**
     * Guzzle HTTP client instance
     *
     * @var Client
     */
    protected Client $client;

    /**
     * Configuration options
     *
     * @var array<string, mixed>
     */
    protected array $options = [
        "timeout"         => 10,
        "allow_redirects" => [
            "max" => 5,
        ],
        "headers" => [
            "User-Agent" => "Mozilla/5.0 (compatible; MetadataBot/1.0)",
        ],
    ];

    /**
     * Constructor
     *
     * @param array<string, mixed> $options Optional Guzzle configuration
     */
    public function __construct(array $options = []) {
        $this->setOptions($options);
        $this->client = new Client($this->options);
    }

    /**
     * Fetch HTML content from a URL
     *
     * @param string $url URL to fetch
     *
     * @return string HTML content
     *
     * @throws GuzzleException On HTTP request failure
     */
    public function fetchUrl(string $url): string {

        $response = $this->client->get($url);

        return (string) $response->getBody();
    }

    /**
     * Set or update configuration options
     *
     * Merges provided options with existing configuration.
     * Common options:
     * - timeout: int (seconds)
     * - allow_redirects: bool|array
     * - headers: array
     *
     * @param array<string, mixed> $options Configuration to merge
     *
     * @return void
     */
    public function setOptions(array $options): void {

        $this->options = array_merge($this->options, $options);

        if (isset($this->client)) {
            $this->client = new Client($this->options);
        }
    }
}
