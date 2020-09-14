<?php


namespace App\Http\Controllers;


use App\Jobs\NotifyTbOneJob;
use App\Utils\LimitFlow;

class IndexController extends Controller
{

    /**
     * index
     * @return string
     */
    public function index()
    {

        $limit = LimitFlow::getInstance();
        $limit->setQps('notify_tb', 100);

        for ($i = 0; $i < 1000; $i++) {
            $this->dispatch((new NotifyTbOneJob())->onQueue('high'));
        }

        return "success";
    }

}
