<?php

namespace DealNews\Metadata\Tests;

use \DealNews\Metadata\Extractors\OembedExtractor;
use \DealNews\Metadata\HttpClient;
use \DOMDocument;
use \PHPUnit\Framework\TestCase;

/**
 * Tests for OembedExtractor
 */
class OembedExtractorTest extends TestCase {

    /**
     * Test extract with null URL returns empty array
     *
     * @return void
     */
    public function testExtractWithNullUrl(): void {

        $http_client = $this->createMock(HttpClient::class);
        $http_client->expects($this->never())
                    ->method('fetchUrl');

        $extractor = new OembedExtractor($http_client);
        $dom = $this->parseHtml("<html><head></head></html>");

        $data = $extractor->extract($dom, null);

        $this->assertEmpty($data);
    }

    /**
     * Test extract with YouTube URL (known provider)
     *
     * @return void
     */
    public function testExtractWithKnownProvider(): void {

        $oembed_response = json_encode([
            "type"           => "video",
            "title"          => "Test Video",
            "author_name"    => "Test Author",
            "thumbnail_url"  => "https://example.com/thumb.jpg",
            "thumbnail_width" => 1280,
            "thumbnail_height" => 720,
            "html"           => "<iframe></iframe>",
            "provider_name"  => "YouTube",
        ]);

        $http_client = $this->createMock(HttpClient::class);
        $http_client->expects($this->once())
                    ->method('fetchUrl')
                    ->with($this->stringContains('youtube.com/oembed'))
                    ->willReturn($oembed_response);

        $extractor = new OembedExtractor($http_client);
        $dom = $this->parseHtml("<html><head></head></html>");
        $url = "https://www.youtube.com/watch?v=dQw4w9WgXcQ";

        $data = $extractor->extract($dom, $url);

        $this->assertEquals("Test Video", $data["title"]);
        $this->assertEquals("Test Author", $data["author"]);
        $this->assertEquals("https://example.com/thumb.jpg", $data["image_url"]);
        $this->assertEquals(1280, $data["image_width"]);
        $this->assertEquals(720, $data["image_height"]);
        $this->assertEquals("<iframe></iframe>", $data["oembed_html"]);
        $this->assertEquals("video", $data["oembed_type"]);
        $this->assertEquals("YouTube", $data["site_name"]);
    }

    /**
     * Test extract with discovery link
     *
     * @return void
     */
    public function testExtractWithDiscovery(): void {

        $html = '<html><head>'.
                '<link rel="alternate" type="application/json+oembed" '.
                'href="https://example.com/oembed">'.
                '</head></html>';

        $oembed_response = json_encode([
            "type"  => "rich",
            "title" => "Discovered Content",
        ]);

        $http_client = $this->createMock(HttpClient::class);
        $http_client->expects($this->once())
                    ->method('fetchUrl')
                    ->with($this->stringContains('example.com/oembed'))
                    ->willReturn($oembed_response);

        $extractor = new OembedExtractor($http_client);
        $dom = $this->parseHtml($html);
        $url = "https://example.com/article";

        $data = $extractor->extract($dom, $url);

        $this->assertEquals("Discovered Content", $data["title"]);
        $this->assertEquals("rich", $data["oembed_type"]);
    }

    /**
     * Test extract with no endpoint available
     *
     * @return void
     */
    public function testExtractWithNoEndpoint(): void {

        $http_client = $this->createMock(HttpClient::class);
        $http_client->expects($this->never())
                    ->method('fetchUrl');

        $extractor = new OembedExtractor($http_client);
        $dom = $this->parseHtml("<html><head></head></html>");
        $url = "https://unknown-site.example.com/page";

        $data = $extractor->extract($dom, $url);

        $this->assertEmpty($data);
    }

    /**
     * Test extract with HTTP error (graceful failure)
     *
     * @return void
     */
    public function testExtractWithHttpError(): void {

        $http_client = $this->createMock(HttpClient::class);
        $http_client->expects($this->once())
                    ->method('fetchUrl')
                    ->willThrowException(
                        new \Exception("Network error")
                    );

        $extractor = new OembedExtractor($http_client);
        $dom = $this->parseHtml("<html><head></head></html>");
        $url = "https://www.youtube.com/watch?v=test";

        $data = $extractor->extract($dom, $url);

        $this->assertEmpty($data);
    }

    /**
     * Test extract with invalid JSON response
     *
     * @return void
     */
    public function testExtractWithInvalidJson(): void {

        $http_client = $this->createMock(HttpClient::class);
        $http_client->expects($this->once())
                    ->method('fetchUrl')
                    ->willReturn("not valid json");

        $extractor = new OembedExtractor($http_client);
        $dom = $this->parseHtml("<html><head></head></html>");
        $url = "https://www.youtube.com/watch?v=test";

        $data = $extractor->extract($dom, $url);

        $this->assertEmpty($data);
    }

    /**
     * Test extract with JSON string (not array)
     *
     * @return void
     */
    public function testExtractWithNonArrayJson(): void {

        $http_client = $this->createMock(HttpClient::class);
        $http_client->expects($this->once())
                    ->method('fetchUrl')
                    ->willReturn(json_encode("string value"));

        $extractor = new OembedExtractor($http_client);
        $dom = $this->parseHtml("<html><head></head></html>");
        $url = "https://www.youtube.com/watch?v=test";

        $data = $extractor->extract($dom, $url);

        $this->assertEmpty($data);
    }

    /**
     * Test extract with partial oEmbed response
     *
     * @return void
     */
    public function testExtractWithPartialResponse(): void {

        $oembed_response = json_encode([
            "type" => "photo",
            "title" => "Photo Title",
        ]);

        $http_client = $this->createMock(HttpClient::class);
        $http_client->expects($this->once())
                    ->method('fetchUrl')
                    ->willReturn($oembed_response);

        $extractor = new OembedExtractor($http_client);
        $dom = $this->parseHtml("<html><head></head></html>");
        $url = "https://www.youtube.com/watch?v=test";

        $data = $extractor->extract($dom, $url);

        $this->assertEquals("Photo Title", $data["title"]);
        $this->assertEquals("photo", $data["oembed_type"]);
        $this->assertArrayNotHasKey("author", $data);
        $this->assertArrayNotHasKey("image_url", $data);
    }

    /**
     * Test extract with numeric thumbnail dimensions as strings
     *
     * @return void
     */
    public function testExtractWithNumericStringDimensions(): void {

        $oembed_response = json_encode([
            "thumbnail_width" => "640",
            "thumbnail_height" => "480",
        ]);

        $http_client = $this->createMock(HttpClient::class);
        $http_client->expects($this->once())
                    ->method('fetchUrl')
                    ->willReturn($oembed_response);

        $extractor = new OembedExtractor($http_client);
        $dom = $this->parseHtml("<html><head></head></html>");
        $url = "https://www.youtube.com/watch?v=test";

        $data = $extractor->extract($dom, $url);

        $this->assertSame(640, $data["image_width"]);
        $this->assertSame(480, $data["image_height"]);
    }

    /**
     * Test extract with non-numeric dimensions (should be ignored)
     *
     * @return void
     */
    public function testExtractWithNonNumericDimensions(): void {

        $oembed_response = json_encode([
            "thumbnail_width" => "not-a-number",
            "thumbnail_height" => [],
        ]);

        $http_client = $this->createMock(HttpClient::class);
        $http_client->expects($this->once())
                    ->method('fetchUrl')
                    ->willReturn($oembed_response);

        $extractor = new OembedExtractor($http_client);
        $dom = $this->parseHtml("<html><head></head></html>");
        $url = "https://www.youtube.com/watch?v=test";

        $data = $extractor->extract($dom, $url);

        $this->assertArrayNotHasKey("image_width", $data);
        $this->assertArrayNotHasKey("image_height", $data);
    }

    /**
     * Test extract with non-string fields (should be ignored)
     *
     * @return void
     */
    public function testExtractWithNonStringFields(): void {

        $oembed_response = json_encode([
            "title" => 12345,
            "author_name" => ["not", "string"],
            "thumbnail_url" => null,
            "html" => false,
            "type" => true,
            "provider_name" => 99,
        ]);

        $http_client = $this->createMock(HttpClient::class);
        $http_client->expects($this->once())
                    ->method('fetchUrl')
                    ->willReturn($oembed_response);

        $extractor = new OembedExtractor($http_client);
        $dom = $this->parseHtml("<html><head></head></html>");
        $url = "https://www.youtube.com/watch?v=test";

        $data = $extractor->extract($dom, $url);

        $this->assertEmpty($data);
    }

    /**
     * Test URL encoding in oEmbed request
     *
     * @return void
     */
    public function testUrlEncodingInRequest(): void {

        $url = "https://www.youtube.com/watch?v=test&t=30s";
        $expected_encoded = urlencode($url);

        $http_client = $this->createMock(HttpClient::class);
        $http_client->expects($this->once())
                    ->method('fetchUrl')
                    ->with($this->stringContains($expected_encoded))
                    ->willReturn(json_encode(["type" => "video"]));

        $extractor = new OembedExtractor($http_client);
        $dom = $this->parseHtml("<html><head></head></html>");

        $extractor->extract($dom, $url);
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
