<?php

namespace App\Console\Commands;

use App\Models\Party;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateEventcode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'regenerate:qrcode {--id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate event Qrcode';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $id = $this->option('id');
        Party::query()
            ->when($id, fn($query) => $query->where('id', $id))
            ->get(['id', 'uuid'])
            ->each(function (Party $event) {
                if ($event->uuid == null) {
                    $event->uuid = Str::uuid();
                    $event->save();
                }
                $event->generateQrcode();
            });
        return 0;
    }


}
