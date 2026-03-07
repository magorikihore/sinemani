<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Drama;
use App\Models\Episode;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding sample dramas, episodes & banners...');

        // Use free sample videos from public domain / creative commons
        $sampleVideos = [
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/Sintel.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/TearsOfSteel.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerFun.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerJoyrides.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerMeltdowns.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/SubaruOutbackOnStreetAndDirt.mp4',
        ];

        // Sample poster images (placeholder service)
        $posterBase = 'https://picsum.photos/seed';

        // ── Dramas ─────────────────────────────────────────
        $dramas = [
            [
                'title' => 'The CEO\'s Secret Wife',
                'synopsis' => 'After a drunken night in Las Vegas, ordinary office worker Amina wakes up married to the most powerful CEO in East Africa. As they navigate a fake marriage that starts to feel real, secrets from both their pasts threaten everything.',
                'category' => 'Romance',
                'tags' => ['CEO', 'Contract Marriage', 'Strong Female Lead'],
                'content_rating' => 'PG-13',
                'release_year' => 2025,
                'total_episodes' => 8,
                'is_featured' => true,
                'is_trending' => true,
                'is_new_release' => true,
                'is_free' => false,
                'view_count' => 125000,
                'like_count' => 8700,
                'rating' => 4.6,
                'rating_count' => 2340,
            ],
            [
                'title' => 'Revenge of the Forgotten Princess',
                'synopsis' => 'Betrayed by her family and left for dead, Princess Zara returns five years later with a new identity and a burning desire for revenge. But as she gets closer to her enemies, she discovers the truth is far darker than she imagined.',
                'category' => 'Thriller',
                'tags' => ['Revenge', 'Secret Identity', 'Palace'],
                'content_rating' => 'PG-13',
                'release_year' => 2025,
                'total_episodes' => 10,
                'is_featured' => true,
                'is_trending' => true,
                'is_new_release' => false,
                'is_free' => false,
                'view_count' => 230000,
                'like_count' => 15200,
                'rating' => 4.8,
                'rating_count' => 5600,
            ],
            [
                'title' => 'Campus Love Wars',
                'synopsis' => 'The most popular boy in university bets his friends he can make any girl fall for him. His target? The ice-cold scholarship student who couldn\'t care less. What starts as a game quickly becomes the most real thing in his life.',
                'category' => 'Romance',
                'tags' => ['Campus', 'Enemies to Lovers', 'Comedy'],
                'content_rating' => 'PG',
                'release_year' => 2026,
                'total_episodes' => 12,
                'is_featured' => false,
                'is_trending' => true,
                'is_new_release' => true,
                'is_free' => false,
                'view_count' => 95000,
                'like_count' => 6100,
                'rating' => 4.3,
                'rating_count' => 1800,
            ],
            [
                'title' => 'The Billionaire\'s Hidden Heir',
                'synopsis' => 'When a young single mother applies for a cleaning job at a luxury penthouse, she has no idea the owner is the father of her child — the man who disappeared without a trace five years ago.',
                'category' => 'Drama',
                'tags' => ['Billionaire', 'Second Chance', 'Heartbreak'],
                'content_rating' => 'PG-13',
                'release_year' => 2025,
                'total_episodes' => 8,
                'is_featured' => true,
                'is_trending' => false,
                'is_new_release' => false,
                'is_free' => false,
                'view_count' => 310000,
                'like_count' => 21000,
                'rating' => 4.7,
                'rating_count' => 7200,
            ],
            [
                'title' => 'Moonlight Shadows',
                'synopsis' => 'In a small Tanzanian village, strange events begin after a mysterious new family moves in during the full moon. Local teacher Baraka must uncover the truth before the next full moon — or lose everyone he loves.',
                'category' => 'Horror',
                'tags' => ['Werewolf', 'Suspense', 'Emotional'],
                'content_rating' => 'R',
                'release_year' => 2025,
                'total_episodes' => 6,
                'is_featured' => false,
                'is_trending' => false,
                'is_new_release' => true,
                'is_free' => false,
                'view_count' => 67000,
                'like_count' => 4200,
                'rating' => 4.1,
                'rating_count' => 980,
            ],
            [
                'title' => 'Love Beyond Time',
                'synopsis' => 'A modern-day archaeologist accidentally activates an ancient artifact and is thrown back 500 years to the Swahili Coast. She must find a way home, but how can she leave when she\'s fallen for a kingdom\'s warrior prince?',
                'category' => 'Fantasy',
                'tags' => ['Time Travel', 'Love Triangle', 'Strong Female Lead'],
                'content_rating' => 'PG-13',
                'release_year' => 2026,
                'total_episodes' => 10,
                'is_featured' => true,
                'is_trending' => true,
                'is_new_release' => true,
                'is_free' => false,
                'view_count' => 180000,
                'like_count' => 12500,
                'rating' => 4.9,
                'rating_count' => 4100,
            ],
            [
                'title' => 'The Mafia Boss Loves Me',
                'synopsis' => 'Kidnapped by the most feared mafia boss in the city because she witnessed a crime, ordinary nurse Halima must survive 30 days in captivity. But as the days pass, the cold-hearted boss starts showing a side nobody has ever seen.',
                'category' => 'Action',
                'tags' => ['Mafia', 'Cold Male Lead', 'Enemies to Lovers'],
                'content_rating' => 'R',
                'release_year' => 2025,
                'total_episodes' => 8,
                'is_featured' => false,
                'is_trending' => true,
                'is_new_release' => false,
                'is_free' => false,
                'view_count' => 290000,
                'like_count' => 19800,
                'rating' => 4.5,
                'rating_count' => 6300,
            ],
            [
                'title' => 'My Accidental Roommate',
                'synopsis' => 'Due to a booking error, two strangers — a neat-freak lawyer and a messy artist — are forced to share an apartment for 3 months. Chaos, hilarity, and an unexpected romance ensue.',
                'category' => 'Comedy',
                'tags' => ['Enemies to Lovers', 'Comedy', 'Slice of Life'],
                'content_rating' => 'PG',
                'release_year' => 2026,
                'total_episodes' => 10,
                'is_featured' => false,
                'is_trending' => false,
                'is_new_release' => true,
                'is_free' => true,
                'view_count' => 45000,
                'like_count' => 3200,
                'rating' => 4.2,
                'rating_count' => 900,
            ],
            [
                'title' => 'Dynasty of Wolves',
                'synopsis' => 'Three brothers fight over the family empire after their father\'s sudden death. But the real power lies with their mother, who has been pulling strings all along. A tale of power, betrayal, and family loyalty.',
                'category' => 'Drama',
                'tags' => ['Billionaire', 'Revenge', 'Suspense'],
                'content_rating' => 'PG-13',
                'release_year' => 2025,
                'total_episodes' => 12,
                'is_featured' => true,
                'is_trending' => true,
                'is_new_release' => false,
                'is_free' => false,
                'view_count' => 410000,
                'like_count' => 28000,
                'rating' => 4.8,
                'rating_count' => 9100,
            ],
            [
                'title' => 'Second Chance at Love',
                'synopsis' => 'High school sweethearts Juma and Neema meet again 10 years later — both successful but emotionally broken. Can they heal old wounds and give love a second chance, or are some wounds too deep?',
                'category' => 'Romance',
                'tags' => ['Second Chance', 'Heartbreak', 'Emotional'],
                'content_rating' => 'PG-13',
                'release_year' => 2026,
                'total_episodes' => 8,
                'is_featured' => false,
                'is_trending' => false,
                'is_new_release' => true,
                'is_free' => false,
                'view_count' => 52000,
                'like_count' => 3800,
                'rating' => 4.4,
                'rating_count' => 1200,
            ],
        ];

        $episodeTitles = [
            'The Beginning', 'Unexpected Encounter', 'Hidden Truth', 'Rising Tension',
            'The Betrayal', 'Broken Trust', 'Turning Point', 'Dark Secrets',
            'The Confrontation', 'Race Against Time', 'Moment of Truth', 'Final Revelation',
        ];

        foreach ($dramas as $index => $dramaData) {
            $category = Category::where('name', $dramaData['category'])->first();
            $tagNames = $dramaData['tags'];

            unset($dramaData['category'], $dramaData['tags']);

            $slug = Str::slug($dramaData['title']);

            $drama = Drama::firstOrCreate(
                ['slug' => $slug],
                array_merge($dramaData, [
                    'slug' => $slug,
                    'category_id' => $category?->id,
                    'status' => 'published',
                    'language' => 'Swahili',
                    'country' => 'Tanzania',
                    'published_at' => now()->subDays(rand(1, 90)),
                    'published_episodes' => $dramaData['total_episodes'],
                    'sort_order' => $index + 1,
                ])
            );

            // Attach tags
            $tagIds = Tag::whereIn('name', $tagNames)->pluck('id')->toArray();
            $drama->tags()->syncWithoutDetaching($tagIds);

            // Create episodes
            for ($ep = 1; $ep <= $dramaData['total_episodes']; $ep++) {
                $videoIndex = ($index + $ep) % count($sampleVideos);
                $isFree = ($ep <= 2); // First 2 episodes free

                Episode::firstOrCreate(
                    ['drama_id' => $drama->id, 'episode_number' => $ep],
                    [
                        'drama_id' => $drama->id,
                        'title' => $episodeTitles[min($ep - 1, count($episodeTitles) - 1)],
                        'slug' => $slug . '-ep-' . $ep,
                        'description' => "Episode {$ep} of {$drama->title}. The story intensifies as new challenges arise.",
                        'video_url' => $sampleVideos[$videoIndex],
                        'duration' => rand(180, 600), // 3-10 minutes
                        'episode_number' => $ep,
                        'season_number' => 1,
                        'is_free' => $isFree,
                        'coin_price' => $isFree ? 0 : rand(5, 15),
                        'status' => 'published',
                        'view_count' => rand(1000, 50000),
                        'like_count' => rand(100, 5000),
                        'sort_order' => $ep,
                        'published_at' => now()->subDays(rand(0, 60)),
                    ]
                );
            }
        }

        // ── Banners ────────────────────────────────────────
        $featuredDramas = Drama::where('is_featured', true)->take(4)->get();

        foreach ($featuredDramas as $i => $drama) {
            Banner::firstOrCreate(
                ['title' => $drama->title . ' — Now Streaming'],
                [
                    'title' => $drama->title . ' — Now Streaming',
                    'image' => '',
                    'link_type' => 'drama',
                    'link_value' => (string) $drama->id,
                    'sort_order' => $i + 1,
                    'is_active' => true,
                    'starts_at' => now(),
                    'ends_at' => now()->addMonths(3),
                ]
            );
        }

        // Extra promo banners
        Banner::firstOrCreate(
            ['title' => '🎁 Claim Your Daily Coins!'],
            [
                'title' => '🎁 Claim Your Daily Coins!',
                'image' => '',
                'link_type' => 'screen',
                'link_value' => 'daily_reward',
                'sort_order' => 5,
                'is_active' => true,
            ]
        );

        Banner::firstOrCreate(
            ['title' => '💎 Get VIP — Unlock Everything!'],
            [
                'title' => '💎 Get VIP — Unlock Everything!',
                'image' => '',
                'link_type' => 'screen',
                'link_value' => 'subscription',
                'sort_order' => 6,
                'is_active' => true,
            ]
        );

        $this->command->info('✅ Seeded ' . Drama::count() . ' dramas, ' . Episode::count() . ' episodes, ' . Banner::count() . ' banners.');
    }
}
