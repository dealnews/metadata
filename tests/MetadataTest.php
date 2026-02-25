<?php

namespace DealNews\Metadata\Tests;

use \DealNews\Metadata\Metadata;
use \PHPUnit\Framework\TestCase;

/**
 * Tests for Metadata value object
 */
class MetadataTest extends TestCase {

    /**
     * Test metadata object instantiation with defaults
     *
     * @return void
     */
    public function testInstantiation(): void {

        $metadata = new Metadata();

        $this->assertNull($metadata->title);
        $this->assertNull($metadata->description);
        $this->assertNull($metadata->url);
        $this->assertNull($metadata->image_url);
        $this->assertNull($metadata->image_width);
        $this->assertNull($metadata->image_height);
        $this->assertNull($metadata->type);
        $this->assertNull($metadata->site_name);
        $this->assertNull($metadata->author);
        $this->assertNull($metadata->published_time);
        $this->assertNull($metadata->modified_time);
        $this->assertNull($metadata->oembed_html);
        $this->assertNull($metadata->oembed_type);
    }

    /**
     * Test setting and getting properties
     *
     * @return void
     */
    public function testSetProperties(): void {

        $metadata = new Metadata();

        $metadata->title = "Test Title";
        $metadata->description = "Test Description";
        $metadata->url = "https://example.com/page";
        $metadata->image_url = "https://example.com/image.jpg";
        $metadata->image_width = 1200;
        $metadata->image_height = 630;
        $metadata->type = "article";
        $metadata->site_name = "Example Site";
        $metadata->author = "John Doe";
        $metadata->published_time = "2024-01-01T00:00:00Z";
        $metadata->modified_time = "2024-01-02T00:00:00Z";
        $metadata->oembed_html = "<iframe></iframe>";
        $metadata->oembed_type = "video";

        $this->assertEquals("Test Title", $metadata->title);
        $this->assertEquals("Test Description", $metadata->description);
        $this->assertEquals("https://example.com/page", $metadata->url);
        $this->assertEquals("https://example.com/image.jpg", $metadata->image_url);
        $this->assertEquals(1200, $metadata->image_width);
        $this->assertEquals(630, $metadata->image_height);
        $this->assertEquals("article", $metadata->type);
        $this->assertEquals("Example Site", $metadata->site_name);
        $this->assertEquals("John Doe", $metadata->author);
        $this->assertEquals("2024-01-01T00:00:00Z", $metadata->published_time);
        $this->assertEquals("2024-01-02T00:00:00Z", $metadata->modified_time);
        $this->assertEquals("<iframe></iframe>", $metadata->oembed_html);
        $this->assertEquals("video", $metadata->oembed_type);
    }
}
