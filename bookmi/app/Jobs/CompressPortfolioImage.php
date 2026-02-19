<?php

namespace App\Jobs;

use App\Models\PortfolioItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class CompressPortfolioImage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries   = 3;
    public int $backoff = 30;

    public function __construct(
        public readonly PortfolioItem $portfolioItem,
    ) {
    }

    public function handle(): void
    {
        if ($this->portfolioItem->media_type !== 'image') {
            return;
        }

        if ($this->portfolioItem->is_compressed) {
            return;
        }

        try {
            $original = Storage::disk('public')->path($this->portfolioItem->original_path);

            if (! file_exists($original)) {
                Log::warning('CompressPortfolioImage: original file not found', [
                    'portfolio_item_id' => $this->portfolioItem->id,
                    'path'              => $original,
                ]);
                return;
            }

            $compressedPath = str_replace(
                ['uploads/portfolio/', '.'],
                ['uploads/portfolio/compressed/', '_compressed.'],
                $this->portfolioItem->original_path,
            );

            $directory = dirname(Storage::disk('public')->path($compressedPath));
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            Image::make($original)
                ->resize(1200, null, fn ($c) => $c->aspectRatio()->upsize())
                ->save(Storage::disk('public')->path($compressedPath), 80);

            $this->portfolioItem->update([
                'compressed_path' => $compressedPath,
                'is_compressed'   => true,
            ]);

            Log::info('CompressPortfolioImage: success', [
                'portfolio_item_id' => $this->portfolioItem->id,
                'compressed_path'   => $compressedPath,
            ]);
        } catch (\Throwable $e) {
            Log::error('CompressPortfolioImage: failed', [
                'portfolio_item_id' => $this->portfolioItem->id,
                'error'             => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
