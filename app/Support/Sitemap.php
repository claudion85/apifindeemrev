<?php

namespace App\Support;

/**
 * Sitemap generator
 */
class Sitemap
{
    public function generate()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . PHP_EOL;

        $baseUrl = 'https://www.findeem.com/';

        $urls = [
            ['loc' => $baseUrl, 'changefreq' => 'daily'],
            ['loc' => $baseUrl . 'login', 'changefreq' => 'monthly'],
            ['loc' => $baseUrl . 'register', 'changefreq' => 'monthly'],
            ['loc' => $baseUrl . 'newsfeed', 'changefreq' => 'monthly'],
            ['loc' => $baseUrl . 'terms-and-conditions', 'changefreq' => 'monthly'],
            ['loc' => $baseUrl . 'cookie-policy', 'changefreq' => 'monthly'],
        ];

        $events = \App\Event::where('visibility', 'public')
            ->where('end_date', '>=', \Carbon\Carbon::now())->get([
                'slug'
            ]);
        foreach ($events as $event) {
            $urls[] = [
                'loc' => $baseUrl . 'events/' . $event->slug,
                'changefreq' => 'daily',
            ];
        }

        $categories = \App\Category::where('type', 'events')->get([
            'slug'
        ]);
        foreach ($categories as $category) {
            $urls[] = [
                'loc' => $baseUrl . 'categories/' . $category->slug,
                'changefreq' => 'daily',
            ];
        }

        foreach ($urls as $url) {
            $xml .= '<url>' . PHP_EOL;
            $xml .= '  <loc>' . $url['loc'] . '</loc>' . PHP_EOL;
            $xml .= '  <changefreq>' . $url['changefreq'] . '</changefreq>' . PHP_EOL;
            $xml .= '</url>' . PHP_EOL;
        }

        $xml .= '</urlset>' . PHP_EOL;

        return $xml;
    }
}
