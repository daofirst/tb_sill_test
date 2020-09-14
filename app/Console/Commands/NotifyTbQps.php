<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Utils\LimitFlow;

class NotifyTbQps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify-tb:qps {qps}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '设置淘宝QPS';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /** @var string $qps */
        $qps = $this->argument('qps');

        $limitFlow = LimitFlow::getInstance();
        $limitFlow->setQps('notify_tb', (int)$qps);

        $this->info('update success.');
    }
}
