<?php

namespace App\Services\Newsletter;

/**
 * Injects UTM parameters into every external <a href="..."> found in HTML content.
 * Internal links (anchors, mailto:, tel:) are left untouched.
 */
class UtmInjector
{
    /**
     * @param  string  $html        Raw HTML content (from bard save_html)
     * @param  array   $utmParams   e.g. ['utm_source'=>'newsletter','utm_medium'=>'email',...]
     * @return string
     */
    public static function inject(string $html, array $utmParams): string
    {
        if (empty($html) || empty($utmParams)) {
            return $html;
        }

        return preg_replace_callback(
            '/href=["\']([^"\']+)["\']/i',
            function (array $matches) use ($utmParams): string {
                $url = $matches[1];

                // Skip non-HTTP links, anchors, placeholders
                if (
                    str_starts_with($url, '#') ||
                    str_starts_with($url, 'mailto:') ||
                    str_starts_with($url, 'tel:') ||
                    str_starts_with($url, '{')   // template placeholders
                ) {
                    return $matches[0];
                }

                $parsed    = parse_url($url);
                $query     = [];

                if (!empty($parsed['query'])) {
                    parse_str($parsed['query'], $query);
                }

                // UTM params override anything already in the URL
                $query = array_merge($query, $utmParams);

                $newQuery = http_build_query($query);

                $rebuilt = ($parsed['scheme'] ?? 'https') . '://'
                    . ($parsed['host']   ?? '')
                    . ($parsed['path']   ?? '')
                    . ($newQuery ? '?' . $newQuery : '')
                    . (!empty($parsed['fragment']) ? '#' . $parsed['fragment'] : '');

                return 'href="' . $rebuilt . '"';
            },
            $html
        );
    }
}
