<?php

namespace DealNews\Metadata\Oembed;

use \DOMDocument;
use \DOMXPath;

/**
 * Discovers oEmbed endpoints via HTML link tags
 *
 * Looks for <link> tags with rel="alternate" and type matching
 * oEmbed JSON or XML formats as specified in the oEmbed spec.
 */
class DiscoveryService {

    /**
     * Discover oEmbed endpoint from HTML document
     *
     * Searches for link tags indicating oEmbed support:
     * <link rel="alternate" type="application/json+oembed" href="...">
     * <link rel="alternate" type="text/xml+oembed" href="...">
     *
     * @param DOMDocument $dom Parsed HTML document
     *
     * @return string|null oEmbed endpoint URL or null if not found
     */
    public function discover(DOMDocument $dom): ?string {

        $xpath = new DOMXPath($dom);

        $endpoint = $this->findJsonEndpoint($xpath);

        if ($endpoint === null) {
            $endpoint = $this->findXmlEndpoint($xpath);
        }

        return $endpoint;
    }

    /**
     * Find JSON oEmbed endpoint
     *
     * @param DOMXPath $xpath XPath query object
     *
     * @return string|null Endpoint URL or null
     */
    protected function findJsonEndpoint(DOMXPath $xpath): ?string {

        $nodes = $xpath->query(
            "//link[@rel='alternate'][@type='application/json+oembed']/@href"
        );

        $endpoint = null;

        if ($nodes && $nodes->length > 0) {
            $href = trim((string) $nodes->item(0)?->textContent);
            if ($href !== "") {
                $endpoint = $href;
            }
        }

        return $endpoint;
    }

    /**
     * Find XML oEmbed endpoint
     *
     * @param DOMXPath $xpath XPath query object
     *
     * @return string|null Endpoint URL or null
     */
    protected function findXmlEndpoint(DOMXPath $xpath): ?string {

        $nodes = $xpath->query(
            "//link[@rel='alternate'][@type='text/xml+oembed']/@href"
        );

        $endpoint = null;

        if ($nodes && $nodes->length > 0) {
            $href = trim((string) $nodes->item(0)?->textContent);
            if ($href !== "") {
                $endpoint = $href;
            }
        }

        return $endpoint;
    }
}
