<?php

namespace App\Console\Commands;

use App\Models\TalentProfile;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url as SitemapUrl;

class GenerateSitemap extends Command
{
    protected $signature = 'bookmi:generate-sitemap';

    protected $description = 'Generate the public sitemap.xml file.';

    public function handle(): int
    {
        $sitemap = Sitemap::create();

        // Static pages
        $sitemap->add(
            SitemapUrl::create(url('/'))
                ->setLastModificationDate(now())
                ->setChangeFrequency(SitemapUrl::CHANGE_FREQUENCY_DAILY)
                ->setPriority(1.0)
        );

        $sitemap->add(
            SitemapUrl::create(url('/talents'))
                ->setLastModificationDate(now())
                ->setChangeFrequency(SitemapUrl::CHANGE_FREQUENCY_DAILY)
                ->setPriority(0.9)
        );

        // All verified talent profiles
        TalentProfile::where('is_verified', true)
            ->whereNotNull('slug')
            ->select(['slug', 'updated_at'])
            ->chunkById(200, function ($profiles) use ($sitemap) {
                foreach ($profiles as $profile) {
                    $sitemap->add(
                        SitemapUrl::create(url('/talents/' . $profile->slug))
                            ->setLastModificationDate($profile->updated_at ?? now())
                            ->setChangeFrequency(SitemapUrl::CHANGE_FREQUENCY_WEEKLY)
                            ->setPriority(0.8)
                    );
                }
            });

        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Sitemap generated at ' . public_path('sitemap.xml'));

        return self::SUCCESS;
    }
}
