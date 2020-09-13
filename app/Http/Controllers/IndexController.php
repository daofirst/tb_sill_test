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

        $limit = LimitFlow::getInstance();
        $limit->setQps('notify_tb', 200);

        for ($i = 0; $i < 30000; $i++) {
            $this->dispatch(new NotifyTbOneJob());
        }

        return "success";
    }

}
