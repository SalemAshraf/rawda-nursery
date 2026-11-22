<?php

namespace App\Http\Controllers;

class SearchController extends Controller
{
    public function sitemap()
    {
        $base = rtrim(config('app.url'), '/');
        $escape = fn ($value) => htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';
        foreach (['ar', 'en'] as $locale) {
            $xml .= '<url><loc>'.$escape($base.'/?lang='.$locale).'</loc>';
            foreach (['ar', 'en'] as $alternate) {
                $xml .= '<xhtml:link rel="alternate" hreflang="'.$alternate.'" href="'.$escape($base.'/?lang='.$alternate).'"/>';
            }
            $xml .= '</url>';
        }

        return response($xml.'</urlset>', 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }
}
