<?php

namespace DealNews\Metadata\Tests;

use \DealNews\Metadata\Extractors\TwitterCardExtractor;
use \DOMDocument;
use \PHPUnit\Framework\TestCase;

/**
 * Tests for TwitterCardExtractor
 */
class TwitterCardExtractorTest extends TestCase {

    /**
     * Test extracting Twitter Card tags
     *
     * @return void
     */
    public function testExtractTwitterCardTags(): void {

        $html = '<html><head>'.
                '<meta name="twitter:title" content="Twitter Title">'.
                '<meta name="twitter:description" content="Twitter Desc">'.
                '<meta name="twitter:image" content="https://example.com/img.jpg">'.
                '<meta name="twitter:card" content="summary_large_image">'.
                '<meta name="twitter:site" content="@example">'.
                '<meta name="twitter:creator" content="@johndoe">'.
                '</head></html>';
        $dom = $this->parseHtml($html);

        $extractor = new TwitterCardExtractor();
        $data = $extractor->extract($dom);

        $this->assertEquals("Twitter Title", $data["title"]);
        $this->assertEquals("Twitter Desc", $data["description"]);
        $this->assertEquals("https://example.com/img.jpg", $data["image_url"]);
        $this->assertEquals("summary_large_image", $data["type"]);
        $this->assertEquals("@example", $data["site_name"]);
        $this->assertEquals("@johndoe", $data["author"]);
    }

    /**
     * Test missing Twitter Card tags
     *
     * @return void
     */
    public function testMissingTags(): void {

        $html = "<html><head></head></html>";
        $dom = $this->parseHtml($html);

        $extractor = new TwitterCardExtractor();
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
