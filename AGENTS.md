# AGENTS.md - AI Assistant Context

> **Purpose**: This file provides context for AI coding assistants (Claude, ChatGPT, Cursor, Windsurf, etc.) working on this repository. It's AI-agnostic and focuses on project-specific patterns, conventions, and architecture.

---

## Project Overview

**What**: PHP library that extracts rich metadata from web pages using multiple strategies.

**Why**: When you have a URL, you want structured data about it (title, description, images, author, dates). Sites expose this through various formats - we handle them all.

**How**: Priority-based extraction chain:
1. **oEmbed** (provider registry + discovery)
2. **OpenGraph** (Facebook/social meta tags)
3. **JSON-LD** (schema.org structured data)
4. **Twitter Cards** (Twitter meta tags)
5. **HTML** (title tag, meta description, canonical link)

**Key Feature**: Higher-priority sources override lower ones. If OpenGraph provides a title, we ignore the HTML `<title>` tag. But if OpenGraph only provides an image, we still grab the title from HTML.

**Use Cases**:
- Link previews in social apps
- Article metadata for CMS systems
- SEO analysis tools
- Content aggregators
- Bookmark managers

---

## Architecture & Design

### Component Structure

```
src/
├── Metadata.php              # Value object (return type)
├── MetadataExtractor.php     # Main orchestrator
├── HttpClient.php            # Guzzle wrapper
├── Extractors/
│   ├── ExtractorInterface.php
│   ├── OembedExtractor.php   # Priority 1
│   ├── OpenGraphExtractor.php # Priority 2
│   ├── JsonLdExtractor.php   # Priority 3
│   ├── TwitterCardExtractor.php # Priority 4
│   └── HtmlExtractor.php     # Priority 5 (fallback)
└── Oembed/
    ├── ProviderRegistry.php  # Hardcoded providers (YouTube, etc.)
    └── DiscoveryService.php  # <link> tag discovery
```

### Data Flow

```
User calls: extract($url)
    ↓
MetadataExtractor fetches HTML (or accepts pre-fetched)
    ↓
Parses HTML into DOMDocument
    ↓
Runs each extractor in priority order
    ↓
Merges results (first-wins strategy)
    ↓
Returns Metadata value object
```

### Key Patterns

**Extractor Interface**: All extractors implement:
```php
public function extract(DOMDocument $dom, ?string $url = null): array;
```
Returns associative array of metadata fields. `null` values are omitted.

**Priority Merging**: In `MetadataExtractor::extractFromDom()`:
```php
foreach ($this->extractors as $extractor) {
    $extracted = $extractor->extract($dom, $url);
    foreach ($extracted as $key => $value) {
        if (!isset($data[$key])) {  // Only set if not already set
            $data[$key] = $value;
        }
    }
}
```

**Graceful Degradation**: Every extractor failure is caught silently. We want partial data, not crashes.

**No Static State**: Everything is instance-based. This makes testing easier (dependency injection).

---

## Coding Standards (DealNews-Specific)

### Critical Rules

**1. Bracing Style (1TBS)**
```php
// ✓ Correct
public function extract() {
    if ($condition) {
        doSomething();
    }
}

// ✗ Wrong
public function extract()
{
    if ($condition)
    {
        doSomething();
    }
}
```

Multi-line conditionals: braces on new line
```php
if (
    $really_long_condition &&
    $another_condition &&
    $yet_another
) {
    doSomething();
}
```

**2. Variable Naming**
```php
$snake_case = "always";  // ✓
$camelCase = "never";    // ✗
```

**3. Visibility**
```php
protected $property;  // ✓ Default unless you have a reason
private $property;    // ✗ Avoid - makes testing harder
```

**4. Single Return Point** (with early validation exception)
```php
// ✓ Preferred
public function compute(int $value): int {
    if ($value < 0) {
        return 0;  // Early return for validation is OK
    }
    
    $result = 0;
    // ... 100 lines of logic ...
    if ($condition) {
        $result = 42;
    }
    // ... more logic ...
    return $result;  // Single exit point
}

// ✗ Avoid
public function compute(int $value): int {
    if ($condition) {
        return 42;  // Don't scatter returns throughout logic
    }
    // ... more code ...
    return 0;
}
```

**5. Type Declarations**
```php
// ✓ Always declare types
public function fetch(string $url): ?Metadata { ... }

// ✗ Never omit types
public function fetch($url) { ... }
```

**6. Value Objects Over Arrays**
```php
// ✓ Return structured objects
return new Metadata();  // Properties: title, description, etc.

// ✗ Don't return associative arrays for complex data
return ['title' => '...', 'description' => '...'];
```

**7. Complete PHPDoc**
```php
/**
 * Extract metadata from HTML document
 *
 * Longer description if needed. Explain why, not how.
 *
 * @param DOMDocument $dom Parsed HTML document
 * @param string|null $url Original URL (for resolving relative paths)
 *
 * @return array<string, mixed> Extracted metadata fields
 */
public function extract(DOMDocument $dom, ?string $url = null): array {
```

---

## Build & Test

### Run Tests
```bash
# All tests
./vendor/bin/phpunit tests/

# Specific test file
./vendor/bin/phpunit tests/MetadataExtractorTest.php

# With coverage
./vendor/bin/phpunit tests/ --coverage-text

# Pretty output
./vendor/bin/phpunit tests/ --testdox
```

### Install Dependencies
```bash
composer install
```

### Current Stats
- **45 tests**, 135 assertions
- **87.23% line coverage**
- All tests must pass before merge

---

## Common Patterns in This Codebase

### Pattern 1: XPath Queries for Meta Tags
```php
$xpath = new DOMXPath($dom);
$nodes = $xpath->query("//meta[@property='og:title']/@content");
if ($nodes && $nodes->length > 0) {
    $value = trim((string) $nodes->item(0)?->textContent);
}
```

### Pattern 2: Null Coalescing for Optional Fields
```php
// Don't add to array if null
if ($value !== null) {
    $data["title"] = $value;
}
```

### Pattern 3: Type Validation
```php
// Always check types from external data (JSON, HTML)
if (isset($json["title"]) && is_string($json["title"])) {
    $data["title"] = $json["title"];
}
```

### Pattern 4: URL Resolution
```php
// Relative URLs must be resolved against base URL
protected function resolveUrl(string $url, ?string $base_url): string {
    if ($base_url === null || parse_url($url, PHP_URL_SCHEME) !== null) {
        return $url;  // Already absolute or no base
    }
    // ... resolution logic ...
}
```

### Pattern 5: Silent Failure in Extractors
```php
try {
    // Extraction logic
} catch (\Throwable $e) {
    // Silently fail - we want partial data
    // Don't log, don't throw - just move on
}
```

---

## Testing Strategy

### Test File Naming
- `tests/MetadataTest.php` → tests `src/Metadata.php`
- `tests/Extractors/HtmlExtractorTest.php` → tests `src/Extractors/HtmlExtractor.php`

### What We Test

**Unit Tests**: Each extractor in isolation
```php
$extractor = new OpenGraphExtractor();
$dom = $this->parseHtml('<meta property="og:title" content="Test">');
$data = $extractor->extract($dom);
$this->assertEquals("Test", $data["title"]);
```

**Integration Tests**: Full extraction pipeline
```php
$extractor = new MetadataExtractor();
$metadata = $extractor->extract($html, false);
// Verify priority: OpenGraph > Twitter > HTML
```

**Edge Cases We Cover**:
- Empty/missing tags
- Invalid JSON in JSON-LD
- Non-numeric image dimensions
- Malformed HTML
- Type mismatches (expecting string, got array)
- HTTP errors (network failures)
- Relative URLs

### Mocking Strategy
```php
// Mock HttpClient for tests that shouldn't hit the network
$http_client = $this->createMock(HttpClient::class);
$http_client->expects($this->once())
            ->method('fetchUrl')
            ->willReturn('<html>...</html>');
```

### Coverage Goals
- **100%** on new code
- **80%+** on existing extractors
- All public methods must be tested
- Protected methods tested via public interface

---

## Gotchas & Edge Cases

### 1. PHP's DOMDocument Is Quirky
```php
// Must suppress errors - HTML is messy
libxml_use_internal_errors(true);
$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
libxml_clear_errors();
```

### 2. XPath Queries Return False on Failure
```php
$nodes = $xpath->query("//meta[@property='og:title']/@content");
if ($nodes && $nodes->length > 0) {  // Check both false AND empty
    // Safe to use
}
```

### 3. JSON-LD Can Have @graph Arrays
```php
// Handle both single objects and @graph arrays
if (isset($schema["@graph"]) && is_array($schema["@graph"])) {
    foreach ($schema["@graph"] as $item) {
        $extracted = $this->extractFromSchema($item);
        // Merge results
    }
}
```

### 4. oEmbed Endpoints Need URL Encoding
```php
$oembed_url = $endpoint . "?url=" . urlencode($url) . "&format=json";
```

### 5. Metadata Fields Are Nullable
```php
// Never assume fields are set
if ($metadata->title !== null) {
    echo $metadata->title;
}
```

### 6. Provider Registry Uses Wildcards
```php
// Pattern matching with *
"https://www.youtube.com/watch*"  // Matches watch?v=...
```

### 7. PHP 8.4 ValueError on Empty Strings
```php
// Don't pass empty strings to loadHTML in PHP 8.4+
if (trim($html) === "") {
    return null;
}
$dom->loadHTML($html);
```

---

## Making Changes

### Adding a New Extractor

1. **Create the extractor class**:
```php
namespace DealNews\Metadata\Extractors;

class MyNewExtractor implements ExtractorInterface {
    public function extract(DOMDocument $dom, ?string $url = null): array {
        $data = [];
        // ... extraction logic ...
        return $data;
    }
}
```

2. **Add to MetadataExtractor priority list**:
```php
protected function initializeExtractors(): void {
    $this->extractors = [
        new OembedExtractor($this->http_client),
        new MyNewExtractor(),  // Add at appropriate priority
        new OpenGraphExtractor(),
        // ...
    ];
}
```

3. **Write comprehensive tests**:
```php
class MyNewExtractorTest extends TestCase {
    public function testExtractsData(): void { ... }
    public function testHandlesMissingData(): void { ... }
    public function testHandlesInvalidData(): void { ... }
}
```

4. **Run tests**: `./vendor/bin/phpunit tests/`

### Adding a New Metadata Field

1. **Add property to Metadata.php**:
```php
/**
 * Video duration in seconds
 *
 * @var int|null
 */
public ?int $video_duration = null;
```

2. **Update extractors** that can provide this field:
```php
if (isset($oembed["duration"]) && is_numeric($oembed["duration"])) {
    $data["video_duration"] = (int) $oembed["duration"];
}
```

3. **Update README.md** metadata fields table

4. **Add tests** for the new field

### Adding a New oEmbed Provider

Edit `src/Oembed/ProviderRegistry.php`:
```php
"tiktok" => [
    "schemes" => [
        "https://www.tiktok.com/*/video/*",
        "https://www.tiktok.com/@*/video/*",
    ],
    "endpoint" => "https://www.tiktok.com/oembed",
],
```

Add test to `tests/ProviderRegistryTest.php`:
```php
public function testTikTokMatching(): void {
    $registry = new ProviderRegistry();
    $endpoint = $registry->getEndpoint("https://www.tiktok.com/@user/video/123");
    $this->assertEquals("https://www.tiktok.com/oembed", $endpoint);
}
```

### Debugging Extraction Issues

1. **Check priority order**: Is a higher-priority extractor overwriting your data?
2. **Inspect DOM**: `var_dump($dom->saveHTML())` to see parsed HTML
3. **Test extractors individually**: Isolate the problematic extractor
4. **Check type validation**: Are you rejecting valid data due to type checks?
5. **Verify XPath queries**: Test queries in isolation

### Before Submitting PR

- [ ] All tests pass: `./vendor/bin/phpunit tests/`
- [ ] Code follows 1TBS bracing style
- [ ] Variables use `snake_case`
- [ ] All methods have complete PHPDoc
- [ ] New code has test coverage
- [ ] README.md updated if adding features/fields
- [ ] No bare functions (class-based API only)
- [ ] Type declarations on all methods

---

## Quick Reference

### File Locations
- **Main entry point**: `src/MetadataExtractor.php`
- **Value object**: `src/Metadata.php`
- **Extractors**: `src/Extractors/*.php`
- **Tests**: `tests/*Test.php`
- **Examples**: See README.md

### Dependencies
- PHP 8.2+
- Guzzle 7.0+ (HTTP client)
- ext-dom (HTML parsing)
- ext-json (JSON parsing)
- PHPUnit 10.0+ (testing)

### Useful Commands
```bash
# Test one file
./vendor/bin/phpunit tests/MetadataExtractorTest.php

# Coverage report
./vendor/bin/phpunit tests/ --coverage-html coverage/

# Pretty test names
./vendor/bin/phpunit tests/ --testdox

# Composer autoload refresh
composer dump-autoload
```

### Key Files to Read First
1. `README.md` - User-facing documentation
2. `src/MetadataExtractor.php` - Main orchestrator
3. `src/Extractors/OpenGraphExtractor.php` - Simple extractor example
4. `tests/MetadataExtractorTest.php` - Integration test patterns

---

**Questions?** Check the tests - they're documentation too. Every edge case we handle has a test demonstrating it.
