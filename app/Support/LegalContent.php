<?php

namespace App\Support;

use App\Models\AppSetting;

class LegalContent
{
    public const PRIVACY_KEY = 'privacy_policy_content';

    public const TERMS_KEY = 'terms_of_service_content';

    public const ACCOUNT_DELETION_KEY = 'account_deletion_content';

    public const DATA_DELETION_KEY = 'data_deletion_content';

    public static function privacyPolicy(): string
    {
        return AppSetting::getValue(self::PRIVACY_KEY) ?: self::defaultPrivacyPolicy();
    }

    public static function termsOfService(): string
    {
        return AppSetting::getValue(self::TERMS_KEY) ?: self::defaultTermsOfService();
    }

    public static function accountDeletion(): string
    {
        return AppSetting::getValue(self::ACCOUNT_DELETION_KEY) ?: self::defaultAccountDeletion();
    }

    public static function dataDeletion(): string
    {
        return AppSetting::getValue(self::DATA_DELETION_KEY) ?: self::defaultDataDeletion();
    }

    public static function defaultPrivacyPolicy(): string
    {
        $appName = config('app.name', 'Sinemani');
        $contact = AppSetting::getValue('support_email', 'support@sinemani.net');

        return <<<HTML
<p>{$appName} ("we", "us") operates the {$appName} mobile application and website. This page informs you of our policies regarding the collection, use, and disclosure of personal information.</p>

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
<p>We retain account data while your account is active. You may request deletion at any time by emailing <a href="mailto:{$contact}">{$contact}</a>.</p>

<h2>Children's Privacy</h2>
<p>{$appName} is not intended for children under 13. Some content is rated for mature audiences only.</p>

<h2>Your Rights</h2>
<p>You may access, correct, export, or delete your personal data by contacting <a href="mailto:{$contact}">{$contact}</a>. You may also delete your account from the in-app Settings screen.</p>

<h2>Changes</h2>
<p>We may update this policy. Material changes will be announced in the app.</p>

<h2>Contact</h2>
<p>Questions: <a href="mailto:{$contact}">{$contact}</a></p>
HTML;
    }

    public static function defaultTermsOfService(): string
    {
        $appName = config('app.name', 'Sinemani');
        $contact = AppSetting::getValue('support_email', 'support@sinemani.net');

        return <<<HTML
<h2>1. Acceptance</h2>
<p>By using {$appName} you agree to these terms.</p>

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
<p><a href="mailto:{$contact}">{$contact}</a></p>
HTML;
    }

    public static function defaultAccountDeletion(): string
    {
        $appName = config('app.name', 'Sinemani');
        $contact = AppSetting::getValue('support_email', 'support@sinemani.net');

        return <<<HTML
<p>You can request deletion of your {$appName} account and associated personal data using either method below.</p>

<h2>Delete in the app</h2>
<ol>
    <li>Open the {$appName} app and sign in.</li>
    <li>Go to <strong>Profile → Settings</strong>.</li>
    <li>Tap <strong>Delete Account</strong> and confirm.</li>
</ol>
<p>Your account will be deactivated immediately and personal data will be removed from our active systems within 30 days.</p>

<h2>Request by email</h2>
<p>If you cannot access the app, email us at <a href="mailto:{$contact}?subject=Account%20Deletion%20Request">{$contact}</a> with:</p>
<ul>
    <li>Subject line: <strong>Account Deletion Request</strong></li>
    <li>The email address or phone number linked to your account</li>
    <li>Your display name (if known)</li>
</ul>
<p>We will verify your identity and process the request within 30 days.</p>

<h2>Data that is deleted</h2>
<ul>
    <li>Account profile (name, email, phone, profile picture)</li>
    <li>Watch history, watchlist, likes, comments, and ratings</li>
    <li>Coin balance and unlock history (non-refundable)</li>
    <li>Push notification tokens and device identifiers</li>
</ul>

<h2>Data we may retain</h2>
<p>We may keep limited records where required by law or for fraud prevention, such as payment transaction references and subscription purchase records. These are retained only as long as legally required and are not used for marketing.</p>

<h2>Questions</h2>
<p>Contact <a href="mailto:{$contact}">{$contact}</a> or see our <a href="/privacy">Privacy Policy</a>.</p>
HTML;
    }

    public static function defaultDataDeletion(): string
    {
        $appName = config('app.name', 'Sinemani');
        $contact = AppSetting::getValue('support_email', 'support@sinemani.net');

        return <<<HTML
<p>You can request deletion of your personal data stored by {$appName} without deleting your account, or delete all data by closing your account.</p>

<h2>Delete data in the app</h2>
<ul>
    <li><strong>Watch history</strong> — Profile → Watch History → Clear All</li>
    <li><strong>Comments</strong> — Delete your own comments from any episode screen</li>
    <li><strong>Watchlist</strong> — Remove items from your watchlist</li>
</ul>

<h2>Request data deletion by email</h2>
<p>Email <a href="mailto:{$contact}?subject=Data%20Deletion%20Request">{$contact}</a> with:</p>
<ul>
    <li>Subject line: <strong>Data Deletion Request</strong></li>
    <li>The email address or phone number linked to your account</li>
    <li>Which data you want deleted (e.g. watch history, comments, profile photo)</li>
</ul>
<p>We will verify your identity and process the request within 30 days.</p>

<h2>Data types you can request to delete</h2>
<ul>
    <li>Profile information (display name, profile picture)</li>
    <li>Watch history and continue-watching data</li>
    <li>Comments, ratings, likes, and watchlist entries</li>
    <li>Push notification tokens</li>
</ul>

<h2>Delete your entire account</h2>
<p>To delete your account and all associated data, use our <a href="/delete-account">Account &amp; Data Deletion</a> page.</p>

<h2>Questions</h2>
<p>Contact <a href="mailto:{$contact}">{$contact}</a> or see our <a href="/privacy">Privacy Policy</a>.</p>
HTML;
    }
}
