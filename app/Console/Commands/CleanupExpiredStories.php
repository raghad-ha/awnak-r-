<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Story;

class CleanupExpiredStories extends Command
{
    protected $signature = 'stories:cleanup';
    protected $description = 'Delete expired stories and their media files';

    public function handle(): int
    {
        $expired = Story::with('media')->where('expires_at', '<=', now())->get();

        foreach ($expired as $story) {
            foreach ($story->media as $m) {
                if ($m->path) Storage::disk('public')->delete($m->path);
                if ($m->thumbnail_path) Storage::disk('public')->delete($m->thumbnail_path);
            }
            $story->delete(); // cascade deletes media rows if FK cascade exists
        }

        $this->info("Deleted {$expired->count()} expired stories.");
        return 0;
    }
}