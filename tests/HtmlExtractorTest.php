<?php

namespace DealNews\Metadata\Tests;

use \DealNews\Metadata\Extractors\HtmlExtractor;
use \DOMDocument;
use \PHPUnit\Framework\TestCase;

/**
 * Tests for HtmlExtractor
 */
class HtmlExtractorTest extends TestCase {

    /**
     * Test extracting title
     *
     * @return void
     */
    public function testExtractTitle(): void {

        $html = "<html><head><title>Test Page</title></head></html>";
        $dom = $this->parseHtml($html);

        $extractor = new HtmlExtractor();
        $data = $extractor->extract($dom);

        $this->assertEquals("Test Page", $data["title"]);
    }

    /**
     * Test extracting meta description
     *
     * @return void
     */
    public function testExtractDescription(): void {

        $html = '<html><head><meta name="description" '.
                'content="Test Description"></head></html>';
        $dom = $this->parseHtml($html);

        $extractor = new HtmlExtractor();
        $data = $extractor->extract($dom);

        $this->assertEquals("Test Description", $data["description"]);
    }

    /**
     * Test extracting canonical URL
     *
     * @return void
     */
    public function testExtractCanonical(): void {

        $html = '<html><head><link rel="canonical" '.
                'href="https://example.com/page"></head></html>';
        $dom = $this->parseHtml($html);

        $extractor = new HtmlExtractor();
        $data = $extractor->extract($dom);

        $this->assertEquals("https://example.com/page", $data["url"]);
    }

    /**
     * Test empty HTML returns empty array
     *
     * @return void
     */
    public function testEmptyHtml(): void {

        $html = "<html><head></head></html>";
        $dom = $this->parseHtml($html);

        $extractor = new HtmlExtractor();
        $data = $extractor->extract($dom);

        $this->assertEmpty($data);
    }

    /**
     * Test relative canonical URL resolution
     *
     * @return void
     */
    public function testRelativeCanonicalUrl(): void {

        $html = '<html><head><link rel="canonical" href="/page"></head></html>';
        $dom = $this->parseHtml($html);

        $extractor = new HtmlExtractor();
        $data = $extractor->extract($dom, "https://example.com/other");

        $this->assertEquals("https://example.com/page", $data["url"]);
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
