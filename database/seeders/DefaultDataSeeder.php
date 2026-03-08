<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\Category;
use App\Models\CoinPackage;
use App\Models\SubscriptionPlan;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class DefaultDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── Categories ─────────────────────────────────────
        $categories = [
            ['name' => 'Romance', 'slug' => 'romance', 'sort_order' => 1],
            ['name' => 'Action', 'slug' => 'action', 'sort_order' => 2],
            ['name' => 'Comedy', 'slug' => 'comedy', 'sort_order' => 3],
            ['name' => 'Drama', 'slug' => 'drama', 'sort_order' => 4],
            ['name' => 'Thriller', 'slug' => 'thriller', 'sort_order' => 5],
            ['name' => 'Horror', 'slug' => 'horror', 'sort_order' => 6],
            ['name' => 'Sci-Fi', 'slug' => 'sci-fi', 'sort_order' => 7],
            ['name' => 'Fantasy', 'slug' => 'fantasy', 'sort_order' => 8],
            ['name' => 'Historical', 'slug' => 'historical', 'sort_order' => 9],
            ['name' => 'Family', 'slug' => 'family', 'sort_order' => 10],
            ['name' => 'Mystery', 'slug' => 'mystery', 'sort_order' => 11],
            ['name' => 'Slice of Life', 'slug' => 'slice-of-life', 'sort_order' => 12],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['slug' => $cat['slug']], $cat);
        }

        // ── Tags ───────────────────────────────────────────
        $tags = [
            'CEO', 'Revenge', 'Secret Identity', 'Love Triangle', 'Time Travel',
            'Billionaire', 'Werewolf', 'Vampire', 'Contract Marriage', 'Enemies to Lovers',
            'Second Chance', 'Campus', 'Palace', 'Mafia', 'Heartbreak',
            'Strong Female Lead', 'Cold Male Lead', 'Comedy', 'Suspense', 'Emotional',
        ];

        foreach ($tags as $tag) {
            Tag::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($tag)],
                ['name' => $tag, 'slug' => \Illuminate\Support\Str::slug($tag)]
            );
        }

        // ── Coin Packages ──────────────────────────────────
        $packages = [
            ['name' => 'Starter Pack', 'coins' => 60, 'bonus_coins' => 0, 'price' => 1000, 'sort_order' => 1],
            ['name' => 'Basic Pack', 'coins' => 300, 'bonus_coins' => 30, 'price' => 5000, 'sort_order' => 2],
            ['name' => 'Popular Pack', 'coins' => 680, 'bonus_coins' => 80, 'price' => 10000, 'is_popular' => true, 'sort_order' => 3],
            ['name' => 'Premium Pack', 'coins' => 1500, 'bonus_coins' => 200, 'price' => 20000, 'sort_order' => 4],
            ['name' => 'Ultimate Pack', 'coins' => 4000, 'bonus_coins' => 600, 'price' => 50000, 'sort_order' => 5],
            ['name' => 'VIP Pack', 'coins' => 9000, 'bonus_coins' => 1500, 'price' => 100000, 'sort_order' => 6],
        ];

        foreach ($packages as $pkg) {
            CoinPackage::firstOrCreate(['name' => $pkg['name']], $pkg);
        }

        // ── Default App Settings ───────────────────────────
        $settings = [
            ['key' => 'app_name', 'value' => 'DramaBox', 'group' => 'general'],
            ['key' => 'app_version', 'value' => '1.0.0', 'group' => 'general'],
            ['key' => 'maintenance_mode', 'value' => 'false', 'group' => 'general'],
            ['key' => 'signup_bonus_coins', 'value' => '50', 'group' => 'coins'],
            ['key' => 'ad_reward_coins', 'value' => '5', 'group' => 'coins'],
            ['key' => 'default_episode_price', 'value' => '10', 'group' => 'coins'],
            ['key' => 'vip_weekly_price', 'value' => '7500', 'group' => 'subscription'],
            ['key' => 'vip_monthly_price', 'value' => '25000', 'group' => 'subscription'],
            ['key' => 'vip_yearly_price', 'value' => '200000', 'group' => 'subscription'],
            ['key' => 'terms_url', 'value' => '', 'group' => 'urls'],
            ['key' => 'privacy_url', 'value' => '', 'group' => 'urls'],
            ['key' => 'support_email', 'value' => 'support@dramabox.com', 'group' => 'general'],
        ];

        foreach ($settings as $setting) {
            AppSetting::firstOrCreate(['key' => $setting['key']], $setting);
        }

        // ── Subscription Plans ──────────────────────────
        $plans = [
            [
                'name' => 'Weekly Pass',
                'slug' => 'weekly-pass',
                'description' => 'Unlock all content for 7 days',
                'interval' => 'weekly',
                'duration_days' => 7,
                'price' => 7500,
                'original_price' => null,
                'coin_bonus' => 20,
                'daily_coin_bonus' => 5,
                'is_popular' => false,
                'sort_order' => 1,
                'features' => json_encode([
                    'Access to all episodes',
                    'No coin cost for any episode',
                    'Ad-free experience',
                    '5 bonus coins daily',
                ]),
            ],
            [
                'name' => 'Monthly VIP',
                'slug' => 'monthly-vip',
                'description' => 'Best value for regular viewers',
                'interval' => 'monthly',
                'duration_days' => 30,
                'price' => 25000,
                'original_price' => 30000,
                'coin_bonus' => 100,
                'daily_coin_bonus' => 10,
                'is_popular' => true,
                'sort_order' => 2,
                'features' => json_encode([
                    'Access to all episodes',
                    'No coin cost for any episode',
                    'Ad-free experience',
                    '10 bonus coins daily',
                    '100 bonus coins on subscribe',
                    'Early access to new episodes',
                ]),
            ],
            [
                'name' => 'Yearly Premium',
                'slug' => 'yearly-premium',
                'description' => 'Ultimate savings — over 30% off',
                'interval' => 'yearly',
                'duration_days' => 365,
                'price' => 200000,
                'original_price' => 300000,
                'coin_bonus' => 500,
                'daily_coin_bonus' => 15,
                'is_popular' => false,
                'sort_order' => 3,
                'features' => json_encode([
                    'Access to all episodes',
                    'No coin cost for any episode',
                    'Ad-free experience',
                    '15 bonus coins daily',
                    '500 bonus coins on subscribe',
                    'Early access to new episodes',
                    'Exclusive VIP badge',
                ]),
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::firstOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
