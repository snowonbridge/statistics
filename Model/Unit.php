<?php
/**
 * Created by PhpStorm.
 * User: nihao
 * Date: 17-10-12
 * Time: 上午11:40
 */

namespace Workerman\Model;


use Workerman\Lib\Logger;
use Workerman\Lib\Model;

/**
 * @desc 处理元数据为列表单位数据
 * Class Unit
 * @package Workerman\Model
 */
class Unit extends Model{

    const UNIT_HOUR=1;
    const UNIT_DAY=2;
    const UNIT_WEEK=3;
    const UNIT_MONTH=4;
    public function getHourList($list)
    {
        //数据库存的时间是按小时存的，不用处理
        foreach($list as $item)
        {
            if(!isset($result[date("Y-m-d H:00:00",$item['create_time'])]))
            {
                $result[date("Y-m-d H:00:00",$item['create_time'])]['counts'] = 0;
                $result[date("Y-m-d H:00:00",$item['create_time'])]['duration'] = 0;
                $result[date("Y-m-d H:00:00",$item['create_time'])]['times'] = 0;
            }
            $result[date("Y-m-d H:00:00",$item['create_time'])]['counts'] += $item['counts'] ;
            $result[date("Y-m-d H:00:00",$item['create_time'])]['duration'] += $item['duration'] ;
            $result[date("Y-m-d H:00:00",$item['create_time'])]['times'] ++;
        }
        return !empty($result)?$result:[];
    }
    public function getDayList($list)
    {
        foreach($list as $item)
        {
            if(!isset($result[date("Y-m-d",$item['create_time'])]))
            {
                $result[date("Y-m-d",$item['create_time'])]['counts'] = 0;
                $result[date("Y-m-d",$item['create_time'])]['duration'] = 0;
                $result[date("Y-m-d",$item['create_time'])]['times'] =0 ;
            }
            $result[date("Y-m-d",$item['create_time'])]['counts'] += $item['counts'] ;
            $result[date("Y-m-d",$item['create_time'])]['times'] ++ ;
            $result[date("Y-m-d",$item['create_time'])]['duration'] += $item['duration'] ;
        }
        $result = $this->getResultList($result);
        return isset($result)?$result:[];
    }
    public function getWeekList($list)
    {

        foreach($list as $item)
        {
            $data = $item['create_time'];
            $date_now=date('j',strtotime($data)); //得到今天是几号
            $cal_result=ceil(($date_now)/7); //计算是第几个星期几
            if(!isset($result[date("Y-m-d",$item['create_time'])]))
            {
                $result[date("Y-m {$cal_result}周",$item['create_time'])]['counts'] = 0;
                $result[date("Y-m {$cal_result}周",$item['create_time'])]['duration'] = 0;
                $result[date("Y-m {$cal_result}周",$item['create_time'])]['times'] = 0;
            }
            $result[date("Y-m {$cal_result}周",$item['create_time'])]['counts'] += $item['counts'] ;
            $result[date("Y-m {$cal_result}周",$item['create_time'])]['times'] ++;
            $result[date("Y-m {$cal_result}周",$item['create_time'])]['duration'] += $item['duration'] ;
        }
        $result = $this->getResultList($result);
        return isset($result)?$result:[];
    }
    public function getMonthList($list)
    {
        foreach($list as $item)
        {
            if(!isset($result[date("Y-m-d",$item['create_time'])]))
            {
                $result[date("Y-m",$item['create_time'])]['counts'] = 0;
                $result[date("Y-m",$item['create_time'])]['duration'] = 0;
                $result[date("Y-m",$item['create_time'])]['times'] =0 ;
            }
            $result[date("Y-m",$item['create_time'])]['counts'] += $item['counts'] ;
            $result[date("Y-m",$item['create_time'])]['times'] ++ ;
            $result[date("Y-m",$item['create_time'])]['duration'] += $item['duration'] ;
        }
        $result = $this->getResultList($result);
        return $result;
    }
    public function getResultList($list)
    {
        foreach($list as $k=>$item)
        {
           if($item['times'] >0)
           {
               $result[$k]['counts'] = round($item['counts']/$item['times'],2);
               $result[$k]['duration'] = round($item['duration']/$item['times'],2);
           }

        }

        return isset($result)?$result:[];
    }
    public function getUnitDataList($list,$unit)
    {

        switch($unit)
        {
            case self::UNIT_HOUR:
                $list = Unit::instance()->getHourList($list);
                ksort($list);
                return $list;
                break;
            case self::UNIT_DAY:
                $list = Unit::instance()->getDayList($list);
                ksort($list);
                return $list;
                break;
            case self::UNIT_WEEK:
                $list =  Unit::instance()->getWeekList($list);
                ksort($list);
                return $list;
                break;
            case self::UNIT_MONTH:
                $list =  Unit::instance()->getMonthList($list);
                ksort($list);
                return $list;
                break;
            default:
                Logger::write("暂不支持该单位精度",__METHOD__,'ERROR');
                return [];
        }
    }
    public function autoGetUnit($start_time,$end_time)
    {
        if($end_time-$start_time <= self::ONE_DAY)
        {
            return self::UNIT_HOUR;
        }elseif($end_time-$start_time <= self::ONE_DAY*5){
            return self::UNIT_HOUR;
        }elseif($end_time-$start_time <= self::ONE_MONTH)
        {
            return self::UNIT_DAY;
        }elseif($end_time-$start_time <= self::HALF_YEAR)
        {
            return self::UNIT_WEEK;
        }else{
            return self::UNIT_MONTH;
        }
    }
    public function getUserUnitDataList($list,$unit)
    {

        switch($unit)
        {

            case self::UNIT_DAY:
                $list = $this->getUserDayList($list);
                ksort($list);
                return $list;
                break;
            case self::UNIT_WEEK:
                $list =  Unit::instance()->getUserWeekList($list);
                ksort($list);
                return $list;
                break;
            case self::UNIT_MONTH:
                $list =  Unit::instance()->getUserMonthList($list);
                ksort($list);
                return $list;
                break;
            default:
                Logger::write("暂不支持该单位精度",__METHOD__,'ERROR');
                return [];
        }
    }
    public function getUserDayList($list)
    {
        foreach($list as $item)
        {
            $item['create_time'] = date("Y-m-d H:i:s", $item['create_time']);
            $item['duration'] = round($item['duration']/60,1);
            $item['page_no'] = PageSetting::instance()->getName($item['page_no']);
            $item['action_no'] = ActionSetting::instance()->getName($item['action_no']);
            $item['point_no'] = PointSetting::instance()->getName($item['point_no']);
            $result[date("Y-m-d",$item['create_time'])][] = $item ;
        }
        return isset($result)?$result:[];
    }
    public function getUserWeekList($list)
    {
        foreach($list as $item)
        {
            $data = $item['create_time'];
            $date_now=date('j',strtotime($data)); //得到今天是几号
            $cal_result=ceil(($date_now)/7); //计算是第几个星期几
            $item['create_time'] = date("Y-m-d H:i:s", $item['create_time']);
            $item['duration'] = round($item['duration']/60,1);
            $result[date("Y-m {$cal_result}周",$item['create_time'])][]= $item ;
        }
        return isset($result)?$result:[];
    }
    public function getUserMonthList($list)
    {
        foreach($list as $item)
        {
            $item['create_time'] = date("Y-m-d H:i:s", $item['create_time']);
            $item['duration'] = round($item['duration']/60,1);
            $result[date("Y-m",$item['create_time'])][] = $item ;
        }
        return isset($result)?$result:[];
    }
    const ONE_DAY= 86400;//3600*24
    const ONE_MONTH= 2678400;//3600*24*31
    const HALF_YEAR= 16070400;//3600*24*31*6
}