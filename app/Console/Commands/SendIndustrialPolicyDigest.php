<?php

namespace App\Console\Commands;

use App\Mail\IndustrialPolicyDigest;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

#[Signature('digest:industrial-policy')]
#[Description('Email a daily digest of articles mentioning "industrial policy" from major publications.')]
class SendIndustrialPolicyDigest extends Command
{
    /**
     * @var array<string, string>
     */
    private const PUBLICATIONS = [
        'NYT' => 'nytimes.com',
        'Economist' => 'economist.com',
        'FT' => 'ft.com',
        'Foreign Affairs' => 'foreignaffairs.com',
        'WSJ' => 'wsj.com',
        'Washington Post' => 'washingtonpost.com',
    ];

    public function handle(): int
    {
        $cutoff = CarbonImmutable::now()->subDay();
        $results = [];

        foreach (self::PUBLICATIONS as $name => $domain) {
            $articles = $this->fetchArticles($name, $domain, $cutoff);

            if ($articles !== []) {
                $results[$name] = $articles;
            }
        }

        $total = array_sum(array_map('count', $results));

        if ($total === 0) {
            Log::info('No articles found');
            $this->info('No articles found.');

            return self::SUCCESS;
        }

        Mail::to(config('digest.recipients'))->send(new IndustrialPolicyDigest($results));

        $publicationCount = count($results);
        $articleNoun = $total === 1 ? 'article' : 'articles';
        $publicationNoun = $publicationCount === 1 ? 'publication' : 'publications';

        $this->info("Digest sent — {$total} {$articleNoun} across {$publicationCount} {$publicationNoun}.");

        return self::SUCCESS;
    }

    /**
     * @return list<array{title: string, url: string, pub_date: string}>
     */
    private function fetchArticles(string $name, string $domain, CarbonImmutable $cutoff): array
    {
        $response = Http::timeout(10)
            ->withUserAgent('Mozilla/5.0 (compatible; IndustrialPolicyDigest/1.0)')
            ->get('https://news.google.com/rss/search', [
                'q' => '"industrial policy" site:'.$domain,
                'hl' => 'en-US',
                'gl' => 'US',
                'ceid' => 'US:en',
            ]);

        if (! $response->successful()) {
            Log::warning("Industrial policy digest: HTTP {$response->status()} fetching {$name}");

            return [];
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($response->body());

        if ($xml === false) {
            Log::warning("Industrial policy digest: failed to parse RSS for {$name}");

            return [];
        }

        $articles = [];

        foreach ($xml->channel->item ?? [] as $item) {
            $publishedAt = CarbonImmutable::parse((string) $item->pubDate);

            if ($publishedAt->lt($cutoff)) {
                continue;
            }

            $articles[] = [
                'title' => (string) $item->title,
                'url' => (string) $item->link,
                'pub_date' => $publishedAt->setTimezone('America/Los_Angeles')->format('g:ia T'),
            ];
        }

        return $articles;
    }
}
