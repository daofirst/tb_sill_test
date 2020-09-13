<?php


namespace App\Http\Controllers;


use App\Jobs\NotifyTbOneJob;
use App\Utils\Sill\LimitFlow;

class IndexController extends Controller
{

    /**
     * index
     * @return string
     */
    public function index()
    {

//        $items = \RedisManager::connection('default')->command('ZRANGEBYSCORE', ['qps_throttle:notify_tb', 1599973226, 1599973226]);
//        $count = count($items);
//
//        dd($count);


        $limit = LimitFlow::getInstance();
        $limit->setQps('notify_tb', 250);

        for ($i = 0; $i < 3000; $i++) {
            $this->dispatch(new NotifyTbOneJob());
        }

        return "success";
    }

}
