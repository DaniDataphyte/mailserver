<?php

namespace Tests\Unit;

use App\Services\Newsletter\UtmInjector;
use Tests\TestCase;

class UtmInjectorTest extends TestCase
{
    private array $utm = [
        'utm_source'   => 'newsletter',
        'utm_medium'   => 'email',
        'utm_campaign' => 'campaign-1',
    ];

    public function test_injects_utm_into_plain_link(): void
    {
        $html   = '<a href="https://dataphyte.com/story">Read</a>';
        $result = UtmInjector::inject($html, $this->utm);

        $this->assertStringContainsString('utm_source=newsletter', $result);
        $this->assertStringContainsString('utm_medium=email', $result);
        $this->assertStringContainsString('utm_campaign=campaign-1', $result);
    }

    public function test_skips_anchor_links(): void
    {
        $html   = '<a href="#section">Jump</a>';
        $result = UtmInjector::inject($html, $this->utm);

        $this->assertStringNotContainsString('utm_source', $result);
        $this->assertEquals($html, $result);
    }

    public function test_skips_mailto_links(): void
    {
        $html   = '<a href="mailto:editor@dataphyte.com">Email us</a>';
        $result = UtmInjector::inject($html, $this->utm);

        $this->assertStringNotContainsString('utm_source', $result);
    }

    public function test_skips_tel_links(): void
    {
        $html   = '<a href="tel:+2348000000000">Call</a>';
        $result = UtmInjector::inject($html, $this->utm);

        $this->assertStringNotContainsString('utm_source', $result);
    }

    public function test_merges_with_existing_query_params(): void
    {
        $html   = '<a href="https://dataphyte.com/story?ref=home">Read</a>';
        $result = UtmInjector::inject($html, $this->utm);

        $this->assertStringContainsString('ref=home', $result);
        $this->assertStringContainsString('utm_source=newsletter', $result);
    }

    public function test_utm_overrides_existing_utm_params(): void
    {
        $html   = '<a href="https://dataphyte.com/?utm_source=old">Read</a>';
        $result = UtmInjector::inject($html, $this->utm);

        $this->assertStringContainsString('utm_source=newsletter', $result);
        $this->assertStringNotContainsString('utm_source=old', $result);
    }

    public function test_handles_multiple_links(): void
    {
        $html = '<a href="https://a.com">A</a> <a href="https://b.com">B</a>';
        $result = UtmInjector::inject($html, $this->utm);

        $this->assertEquals(2, substr_count($result, 'utm_source=newsletter'));
    }

    public function test_returns_empty_string_unchanged(): void
    {
        $this->assertEquals('', UtmInjector::inject('', $this->utm));
    }

    public function test_returns_html_unchanged_when_no_utm_params(): void
    {
        $html = '<a href="https://dataphyte.com">Read</a>';
        $this->assertEquals($html, UtmInjector::inject($html, []));
    }

    public function test_preserves_fragment(): void
    {
        $html   = '<a href="https://dataphyte.com/page#section">Read</a>';
        $result = UtmInjector::inject($html, $this->utm);

        $this->assertStringContainsString('#section', $result);
        $this->assertStringContainsString('utm_source=newsletter', $result);
    }
}
