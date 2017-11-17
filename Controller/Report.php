<?php
/**
 * Created by PhpStorm.
 * User: nihao
 * Date: 17-10-12
 * Time: 下午7:25
 */

namespace Workerman\Controller;


use Workerman\Lib\Controller;
use Workerman\Model\ActionSetting;
use Workerman\Model\PageSetting;
use Workerman\Model\PointReport;
use Workerman\Model\PointReportLog;
use Workerman\Model\PointSetting;
use Workerman\Model\Unit;
class Report extends Controller{


    public function report()
    {
        $page_no = $this->get['page_no'];
        $action_no = $this->get['action_no'];
        $point_no = $this->get['point_no'];
//        $page_no='23';
//        $action_no='3';
//        $point_no='6';
        $start_time = $this->get['start_time']?strtotime($this->get['start_time']):mktime(0,0,0,date('m'),date('d'),date('Y'));
        $end_time = $this->get['end_time']?strtotime($this->get['end_time']):mktime(23,59,59,date('m'),date('d'),date('Y'));
        $unit = Unit::instance()->autoGetUnit($start_time,$end_time);
        $list = PointReport::instance()->getBuryData($page_no,$action_no,$point_no,$start_time,$end_time,$unit);
        if($list)
        {
            $xAxis = array_keys($list);

            $yCountsValues = array_column($list,'counts');

            $yDurationValues = array_column($list,'duration');

            return $this->render('Report/report.html',
                ['post'=>$this->get,'data'=>['xAxis'=>$xAxis,'yCountsValues'=>$yCountsValues,'yDurationValues'=>$yDurationValues],
                    'select'=>$this->getLists()]);
        }else{
           return $this->render('Report/report.html',
               ['post'=>['page_no'=>$page_no,'action_no'=>$action_no,'point_no'=>$point_no,'start_time'=>date("Y-m-d H:i:s",$start_time),'end_time'=>date("Y-m-d H:i:s",$end_time)],'data'=>['error'=>'无该查询条件的统计数据'],
                   'select'=>$this->getLists()]);
        }


    }


    public function userReport()
    {
        $uid = $this->get['uid']?$this->get['uid']:0;
        $page_no = $this->get['page_no']?$this->get['page_no']:0;
        $action_no = $this->get['action_no']?$this->get['action_no']:0;
        $point_no = $this->get['point_no']?$this->get['point_no']:0;
        $start_time = $this->get['start_time']?strtotime($this->get['start_time']):mktime(0,0,0,date('m'),date('d'),date('Y'));
        $end_time = $this->get['end_time']?strtotime($this->get['end_time']):mktime(23,59,59,date('m'),date('d'),date('Y'));
        $unit = Unit::instance()->autoGetUnit($start_time,$end_time);
        $list = PointReportLog::instance()->getTableData($uid,$page_no,$action_no,$point_no,$start_time,$end_time,$unit);
        if($list)
        {
            return $this->render('Report/user_report.html',['post'=>['uid'=>$uid,'page_no'=>$page_no,'action_no'=>$action_no,'point_no'=>$point_no,'start_time'=>date("Y-m-d H:i:s",$start_time),'end_time'=>date("Y-m-d H:i:s",$end_time)],'data'=>$list,'select'=>$this->getLists()]);
        }else{
            return $this->render('Report/user_report.html',
                ['post'=>$this->get,'data'=>[],
                    'select'=>$this->getLists(),'error'=>'无该查询条件的用户统计数据']);
        }

    }
    public function getLists()
    {
        $list1 = PageSetting::instance()->getAll();
        $list2 = ActionSetting::instance()->getAll();
        $list3 = PointSetting::instance()->getAll();
        return ["page_list"=>$list1,'action_list'=>$list2,'point_list'=>$list3];
    }

} 