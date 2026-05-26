@php
    $appName = config('app.name', 'Sinemani');
    $contact = 'support@sinemani.net';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy — {{ $appName }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; max-width: 760px; margin: 40px auto; padding: 0 20px; color: #1a1a1a; line-height: 1.6; }
        h1 { font-size: 28px; }
        h2 { font-size: 20px; margin-top: 28px; }
        a { color: #E50914; }
    </style>
</head>
<body>
    <h1>Privacy Policy</h1>
    <p><strong>Effective date:</strong> {{ now()->format('F j, Y') }}</p>

    <p>{{ $appName }} ("we", "us") operates the {{ $appName }} mobile application and website. This page informs you of our policies regarding the collection, use, and disclosure of personal information.</p>

    <h2>Information We Collect</h2>
    <ul>
        <li>Account information: email address, phone number, display name, profile picture.</li>
        <li>Device information: device model, OS version, push notification token, app version.</li>
        <li>Usage data: watch history, episodes unlocked, comments, ratings, search queries.</li>
        <li>Payment data: mobile money transaction references (we do not store card or PIN data).</li>
    </ul>

    <h2>How We Use Your Information</h2>
    <ul>
        <li>To provide and maintain the service (streaming, coins, subscriptions).</li>
        <li>To process payments for coin packages and subscriptions.</li>
        <li>To send push notifications about new episodes, replies, and rewards.</li>
        <li>To detect fraud, abuse, and policy violations.</li>
    </ul>

    <h2>Data Sharing</h2>
    <p>We do not sell your personal information. We share data only with:</p>
    <ul>
        <li>Payment providers (e.g. Payin / mobile money operators) to process transactions.</li>
        <li>Cloud infrastructure providers (hosting, CDN, push notification services).</li>
        <li>Law enforcement when legally required.</li>
    </ul>

    <h2>Data Retention</h2>
    <p>We retain account data while your account is active. You may request deletion at any time by emailing {{ $contact }}.</p>

    <h2>Children's Privacy</h2>
    <p>{{ $appName }} is not intended for children under 13. Some content is rated for mature audiences only.</p>

    <h2>Your Rights</h2>
    <p>You may access, correct, export, or delete your personal data by contacting {{ $contact }}. You may also delete your account from the in-app Settings screen.</p>

    <h2>Changes</h2>
    <p>We may update this policy. Material changes will be announced in the app.</p>

    <h2>Contact</h2>
    <p>Questions: <a href="mailto:{{ $contact }}">{{ $contact }}</a></p>

    <p><a href="{{ route('terms') }}">Terms of Service</a></p>
</body>
</html>
