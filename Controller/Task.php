<?php
/**
 * Created by PhpStorm.
 * User: nihao
 * Date: 17-10-11
 * Time: 下午3:08
 */

namespace Workerman\Controller;



use Workerman\Lib\Controller;
use Workerman\Lib\Model;
use Workerman\Lib\Okey;
use Workerman\Lib\Redis;
use Workerman\Model\PointReportLog;
use Workerman\Model\PointReport;

class Task extends Controller{



    public function addLog()
    {
        $data = Redis::getIns('user')->blPop(Okey::buryDataList());
        PointReport::instance()->updateReport($data);
        PointReport::instance()->add($data);
    }
    public function autoCreateTable()
    {
        PointReportLog::instance()->autoCreateTable();
    }
} 