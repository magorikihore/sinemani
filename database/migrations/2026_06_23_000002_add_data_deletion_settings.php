<?php

use App\Models\AppSetting;
use App\Support\LegalContent;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        AppSetting::firstOrCreate(
            ['key' => LegalContent::DATA_DELETION_KEY],
            [
                'value' => LegalContent::defaultDataDeletion(),
                'group' => 'legal',
            ]
        );

        AppSetting::firstOrCreate(
            ['key' => 'data_deletion_url'],
            [
                'value' => 'https://api.sinemani.net/delete-data',
                'group' => 'urls',
            ]
        );
    }

    public function down(): void
    {
        AppSetting::whereIn('key', [
            LegalContent::DATA_DELETION_KEY,
            'data_deletion_url',
        ])->delete();
    }
};
