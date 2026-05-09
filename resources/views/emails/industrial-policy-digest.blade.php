@php
    $total = array_sum(array_map('count', $results));
    $date = \Carbon\CarbonImmutable::now()->setTimezone('America/Los_Angeles')->format('l, F j');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Industrial Policy Digest</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#18181b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:8px;padding:32px;">
                    <tr>
                        <td>
                            <h1 style="margin:0 0 8px 0;font-size:22px;font-weight:600;color:#0f172a;">Industrial Policy Digest</h1>
                            <p style="margin:0 0 24px 0;font-size:14px;color:#52525b;">{{ $date }} &middot; {{ $total }} {{ $total === 1 ? 'article' : 'articles' }}</p>

                            @foreach ($results as $publication => $articles)
                                <h2 style="margin:24px 0 8px 0;font-size:16px;font-weight:600;color:#0f172a;border-bottom:1px solid #e4e4e7;padding-bottom:6px;">{{ $publication }}</h2>
                                <ul style="margin:0;padding:0 0 0 18px;">
                                    @foreach ($articles as $article)
                                        <li style="margin:0 0 10px 0;font-size:14px;line-height:1.4;">
                                            <a href="{{ $article['url'] }}" style="color:#1d4ed8;text-decoration:none;">{{ $article['title'] }}</a>
                                            <span style="display:inline-block;margin-left:6px;font-size:12px;color:#71717a;">{{ $article['pub_date'] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endforeach

                            <p style="margin:32px 0 0 0;padding-top:16px;border-top:1px solid #e4e4e7;font-size:12px;color:#71717a;">
                                You're receiving this because you subscribed to the Industrial Policy Digest.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
