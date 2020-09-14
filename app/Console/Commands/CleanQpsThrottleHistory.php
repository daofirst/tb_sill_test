<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \RedisManager;

class CleanQpsThrottleHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qps-throttle:clear-history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '清除qps限制历史记录';

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
        $redis = RedisManager::connection('default');
        $redisKeys = $redis->command('KEYS', ['qps_throttle:*']);
        $redisPrefix = config('database.redis.options.prefix');

        foreach($redisKeys as $value) {
            $keyName = ltrim($value, $redisPrefix);
            $min = 0;
            $max = now()->subDays(2)->timestamp;
            
            $redis->command('ZREMRANGEBYSCORE', [$keyName, $min, $max]);
        }

        $this->info('clear success');

    }
}
