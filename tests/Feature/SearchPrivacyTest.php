<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_has_server_readable_bilingual_metadata_without_invented_prices(): void
    {
        foreach (['ar', 'en'] as $lang) {
            $r = $this->get('/?lang='.$lang)->assertOk();
            $r->assertSee('rel="canonical"', false)->assertSee('/?lang='.$lang, false)->assertSee('hreflang="ar"', false)->assertSee('hreflang="en"', false)->assertSee('application/ld+json', false)->assertSee('SoftwareApplication')->assertSee('<noscript>', false);
            $r->assertDontSee('aggregateRating')->assertDontSee('priceCurrency');
        }
    }

    public function test_private_pages_are_noindex_and_no_store_and_sitemap_lists_only_public_variants(): void
    {
        foreach (['/login', '/family/login', '/family/activate', '/platform/login'] as $path) {
            $r = $this->get($path)->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
            $this->assertStringContainsString('no-store', $r->headers->get('Cache-Control'));
            $r->assertSee('content="noindex,nofollow,noarchive"', false);
        }
        $r = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->assertEquals(2, substr_count($r->getContent(), '<url>'));
        $r->assertSee('lang=ar', false)->assertSee('lang=en', false)->assertDontSee('/family')->assertDontSee('/children');
    }
}
