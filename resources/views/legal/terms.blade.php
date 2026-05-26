@php
    $appName = config('app.name', 'Sinemani');
    $contact = 'support@sinemani.net';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service — {{ $appName }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; max-width: 760px; margin: 40px auto; padding: 0 20px; color: #1a1a1a; line-height: 1.6; }
        h1 { font-size: 28px; }
        h2 { font-size: 20px; margin-top: 28px; }
        a { color: #E50914; }
    </style>
</head>
<body>
    <h1>Terms of Service</h1>
    <p><strong>Effective date:</strong> {{ now()->format('F j, Y') }}</p>

    <h2>1. Acceptance</h2>
    <p>By using {{ $appName }} you agree to these terms.</p>

    <h2>2. Account</h2>
    <p>You are responsible for keeping your credentials secure and for all activity on your account.</p>

    <h2>3. Coins & Subscriptions</h2>
    <p>Coins and subscriptions are digital goods. Purchases are final and non-refundable except where required by law.</p>

    <h2>4. Content</h2>
    <p>Drama content is licensed for personal, non-commercial viewing only. You may not download, redistribute, or rebroadcast it.</p>

    <h2>5. Prohibited Conduct</h2>
    <ul>
        <li>Reverse engineering or bypassing payment.</li>
        <li>Posting illegal, hateful, or copyrighted content in comments.</li>
        <li>Automated scraping.</li>
    </ul>

    <h2>6. Termination</h2>
    <p>We may suspend accounts that violate these terms.</p>

    <h2>7. Disclaimer</h2>
    <p>The service is provided "as is" without warranties of any kind.</p>

    <h2>8. Contact</h2>
    <p><a href="mailto:{{ $contact }}">{{ $contact }}</a></p>

    <p><a href="{{ route('privacy') }}">Privacy Policy</a></p>
</body>
</html>
