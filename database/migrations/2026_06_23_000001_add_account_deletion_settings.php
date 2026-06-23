<?php

use App\Models\AppSetting;
use App\Support\LegalContent;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        AppSetting::firstOrCreate(
            ['key' => LegalContent::ACCOUNT_DELETION_KEY],
            [
                'value' => LegalContent::defaultAccountDeletion(),
                'group' => 'legal',
            ]
        );

        AppSetting::firstOrCreate(
            ['key' => 'account_deletion_url'],
            [
                'value' => 'https://api.sinemani.net/delete-account',
                'group' => 'urls',
            ]
        );
    }

    public function down(): void
    {
        AppSetting::whereIn('key', [
            LegalContent::ACCOUNT_DELETION_KEY,
            'account_deletion_url',
        ])->delete();
    }
};
