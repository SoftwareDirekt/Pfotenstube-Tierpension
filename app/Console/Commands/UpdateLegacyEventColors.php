<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Support\EventColor;
use Illuminate\Console\Command;

class UpdateLegacyEventColors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:update-legacy-colors {--chunk=500 : Number of records per chunk}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Normalizes legacy event colors based on the current status palette.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $chunkSize = (int) $this->option('chunk');
        $updated = 0;

        Event::query()
            ->whereIn('status', EventColor::statuses())
            ->chunkById($chunkSize, function ($events) use (&$updated) {
                foreach ($events as $event) {
                    $palette = EventColor::forStatus($event->status);

                    $needsUpdate = $event->backgroundColor !== $palette['backgroundColor']
                        || $event->textColor !== $palette['textColor'];

                    if ($needsUpdate) {
                        $event->backgroundColor = $palette['backgroundColor'];
                        $event->textColor = $palette['textColor'];
                        $event->save();
                        $updated++;
                    }
                }
            });

        $this->info("Legacy events updated: {$updated}");

        return Command::SUCCESS;
    }
}

