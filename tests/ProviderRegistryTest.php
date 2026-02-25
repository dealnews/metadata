<?php

namespace DealNews\Metadata\Tests;

use \DealNews\Metadata\Oembed\ProviderRegistry;
use \PHPUnit\Framework\TestCase;

/**
 * Tests for ProviderRegistry
 */
class ProviderRegistryTest extends TestCase {

    /**
     * Test YouTube URL matching
     *
     * @return void
     */
    public function testYouTubeMatching(): void {

        $registry = new ProviderRegistry();

        $endpoint = $registry->getEndpoint(
            "https://www.youtube.com/watch?v=dQw4w9WgXcQ"
        );
        $this->assertEquals("https://www.youtube.com/oembed", $endpoint);

        $endpoint = $registry->getEndpoint("https://youtu.be/dQw4w9WgXcQ");
        $this->assertEquals("https://www.youtube.com/oembed", $endpoint);
    }

    /**
     * Test Vimeo URL matching
     *
     * @return void
     */
    public function testVimeoMatching(): void {

        $registry = new ProviderRegistry();

        $endpoint = $registry->getEndpoint("https://vimeo.com/123456789");
        $this->assertEquals("https://vimeo.com/api/oembed.json", $endpoint);
    }

    /**
     * Test Twitter URL matching
     *
     * @return void
     */
    public function testTwitterMatching(): void {

        $registry = new ProviderRegistry();

        $endpoint = $registry->getEndpoint(
            "https://twitter.com/user/status/123456"
        );
        $this->assertEquals("https://publish.twitter.com/oembed", $endpoint);

        $endpoint = $registry->getEndpoint(
            "https://x.com/user/status/123456"
        );
        $this->assertEquals("https://publish.twitter.com/oembed", $endpoint);
    }

    /**
     * Test non-matching URL
     *
     * @return void
     */
    public function testNonMatchingUrl(): void {

        $registry = new ProviderRegistry();

        $endpoint = $registry->getEndpoint("https://example.com/page");
        $this->assertNull($endpoint);
    }
}
