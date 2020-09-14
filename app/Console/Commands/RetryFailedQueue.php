<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Artisan;

class RetryFailedQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'failad-queue:retry-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '重试所有失败的任务';

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

        $perPage = 15;

        while(true) {
            $ids = \DB::table('failed_jobs')->orderBy('id', 'asc')->limit($perPage)->pluck('id')->toArray();

            if (count($ids) < 1) {
                break;
            }

            foreach ($ids as $id) {
                try {
                    $this->info('retry job:' . $id);
                    Artisan::call('queue:retry ' . $id);
                } catch (\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
        }
    }
}
