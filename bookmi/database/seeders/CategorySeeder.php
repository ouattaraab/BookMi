<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'DJ', 'color_hex' => '#7C4DFF', 'description' => 'DJ et mixage musical'],
            ['name' => 'Groupe Musical', 'color_hex' => '#1565C0', 'description' => 'Groupes et orchestres'],
            ['name' => 'Chanteur', 'color_hex' => '#E91E63', 'description' => 'Artistes solo et chanteurs'],
            ['name' => 'Humoriste', 'color_hex' => '#FF4081', 'description' => 'Humoristes et stand-up'],
            ['name' => 'Danseur', 'color_hex' => '#00BFA5', 'description' => 'Danseurs et troupes de danse'],
            ['name' => 'MC / Animateur', 'color_hex' => '#FFB300', 'description' => 'Maîtres de cérémonie et animateurs'],
            ['name' => 'Photographe', 'color_hex' => '#536DFE', 'description' => 'Photographes événementiels'],
            ['name' => 'Vidéaste', 'color_hex' => '#00ACC1', 'description' => 'Vidéastes et réalisateurs'],
            ['name' => 'Décorateur', 'color_hex' => '#FF6E40', 'description' => 'Décorateurs événementiels'],
            ['name' => 'Maquilleur', 'color_hex' => '#AB47BC', 'description' => 'Maquilleurs et coiffeurs'],
            ['name' => 'Traiteur', 'color_hex' => '#66BB6A', 'description' => 'Traiteurs et services culinaires'],
            ['name' => 'Magicien', 'color_hex' => '#5C6BC0', 'description' => 'Magiciens et illusionnistes'],
        ];

        foreach ($categories as $data) {
            $data['slug'] = Str::slug($data['name']);
            Category::firstOrCreate(
                ['slug' => $data['slug']],
                $data,
            );
        }
    }
}
