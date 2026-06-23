<?php

use App\Models\AppSetting;
use App\Support\LegalContent;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            [
                'key' => LegalContent::PRIVACY_KEY,
                'value' => LegalContent::defaultPrivacyPolicy(),
                'group' => 'legal',
            ],
            [
                'key' => LegalContent::TERMS_KEY,
                'value' => LegalContent::defaultTermsOfService(),
                'group' => 'legal',
            ],
        ];

        foreach ($settings as $setting) {
            AppSetting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }

    public function down(): void
    {
        AppSetting::whereIn('key', [
            LegalContent::PRIVACY_KEY,
            LegalContent::TERMS_KEY,
        ])->delete();
    }
};
