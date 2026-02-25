# DealNews Metadata Extractor

A PHP library for extracting rich metadata from web pages using multiple strategies: oEmbed, OpenGraph, JSON-LD, Twitter Cards, and HTML fallbacks.

## Features

- **Multi-Source Extraction**: Pulls metadata from oEmbed, OpenGraph, JSON-LD, Twitter Cards, and standard HTML
- **Priority-Based Merging**: Higher-quality sources take precedence (oEmbed → OpenGraph → JSON-LD → Twitter → HTML)
- **Flexible Input**: Fetch URLs directly or process pre-fetched HTML content
- **Typed Value Object**: Returns structured `Metadata` object with typed properties
- **Known Provider Support**: Fast-path for popular platforms (YouTube, Vimeo, Twitter/X, Instagram, etc.)
- **oEmbed Discovery**: Automatic discovery via HTML link tags
- **Configurable Error Handling**: Choose between exceptions or graceful degradation

## Requirements

- PHP 8.2 or higher
- ext-dom (included with PHP)
- ext-json (included with PHP)
- Guzzle 7.0+ (for HTTP requests)

## Installation

```bash
composer require dealnews/metadata
```

## Basic Usage

### Extract from URL

```php
use DealNews\Metadata\MetadataExtractor;

$extractor = new MetadataExtractor();
$metadata = $extractor->extract('https://example.com/article');

echo $metadata->title;        // "Article Title"
echo $metadata->description;  // "Article description..."
echo $metadata->image_url;    // "https://example.com/image.jpg"
echo $metadata->author;       // "John Doe"
```

### Extract from HTML

```php
$extractor = new MetadataExtractor();
$html = '<html><head><title>My Page</title>...';
$metadata = $extractor->extract($html, false);  // false = not a URL
```

### Configuration Options

```php
$extractor = new MetadataExtractor([
    'throw_on_http_error' => true,   // Throw exceptions on HTTP failures
    'http_timeout'        => 15,     // Request timeout in seconds
    'user_agent'          => 'MyBot/1.0',  // Custom user agent
]);
```

## Metadata Fields

The `Metadata` object contains the following properties:

| Property | Type | Description |
|----------|------|-------------|
| `title` | `?string` | Page title |
| `description` | `?string` | Page description |
| `url` | `?string` | Canonical URL |
| `image_url` | `?string` | Primary image URL |
| `image_width` | `?int` | Image width in pixels |
| `image_height` | `?int` | Image height in pixels |
| `type` | `?string` | Content type (article, video, etc.) |
| `site_name` | `?string` | Name of the website/publisher |
| `author` | `?string` | Author name |
| `published_time` | `?string` | Publication date/time (ISO 8601) |
| `modified_time` | `?string` | Last modified date/time (ISO 8601) |
| `oembed_html` | `?string` | Embedded HTML from oEmbed |
| `oembed_type` | `?string` | oEmbed type (video, photo, rich, link) |

All fields are nullable and will be `null` if not found.

## Extraction Priority

The library runs extractors in this order and merges results:

1. **oEmbed** (provider registry + discovery)
2. **OpenGraph** (og:* meta tags)
3. **JSON-LD** (schema.org structured data)
4. **Twitter Cards** (twitter:* meta tags)
5. **HTML** (title, meta description, canonical link)

Later extractors only fill fields that are still `null` - they won't overwrite data from higher-priority sources.

## oEmbed Support

### Supported Providers

The library includes built-in support for popular oEmbed providers:

- YouTube
- Vimeo
- Twitter/X
- Instagram
- Facebook
- TikTok
- SoundCloud
- Spotify

### Discovery

For sites not in the registry, the library automatically looks for oEmbed discovery links:

```html
<link rel="alternate" type="application/json+oembed" href="...">
```

**Note**: oEmbed endpoints may require API keys or have usage limits. These are the caller's responsibility to manage.

## Error Handling

### Graceful Degradation (Default)

By default, the library returns partial results on errors:

```php
$extractor = new MetadataExtractor();
$metadata = $extractor->extract('https://nonexistent.example.com');
// Returns empty Metadata object, no exception thrown
```

### Strict Mode

Enable exceptions for HTTP errors:

```php
$extractor = new MetadataExtractor(['throw_on_http_error' => true]);

try {
    $metadata = $extractor->extract('https://nonexistent.example.com');
} catch (\GuzzleHttp\Exception\GuzzleException $e) {
    // Handle HTTP error
}
```

## Edge Cases

### Relative URLs

Image and canonical URLs are resolved against the base URL when possible:

```php
// HTML: <link rel="canonical" href="/page">
// Base URL: https://example.com/other
// Result: https://example.com/page
```

### Multiple JSON-LD Blocks

The library handles pages with multiple `<script type="application/ld+json">` blocks and `@graph` structures.

### User-Agent Headers

Some sites block requests without proper User-Agent headers. The library includes a default:

```
Mozilla/5.0 (compatible; MetadataBot/1.0)
```

Customize if needed:

```php
$extractor = new MetadataExtractor([
    'user_agent' => 'MyCustomBot/2.0',
]);
```

### Character Encoding

The library uses PHP's DOMDocument for HTML parsing, which handles most encoding issues automatically via libxml.

## Development

### Running Tests

```bash
composer install
./vendor/bin/phpunit tests/
```

### Code Coverage

```bash
./vendor/bin/phpunit tests/ --coverage-html coverage/
```

## License

BSD-3-Clause. See LICENSE file for details.

## Contributing

This is a DealNews internal library. For issues or questions, contact the development team.
