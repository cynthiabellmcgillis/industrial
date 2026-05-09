<?php

use App\Mail\IndustrialPolicyDigest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

function rssFeed(array $items): string
{
    $itemXml = collect($items)->map(fn ($i) => <<<XML
        <item>
            <title><![CDATA[{$i['title']}]]></title>
            <link>{$i['url']}</link>
            <pubDate>{$i['pub_date']}</pubDate>
        </item>
    XML)->implode("\n");

    return <<<XML
    <?xml version="1.0" encoding="UTF-8"?>
    <rss version="2.0"><channel><title>Test</title>{$itemXml}</channel></rss>
    XML;
}

beforeEach(function () {
    config(['digest.recipients' => ['me@example.com', 'other@example.com']]);
    Mail::fake();
});

test('it sends the digest with parsed articles within 24 hours', function () {
    $body = rssFeed([
        [
            'title' => 'Recent industrial policy article',
            'url' => 'https://news.google.com/articles/recent',
            'pub_date' => CarbonImmutable::now()->subHours(2)->toRfc2822String(),
        ],
        [
            'title' => 'Old industrial policy article',
            'url' => 'https://news.google.com/articles/old',
            'pub_date' => CarbonImmutable::now()->subDays(5)->toRfc2822String(),
        ],
    ]);

    Http::fake([
        'news.google.com/*' => Http::response($body, 200),
    ]);

    $this->artisan('digest:industrial-policy')->assertSuccessful();

    Mail::assertSent(IndustrialPolicyDigest::class, function (IndustrialPolicyDigest $mail) {
        return $mail->hasTo('me@example.com')
            && $mail->hasTo('other@example.com')
            && count($mail->results) === 6
            && collect($mail->results)->flatten(1)->count() === 6
            && $mail->results['NYT'][0]['title'] === 'Recent industrial policy article';
    });
});

test('it does not send when no recent articles found', function () {
    Log::spy();

    $body = rssFeed([
        [
            'title' => 'Stale article',
            'url' => 'https://news.google.com/articles/stale',
            'pub_date' => CarbonImmutable::now()->subDays(5)->toRfc2822String(),
        ],
    ]);

    Http::fake([
        'news.google.com/*' => Http::response($body, 200),
    ]);

    $this->artisan('digest:industrial-policy')->assertSuccessful();

    Mail::assertNothingSent();
    Log::shouldHaveReceived('info')->with('No articles found')->once();
});
