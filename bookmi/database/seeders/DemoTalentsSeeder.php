<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoTalentsSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Catégories ────────────────────────────────────────────────
        $categories = [
            ['name' => 'DJ',          'color_hex' => '#5865F2', 'description' => 'Disc-jockeys et mixeurs'],
            ['name' => 'Musicien',    'color_hex' => '#9B59B6', 'description' => 'Instrumentistes et groupes'],
            ['name' => 'Chanteur',    'color_hex' => '#4CAF50', 'description' => 'Artistes vocaux'],
            ['name' => 'Danseur',     'color_hex' => '#E91E63', 'description' => 'Danseurs et troupes'],
            ['name' => 'Photographe', 'color_hex' => '#00BCD4', 'description' => 'Photographes professionnels'],
        ];

        $catModels = [];
        foreach ($categories as $catData) {
            $catModels[$catData['name']] = Category::firstOrCreate(
                ['name' => $catData['name']],
                [
                    'description' => $catData['description'],
                    'color_hex'   => $catData['color_hex'],
                    'slug'        => \Illuminate\Support\Str::slug($catData['name']),
                ]
            );
        }

        // ── 2. Talents (user + profil) ───────────────────────────────────
        $talents = [
            [
                'user' => [
                    'first_name' => 'Kofi',
                    'last_name'  => 'Mensah',
                    'email'      => 'kofi.mensah@bookmi.test',
                    'phone'      => '+22507000001',
                ],
                'profile' => [
                    'stage_name'    => 'DJ Kofi',
                    'bio'           => 'DJ professionnel avec plus de 10 ans d\'expérience sur les scènes d\'Abidjan. Spécialiste afrobeats, dancehall et hip-hop.',
                    'city'          => 'Abidjan',
                    'cachet_amount' => 150000,
                    'category'      => 'DJ',
                    'average_rating'=> 4.8,
                    'total_bookings'=> 42,
                    'is_verified'   => true,
                    'talent_level'  => 'confirme',
                ],
            ],
            [
                'user' => [
                    'first_name' => 'Ibrahim',
                    'last_name'  => 'Koné',
                    'email'      => 'ibrahim.kone@bookmi.test',
                    'phone'      => '+22507000002',
                ],
                'profile' => [
                    'stage_name'    => 'Ibrahim K',
                    'bio'           => 'Guitariste et compositeur passionné, Ibrahim fusionne le jazz africain avec la musique traditionnelle ivoirienne.',
                    'city'          => 'Abidjan',
                    'cachet_amount' => 80000,
                    'category'      => 'Musicien',
                    'average_rating'=> 4.5,
                    'total_bookings'=> 18,
                    'is_verified'   => true,
                    'talent_level'  => 'confirme',
                ],
            ],
            [
                'user' => [
                    'first_name' => 'Aya',
                    'last_name'  => 'Touré',
                    'email'      => 'aya.toure@bookmi.test',
                    'phone'      => '+22507000003',
                ],
                'profile' => [
                    'stage_name'    => 'Aya Touré',
                    'bio'           => 'Chanteuse aux influences gospel et R&B. Sa voix puissante transcende les événements les plus exigeants.',
                    'city'          => 'Bouaké',
                    'cachet_amount' => 120000,
                    'category'      => 'Chanteur',
                    'average_rating'=> 4.9,
                    'total_bookings'=> 35,
                    'is_verified'   => true,
                    'talent_level'  => 'elite',
                ],
            ],
            [
                'user' => [
                    'first_name' => 'Mariama',
                    'last_name'  => 'Diallo',
                    'email'      => 'mariama.diallo@bookmi.test',
                    'phone'      => '+22507000004',
                ],
                'profile' => [
                    'stage_name'    => 'Mariama D',
                    'bio'           => 'Danseuse contemporaine et traditionnelle, Mariama anime les cérémonies, mariages et soirées corporate avec éclat.',
                    'city'          => 'Abidjan',
                    'cachet_amount' => 60000,
                    'category'      => 'Danseur',
                    'average_rating'=> 4.7,
                    'total_bookings'=> 27,
                    'is_verified'   => true,
                    'talent_level'  => 'confirme',
                ],
            ],
            [
                'user' => [
                    'first_name' => 'Alex',
                    'last_name'  => 'Brou',
                    'email'      => 'alex.brou@bookmi.test',
                    'phone'      => '+22507000005',
                ],
                'profile' => [
                    'stage_name'    => 'Alex Brou Photography',
                    'bio'           => 'Photographe professionnel spécialisé dans les événements, mariages et portraits. Équipement haut de gamme, livraison rapide.',
                    'city'          => 'San-Pédro',
                    'cachet_amount' => 75000,
                    'category'      => 'Photographe',
                    'average_rating'=> 4.6,
                    'total_bookings'=> 53,
                    'is_verified'   => true,
                    'talent_level'  => 'elite',
                ],
            ],
        ];

        foreach ($talents as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['user']['email']],
                [
                    'first_name'        => $data['user']['first_name'],
                    'last_name'         => $data['user']['last_name'],
                    'phone'             => $data['user']['phone'],
                    'password'          => Hash::make('Password123!'),
                    'email_verified_at' => now(),
                    'is_active'         => true,
                ]
            );

            $category = $catModels[$data['profile']['category']];

            if (! TalentProfile::where('user_id', $user->id)->exists()) {
                TalentProfile::create([
                    'user_id'        => $user->id,
                    'category_id'    => $category->id,
                    'stage_name'     => $data['profile']['stage_name'],
                    'bio'            => $data['profile']['bio'],
                    'city'           => $data['profile']['city'],
                    'cachet_amount'  => $data['profile']['cachet_amount'],
                    'average_rating' => $data['profile']['average_rating'],
                    'total_bookings' => $data['profile']['total_bookings'],
                    'is_verified'    => $data['profile']['is_verified'],
                    'talent_level'   => $data['profile']['talent_level'],
                    'profile_completion_percentage' => 80,
                ]);
            }
        }

        $this->command->info('✓ 5 catégories et 5 talents créés.');
    }
}
