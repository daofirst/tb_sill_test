<?php

namespace App\Jobs;

use App\Utils\Sill\LimitFlow;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyTbOneJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \App\Exceptions\LimitFlowException
     */
    public function handle()
    {
        $limitFlow = LimitFlow::getInstance();

        $limitFlow->throttle('notify_tb', function () {

            $client = new Client([
                'base_uri' => 'http://127.0.0.1:8080',
            ]);

            try {
                $client->request('GET', '/');
            } catch (\Exception $e) {
                \Log::info("失败了: " . $e->getMessage());
                $this->release();
            }

        }, function (\Exception $e) {
            \Log::info($e->getMessage());
            $this->release();
        });

    }

    /**
     * Determine the time at which the job should timeout.
     *
     * @return \DateTime
     */
    public function retryUntil()
    {
        return now()->addMinutes(30);
    }
}
