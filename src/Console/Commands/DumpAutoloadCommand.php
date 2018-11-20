<?php

namespace Maimake\Largen\Console\Commands;

class DumpAutoloadCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dump_autoload';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'execute composer dump-autoload';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->dump_autoload();
    }
}
