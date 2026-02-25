<?php

namespace DealNews\Metadata\Tests;

use \DealNews\Metadata\MetadataExtractor;
use \PHPUnit\Framework\TestCase;

/**
 * Tests for MetadataExtractor
 */
class MetadataExtractorTest extends TestCase {

    /**
     * Test construction with default options
     *
     * @return void
     */
    public function testConstructionWithDefaults(): void {

        $extractor = new MetadataExtractor();

        $this->assertInstanceOf(MetadataExtractor::class, $extractor);
    }

    /**
     * Test construction with custom options
     *
     * @return void
     */
    public function testConstructionWithCustomOptions(): void {

        $extractor = new MetadataExtractor([
            "throw_on_http_error" => true,
            "http_timeout"        => 30,
            "user_agent"          => "CustomBot/2.0",
        ]);

        $this->assertInstanceOf(MetadataExtractor::class, $extractor);
    }

    /**
     * Test extract from HTML string
     *
     * @return void
     */
    public function testExtractFromHtml(): void {

        $html = '<html><head>'.
                '<title>Test Page</title>'.
                '<meta name="description" content="Test Description">'.
                '<meta property="og:title" content="OG Title">'.
                '<meta property="og:image" content="https://example.com/img.jpg">'.
                '</head></html>';

        $extractor = new MetadataExtractor();
        $metadata = $extractor->extract($html, false);

        $this->assertEquals("OG Title", $metadata->title);
        $this->assertEquals("Test Description", $metadata->description);
        $this->assertEquals("https://example.com/img.jpg", $metadata->image_url);
    }

    /**
     * Test priority-based merging
     *
     * OpenGraph should take priority over HTML fallback
     *
     * @return void
     */
    public function testPriorityBasedMerging(): void {

        $html = '<html><head>'.
                '<title>HTML Title</title>'.
                '<meta property="og:title" content="OpenGraph Title">'.
                '<meta name="twitter:title" content="Twitter Title">'.
                '</head></html>';

        $extractor = new MetadataExtractor();
        $metadata = $extractor->extract($html, false);

        $this->assertEquals(
            "OpenGraph Title",
            $metadata->title,
            "OpenGraph should take priority over Twitter and HTML"
        );
    }

    /**
     * Test that later extractors fill missing fields
     *
     * @return void
     */
    public function testLaterExtractorsFillMissingFields(): void {

        $html = '<html><head>'.
                '<meta property="og:title" content="OG Title">'.
                '<meta name="description" content="HTML Description">'.
                '</head></html>';

        $extractor = new MetadataExtractor();
        $metadata = $extractor->extract($html, false);

        $this->assertEquals("OG Title", $metadata->title);
        $this->assertEquals(
            "HTML Description",
            $metadata->description,
            "HTML should fill description when OG doesn't provide it"
        );
    }

    /**
     * Test extract with empty HTML
     *
     * @return void
     */
    public function testExtractWithEmptyHtml(): void {

        $extractor = new MetadataExtractor();
        $metadata = $extractor->extract("<html></html>", false);

        $this->assertNull($metadata->title);
        $this->assertNull($metadata->description);
    }

    /**
     * Test extract with malformed HTML (parse failure)
     *
     * @return void
     */
    public function testExtractWithMalformedHtml(): void {

        $extractor = new MetadataExtractor();
        $metadata = $extractor->extract(" ", false);

        $this->assertNull($metadata->title);
    }

    /**
     * Test extract with JSON-LD data
     *
     * @return void
     */
    public function testExtractWithJsonLd(): void {

        $json_ld = json_encode([
            "@context"      => "https://schema.org",
            "@type"         => "Article",
            "headline"      => "Article Headline",
            "author"        => ["@type" => "Person", "name" => "Jane Doe"],
            "datePublished" => "2024-01-15T08:00:00Z",
        ]);

        $html = '<html><head>'.
                '<script type="application/ld+json">'.$json_ld.'</script>'.
                '</head></html>';

        $extractor = new MetadataExtractor();
        $metadata = $extractor->extract($html, false);

        $this->assertEquals("Article Headline", $metadata->title);
        $this->assertEquals("Jane Doe", $metadata->author);
        $this->assertEquals("2024-01-15T08:00:00Z", $metadata->published_time);
    }

    /**
     * Test extract with multiple metadata sources
     *
     * @return void
     */
    public function testExtractWithMultipleSources(): void {

        $html = '<html><head>'.
                '<title>HTML Title</title>'.
                '<meta name="description" content="HTML Description">'.
                '<meta property="og:title" content="OG Title">'.
                '<meta property="og:url" content="https://example.com/page">'.
                '<meta name="twitter:image" content="https://example.com/twitter.jpg">'.
                '<meta name="twitter:site" content="@example">'.
                '</head></html>';

        $extractor = new MetadataExtractor();
        $metadata = $extractor->extract($html, false);

        $this->assertEquals("OG Title", $metadata->title);
        $this->assertEquals("HTML Description", $metadata->description);
        $this->assertEquals("https://example.com/page", $metadata->url);
        $this->assertEquals(
            "https://example.com/twitter.jpg",
            $metadata->image_url
        );
        $this->assertEquals("@example", $metadata->site_name);
    }

    /**
     * Test extract ignores unknown properties
     *
     * @return void
     */
    public function testExtractIgnoresUnknownProperties(): void {

        $html = '<html><head>'.
                '<meta property="og:title" content="Title">'.
                '</head></html>';

        $extractor = new MetadataExtractor();
        $metadata = $extractor->extract($html, false);

        $this->assertEquals("Title", $metadata->title);
    }

    /**
     * Test extract handles extractor exceptions gracefully
     *
     * @return void
     */
    public function testExtractHandlesExtractorExceptionsGracefully(): void {

        $html = '<html><head>'.
                '<title>Fallback Title</title>'.
                '<script type="application/ld+json">invalid json{</script>'.
                '</head></html>';

        $extractor = new MetadataExtractor();
        $metadata = $extractor->extract($html, false);

        $this->assertEquals(
            "Fallback Title",
            $metadata->title,
            "Should still extract from working extractors"
        );
    }

    /**
     * Test extract with canonical URL
     *
     * @return void
     */
    public function testExtractWithCanonicalUrl(): void {

        $html = '<html><head>'.
                '<link rel="canonical" href="https://example.com/canonical">'.
                '</head></html>';

        $extractor = new MetadataExtractor();
        $metadata = $extractor->extract($html, false);

        $this->assertEquals("https://example.com/canonical", $metadata->url);
    }

    /**
     * Test extract with image dimensions
     *
     * @return void
     */
    public function testExtractWithImageDimensions(): void {

        $html = '<html><head>'.
                '<meta property="og:image" content="https://example.com/img.jpg">'.
                '<meta property="og:image:width" content="1200">'.
                '<meta property="og:image:height" content="630">'.
                '</head></html>';

        $extractor = new MetadataExtractor();
        $metadata = $extractor->extract($html, false);

        $this->assertEquals("https://example.com/img.jpg", $metadata->image_url);
        $this->assertEquals(1200, $metadata->image_width);
        $this->assertEquals(630, $metadata->image_height);
    }

    /**
     * Test extract with article metadata
     *
     * @return void
     */
    public function testExtractWithArticleMetadata(): void {

        $html = '<html><head>'.
                '<meta property="og:type" content="article">'.
                '<meta property="og:article:author" content="John Smith">'.
                '<meta property="og:article:published_time" '.
                'content="2024-01-01T00:00:00Z">'.
                '<meta property="og:article:modified_time" '.
                'content="2024-01-02T00:00:00Z">'.
                '</head></html>';

        $extractor = new MetadataExtractor();
        $metadata = $extractor->extract($html, false);

        $this->assertEquals("article", $metadata->type);
        $this->assertEquals("John Smith", $metadata->author);
        $this->assertEquals("2024-01-01T00:00:00Z", $metadata->published_time);
        $this->assertEquals("2024-01-02T00:00:00Z", $metadata->modified_time);
    }

    /**
     * Test extract with comprehensive metadata
     *
     * @return void
     */
    public function testExtractWithComprehensiveMetadata(): void {

        $html = '<html><head>'.
                '<title>Fallback Title</title>'.
                '<meta name="description" content="Page description">'.
                '<link rel="canonical" href="https://example.com/page">'.
                '<meta property="og:title" content="Primary Title">'.
                '<meta property="og:description" content="OG Description">'.
                '<meta property="og:image" content="https://example.com/og.jpg">'.
                '<meta property="og:type" content="article">'.
                '<meta property="og:site_name" content="Example Site">'.
                '<meta name="twitter:card" content="summary_large_image">'.
                '<meta name="twitter:creator" content="@author">'.
                '</head></html>';

        $extractor = new MetadataExtractor();
        $metadata = $extractor->extract($html, false);

        $this->assertEquals("Primary Title", $metadata->title);
        $this->assertEquals("OG Description", $metadata->description);
        $this->assertEquals("https://example.com/page", $metadata->url);
        $this->assertEquals("https://example.com/og.jpg", $metadata->image_url);
        $this->assertEquals("article", $metadata->type);
        $this->assertEquals("Example Site", $metadata->site_name);
        $this->assertEquals("@author", $metadata->author);
    }
}
