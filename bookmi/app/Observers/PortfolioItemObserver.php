<?php

namespace App\Observers;

use App\Models\PortfolioItem;
use App\Services\ActivityLogger;

class PortfolioItemObserver
{
    public function created(PortfolioItem $item): void
    {
        ActivityLogger::log('portfolio.item_added', $item, [
            'talent_profile_id' => $item->talent_profile_id,
            'media_type'        => $item->media_type,
            'link_platform'     => $item->link_platform,
        ]);
    }

    public function deleted(PortfolioItem $item): void
    {
        ActivityLogger::log('portfolio.item_removed', $item, [
            'talent_profile_id' => $item->talent_profile_id,
            'media_type'        => $item->media_type,
        ]);
    }
}
