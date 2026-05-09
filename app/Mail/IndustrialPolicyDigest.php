<?php

namespace App\Mail;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IndustrialPolicyDigest extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, list<array{title: string, url: string, pub_date: string}>>  $results
     */
    public function __construct(public array $results) {}

    public function envelope(): Envelope
    {
        $total = array_sum(array_map('count', $this->results));
        $date = CarbonImmutable::now()->setTimezone('America/Los_Angeles')->format('l, F j');

        return new Envelope(
            subject: "Industrial Policy Digest — {$date} ({$total} articles)",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.industrial-policy-digest',
        );
    }
}
