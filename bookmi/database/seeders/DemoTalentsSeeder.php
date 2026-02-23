<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ServicePackage;
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
                    'average_rating' => 4.8,
                    'total_bookings' => 42,
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
                    'average_rating' => 4.5,
                    'total_bookings' => 18,
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
                    'average_rating' => 4.9,
                    'total_bookings' => 35,
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
                    'average_rating' => 4.7,
                    'total_bookings' => 27,
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
                    'average_rating' => 4.6,
                    'total_bookings' => 53,
                    'is_verified'   => true,
                    'talent_level'  => 'elite',
                ],
            ],
        ];

        foreach ($talents as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['user']['email']],
                [
                    'first_name'        => $data['user']['first_name'],
                    'last_name'         => $data['user']['last_name'],
                    'phone'             => $data['user']['phone'],
                    'password'          => Hash::make('Password123!'),
                    'email_verified_at' => now(),
                    'phone_verified_at' => now(),
                    'is_active'         => true,
                ]
            );

            // Assign talent role (guard api) if not already assigned
            if (! $user->hasRole('talent', 'api')) {
                $user->assignRole('talent');
            }

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

        // ── 3. Packages par talent ────────────────────────────────────────
        $packages = [
            'kofi.mensah@bookmi.test' => [
                ['name' => 'Set 2h',        'type' => 'essentiel', 'cachet_amount' => 100000, 'duration_minutes' => 120, 'description' => 'Set DJ de 2 heures, parfait pour les cocktails et afterworks.', 'inclusions' => ['Matériel son inclus', 'Playlist personnalisée', '1 déplacement Abidjan'], 'sort_order' => 1],
                ['name' => 'Set 4h',        'type' => 'standard',  'cachet_amount' => 150000, 'duration_minutes' => 240, 'description' => 'Set DJ de 4 heures pour mariages, anniversaires et soirées privées.', 'inclusions' => ['Matériel son + lumières', 'Playlist personnalisée', 'Prise en charge déplacement', 'Animation micro incluse'], 'sort_order' => 2],
                ['name' => 'Soirée complète','type' => 'premium',  'cachet_amount' => 250000, 'duration_minutes' => 360, 'description' => 'Prestation complète 6h avec DJ set, animations et effets spéciaux.', 'inclusions' => ['Son & lumières premium', 'Machine à fumée', 'Animation DJ + MC', 'Prise en charge déplacement CI', 'Séance de répétition offerte'], 'sort_order' => 3],
            ],
            'ibrahim.kone@bookmi.test' => [
                ['name' => 'Duo acoustique', 'type' => 'essentiel', 'cachet_amount' => 60000,  'duration_minutes' => 60,  'description' => 'Duo guitare + voix pour ambiance feutrée lors de vos événements.', 'inclusions' => ['Guitare acoustique', 'Répertoire varié', 'Déplacement Abidjan inclus'], 'sort_order' => 1],
                ['name' => 'Groupe live 3h', 'type' => 'standard',  'cachet_amount' => 120000, 'duration_minutes' => 180, 'description' => 'Groupe de 3 musiciens pour 3h de live jazz et afrobeat.', 'inclusions' => ['Guitare, basse, percussions', 'Matériel son', 'Setlist personnalisée', 'Prise en charge déplacement'], 'sort_order' => 2],
                ['name' => 'Concert privé',  'type' => 'premium',   'cachet_amount' => 200000, 'duration_minutes' => 300, 'description' => 'Concert live privé complet avec groupe de 5 musiciens.', 'inclusions' => ['5 musiciens professionnels', 'Son & lumières scène', 'Backline inclus', 'Répétition générale', 'Prise en charge nationale'], 'sort_order' => 3],
            ],
            'aya.toure@bookmi.test' => [
                ['name' => 'Mini-concert',   'type' => 'essentiel', 'cachet_amount' => 80000,  'duration_minutes' => 45,  'description' => 'Performance vocale de 45 min, idéale pour cérémonies et cocktails.', 'inclusions' => ['Prestation vocale solo', 'Accompagnement musical (piste)', 'Déplacement Bouaké inclus'], 'sort_order' => 1],
                ['name' => 'Récital 1h30',   'type' => 'standard',  'cachet_amount' => 150000, 'duration_minutes' => 90,  'description' => 'Récital gospel & R&B de 1h30 avec musicien accompagnateur.', 'inclusions' => ['Voix + pianiste', 'Répertoire sur mesure', 'Déplacement national', 'Répétition offerte'], 'sort_order' => 2],
                ['name' => 'Soirée gala',    'type' => 'premium',   'cachet_amount' => 300000, 'duration_minutes' => 180, 'description' => 'Prestation gala premium 3h avec musiciens live et choristes.', 'inclusions' => ['Groupe de 4 musiciens', '2 choristes', 'Son scène professionnel', 'Mise en scène lumières', 'Coordination technique'], 'sort_order' => 3],
            ],
            'mariama.diallo@bookmi.test' => [
                ['name' => 'Show 30 min',    'type' => 'essentiel', 'cachet_amount' => 40000,  'duration_minutes' => 30,  'description' => 'Show de danse contemporaine ou traditionnelle de 30 minutes.', 'inclusions' => ['Prestation solo', 'Costume inclus', 'Déplacement Abidjan'], 'sort_order' => 1],
                ['name' => 'Duo 1h',         'type' => 'standard',  'cachet_amount' => 80000,  'duration_minutes' => 60,  'description' => 'Duo de danseuses pour 1h d\'animation lors de vos événements.', 'inclusions' => ['2 danseuses', 'Costumes variés', 'Mise en scène', 'Déplacement national'], 'sort_order' => 2],
                ['name' => 'Troupe 2h',      'type' => 'premium',   'cachet_amount' => 180000, 'duration_minutes' => 120, 'description' => 'Troupe de 5 danseuses pour spectacle complet 2h, idéal mariage & gala.', 'inclusions' => ['5 danseuses', 'Costumes professionnels', 'Chorégraphie personnalisée', 'Répétition incluse', 'Prise en charge nationale'], 'sort_order' => 3],
            ],
            'alex.brou@bookmi.test' => [
                ['name' => 'Reportage 3h',   'type' => 'essentiel', 'cachet_amount' => 50000,  'duration_minutes' => 180, 'description' => 'Reportage photo événementiel de 3h, livraison sous 48h.', 'inclusions' => ['100 photos retouchées', 'Galerie en ligne privée', 'Déplacement San-Pédro inclus'], 'sort_order' => 1],
                ['name' => 'Journée complète','type' => 'standard',  'cachet_amount' => 100000, 'duration_minutes' => 480, 'description' => 'Couverture photo de toute la journée (8h), idéal pour mariages.', 'inclusions' => ['300+ photos retouchées', 'Galerie en ligne', 'Album numérique HD', 'Déplacement national', 'Livraison 72h'], 'sort_order' => 2],
                ['name' => 'Pack mariage',   'type' => 'premium',   'cachet_amount' => 200000, 'duration_minutes' => 600, 'description' => 'Couverture photo+vidéo mariage complète, du matin à la nuit.', 'inclusions' => ['500+ photos retouchées', 'Vidéo souvenir 5 min', 'Album physique 30 pages', '2 photographes', 'Livraison clé USB', 'Prise en charge nationale'], 'sort_order' => 3],
            ],
        ];

        foreach ($packages as $email => $pkgList) {
            $user = User::where('email', $email)->first();
            if (! $user) {
                continue;
            }
            $talentProfile = TalentProfile::where('user_id', $user->id)->first();
            if (! $talentProfile) {
                continue;
            }

            // Only add if no packages exist yet
            if ($talentProfile->servicePackages()->count() === 0) {
                foreach ($pkgList as $pkg) {
                    ServicePackage::create([
                        'talent_profile_id' => $talentProfile->id,
                        'name'              => $pkg['name'],
                        'type'              => $pkg['type'],
                        'cachet_amount'     => $pkg['cachet_amount'],
                        'duration_minutes'  => $pkg['duration_minutes'],
                        'description'       => $pkg['description'],
                        'inclusions'        => $pkg['inclusions'],
                        'is_active'         => true,
                        'sort_order'        => $pkg['sort_order'],
                    ]);
                }
            }
        }

        $this->command->info('✓ 5 catégories, 5 talents et leurs packages créés.');
    }
}
