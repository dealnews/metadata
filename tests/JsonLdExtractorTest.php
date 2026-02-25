<?php

namespace DealNews\Metadata\Tests;

use \DealNews\Metadata\Extractors\JsonLdExtractor;
use \DOMDocument;
use \PHPUnit\Framework\TestCase;

/**
 * Tests for JsonLdExtractor
 */
class JsonLdExtractorTest extends TestCase {

    /**
     * Test extracting Article schema
     *
     * @return void
     */
    public function testExtractArticleSchema(): void {

        $json_ld = [
            "@context" => "https://schema.org",
            "@type"    => "Article",
            "headline" => "Test Article",
            "description" => "Article description",
            "url"      => "https://example.com/article",
            "image"    => "https://example.com/image.jpg",
            "author"   => ["@type" => "Person", "name" => "Jane Doe"],
            "datePublished" => "2024-01-01T00:00:00Z",
            "dateModified"  => "2024-01-02T00:00:00Z",
        ];

        $html = "<html><head><script type='application/ld+json'>".
                json_encode($json_ld).
                "</script></head></html>";
        $dom = $this->parseHtml($html);

        $extractor = new JsonLdExtractor();
        $data = $extractor->extract($dom);

        $this->assertEquals("Test Article", $data["title"]);
        $this->assertEquals("Article description", $data["description"]);
        $this->assertEquals("https://example.com/article", $data["url"]);
        $this->assertEquals("https://example.com/image.jpg", $data["image_url"]);
        $this->assertEquals("Jane Doe", $data["author"]);
        $this->assertEquals("2024-01-01T00:00:00Z", $data["published_time"]);
        $this->assertEquals("2024-01-02T00:00:00Z", $data["modified_time"]);
    }

    /**
     * Test extracting with @graph structure
     *
     * @return void
     */
    public function testExtractGraphStructure(): void {

        $json_ld = [
            "@context" => "https://schema.org",
            "@graph"   => [
                [
                    "@type"    => "WebPage",
                    "url"      => "https://example.com/page",
                ],
                [
                    "@type"    => "Article",
                    "headline" => "Graph Article",
                ],
            ],
        ];

        $html = "<html><head><script type='application/ld+json'>".
                json_encode($json_ld).
                "</script></head></html>";
        $dom = $this->parseHtml($html);

        $extractor = new JsonLdExtractor();
        $data = $extractor->extract($dom);

        $this->assertEquals("Graph Article", $data["title"]);
        $this->assertEquals("https://example.com/page", $data["url"]);
    }

    /**
     * Test missing JSON-LD
     *
     * @return void
     */
    public function testMissingJsonLd(): void {

        $html = "<html><head></head></html>";
        $dom = $this->parseHtml($html);

        $extractor = new JsonLdExtractor();
        $data = $extractor->extract($dom);

        $this->assertEmpty($data);
    }

    /**
     * Parse HTML string into DOMDocument
     *
     * @param string $html HTML content
     *
     * @return DOMDocument Parsed document
     */
    protected function parseHtml(string $html): DOMDocument {

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        return $dom;
    }
}
