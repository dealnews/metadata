<?php

namespace DealNews\Metadata;

/**
 * Value object containing extracted metadata from a web page
 *
 * Holds structured data extracted from various sources:
 * oEmbed, OpenGraph, JSON-LD, Twitter Cards, and HTML fallbacks.
 */
class Metadata {

    /**
     * Page title
     *
     * @var string|null
     */
    public ?string $title = null;

    /**
     * Page description
     *
     * @var string|null
     */
    public ?string $description = null;

    /**
     * Canonical URL of the page
     *
     * @var string|null
     */
    public ?string $url = null;

    /**
     * Primary image URL
     *
     * @var string|null
     */
    public ?string $image_url = null;

    /**
     * Image width in pixels
     *
     * @var int|null
     */
    public ?int $image_width = null;

    /**
     * Image height in pixels
     *
     * @var int|null
     */
    public ?int $image_height = null;

    /**
     * Content type (e.g., "article", "video", "product")
     *
     * @var string|null
     */
    public ?string $type = null;

    /**
     * Name of the website/publisher
     *
     * @var string|null
     */
    public ?string $site_name = null;

    /**
     * Author name
     *
     * @var string|null
     */
    public ?string $author = null;

    /**
     * Publication date/time (ISO 8601 format)
     *
     * @var string|null
     */
    public ?string $published_time = null;

    /**
     * Last modified date/time (ISO 8601 format)
     *
     * @var string|null
     */
    public ?string $modified_time = null;

    /**
     * Embedded HTML content from oEmbed
     *
     * @var string|null
     */
    public ?string $oembed_html = null;

    /**
     * oEmbed content type ("video", "photo", "rich", "link")
     *
     * @var string|null
     */
    public ?string $oembed_type = null;
}
