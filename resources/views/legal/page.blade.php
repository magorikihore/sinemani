@php
    $appName = config('app.name', 'Sinemani');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — {{ $appName }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; max-width: 760px; margin: 40px auto; padding: 0 20px; color: #1a1a1a; line-height: 1.6; }
        h1 { font-size: 28px; }
        h2 { font-size: 20px; margin-top: 28px; }
        a { color: #E50914; }
        .legal-content ul { padding-left: 1.25rem; }
        .legal-content p + p { margin-top: 1rem; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p><strong>Effective date:</strong> {{ now()->format('F j, Y') }}</p>

    <div class="legal-content">
        {!! $content !!}
    </div>

    <p><a href="{{ $otherLink['url'] }}">{{ $otherLink['label'] }}</a></p>
</body>
</html>
