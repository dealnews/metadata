<?php

namespace DealNews\Metadata\Oembed;

/**
 * Registry of popular oEmbed providers with their endpoint patterns
 *
 * Maps URL patterns to oEmbed API endpoints for known providers.
 * Used as a fast path before attempting oEmbed discovery.
 */
class ProviderRegistry {

    /**
     * Provider endpoint configurations
     *
     * @var array<string, array{schemes: string[], endpoint: string}>
     */
    protected const PROVIDERS = [
        "youtube" => [
            "schemes" => [
                "https://www.youtube.com/watch*",
                "https://youtube.com/watch*",
                "https://youtu.be/*",
                "https://www.youtube.com/shorts/*",
            ],
            "endpoint" => "https://www.youtube.com/oembed",
        ],
        "vimeo" => [
            "schemes" => [
                "https://vimeo.com/*",
                "https://player.vimeo.com/video/*",
            ],
            "endpoint" => "https://vimeo.com/api/oembed.json",
        ],
        "twitter" => [
            "schemes" => [
                "https://twitter.com/*/status/*",
                "https://x.com/*/status/*",
            ],
            "endpoint" => "https://publish.twitter.com/oembed",
        ],
        "instagram" => [
            "schemes" => [
                "https://www.instagram.com/p/*",
                "https://www.instagram.com/reel/*",
                "https://www.instagram.com/tv/*",
            ],
            "endpoint" => "https://graph.facebook.com/v18.0/instagram_oembed",
        ],
        "facebook" => [
            "schemes" => [
                "https://www.facebook.com/*/posts/*",
                "https://www.facebook.com/*/videos/*",
                "https://www.facebook.com/photo.php?*",
                "https://www.facebook.com/video.php?*",
            ],
            "endpoint" => "https://graph.facebook.com/v18.0/oembed_post",
        ],
        "tiktok" => [
            "schemes" => [
                "https://www.tiktok.com/*/video/*",
                "https://www.tiktok.com/@*/video/*",
            ],
            "endpoint" => "https://www.tiktok.com/oembed",
        ],
        "soundcloud" => [
            "schemes" => [
                "https://soundcloud.com/*",
                "https://soundcloud.app.goo.gl/*",
            ],
            "endpoint" => "https://soundcloud.com/oembed",
        ],
        "spotify" => [
            "schemes" => [
                "https://open.spotify.com/track/*",
                "https://open.spotify.com/album/*",
                "https://open.spotify.com/playlist/*",
            ],
            "endpoint" => "https://open.spotify.com/oembed",
        ],
    ];

    /**
     * Get the oEmbed endpoint for a URL
     *
     * @param string $url URL to check against registered providers
     *
     * @return string|null oEmbed endpoint URL or null if no match
     */
    public function getEndpoint(string $url): ?string {

        $endpoint = null;

        foreach (self::PROVIDERS as $provider) {
            foreach ($provider["schemes"] as $scheme) {
                if ($this->matchesScheme($url, $scheme)) {
                    $endpoint = $provider["endpoint"];
                    break 2;
                }
            }
        }

        return $endpoint;
    }

    /**
     * Check if URL matches a scheme pattern
     *
     * Supports wildcards (*) in scheme patterns.
     *
     * @param string $url URL to test
     * @param string $scheme Pattern to match (e.g., "https://example.com/*")
     *
     * @return bool True if URL matches the scheme pattern
     */
    protected function matchesScheme(string $url, string $scheme): bool {

        $pattern = preg_quote($scheme, "#");
        $pattern = str_replace("\\*", ".*", $pattern);
        $pattern = "#^" . $pattern . "#i";

        return (bool) preg_match($pattern, $url);
    }
}
