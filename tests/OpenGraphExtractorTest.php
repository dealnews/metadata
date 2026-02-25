<?php

namespace DealNews\Metadata\Tests;

use \DealNews\Metadata\Extractors\OpenGraphExtractor;
use \DOMDocument;
use \PHPUnit\Framework\TestCase;

/**
 * Tests for OpenGraphExtractor
 */
class OpenGraphExtractorTest extends TestCase {

    /**
     * Test extracting OpenGraph tags
     *
     * @return void
     */
    public function testExtractOpenGraphTags(): void {

        $html = '<html><head>'.
                '<meta property="og:title" content="OG Title">'.
                '<meta property="og:description" content="OG Description">'.
                '<meta property="og:url" content="https://example.com/page">'.
                '<meta property="og:image" content="https://example.com/img.jpg">'.
                '<meta property="og:image:width" content="1200">'.
                '<meta property="og:image:height" content="630">'.
                '<meta property="og:type" content="article">'.
                '<meta property="og:site_name" content="Example Site">'.
                '</head></html>';
        $dom = $this->parseHtml($html);

        $extractor = new OpenGraphExtractor();
        $data = $extractor->extract($dom);

        $this->assertEquals("OG Title", $data["title"]);
        $this->assertEquals("OG Description", $data["description"]);
        $this->assertEquals("https://example.com/page", $data["url"]);
        $this->assertEquals("https://example.com/img.jpg", $data["image_url"]);
        $this->assertEquals(1200, $data["image_width"]);
        $this->assertEquals(630, $data["image_height"]);
        $this->assertEquals("article", $data["type"]);
        $this->assertEquals("Example Site", $data["site_name"]);
    }

    /**
     * Test missing OpenGraph tags
     *
     * @return void
     */
    public function testMissingTags(): void {

        $html = "<html><head></head></html>";
        $dom = $this->parseHtml($html);

        $extractor = new OpenGraphExtractor();
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
