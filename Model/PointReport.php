<?php
/**
 * Created by PhpStorm.
 * User: nihao
 * Date: 17-10-11
 * Time: 下午6:21
 */

namespace Workerman\Model;


use Workerman\Lib\Logger;
use Workerman\Lib\Model;
use Workerman\Lib\Okey;
use Workerman\Lib\Redis;

class PointReport extends Model{

    /**
     * 需要缓存的数据为，point_report_*系列表的总数，
     * 单个表的总行数
     * 当前表名
     */


    const MAX_TABLE_COUNTS=1;

    public $minTime;
    public function rules()
    {
        return [
            ["page_no","required"],
            ["action_no","required"],
            ["point_no","required"],
            ["counts","required"],
            ["duration","required"],
        ];
    }
    public function add($data)
    {
        $data = isset($data['create_time'])?strtotime(date("Y-m-d H:00:00",$data['create_time'])):mktime(date("H"),0,0,date("m"),date("d"),date("Y"));
        $lastTb = $this->getLastTable();
        if(Redis::getIns()->get(Okey::reportRowCounts($lastTb)))
        {
            Redis::getIns()->set(Okey::reportRowCounts($lastTb),0);
        }
        if (count($data) == count($data, COUNT_RECURSIVE)) {//一维数组
            if(!$this->validate($data,$this->rules()))
            {
                Logger::write("验证不通过".var_export($data,true),__METHOD__,"ERROR");
            }
            Redis::getIns()->incrBy(Okey::reportRowCounts($lastTb),1);
            return $this->insert($lastTb,$data);
        } else {//二维数组
            Redis::getIns()->incrBy(Okey::reportRowCounts($lastTb),count($data));
            return $this->insertBatch($lastTb,$data);
        }

    }
    public function getOne($data)
    {
        if(!isset($data['page_no']) || !isset($data['action_no'])  || !isset($data['point_no']) )
        {
            Logger::write("参数缺失".var_export($data,true),__METHOD__,"ERROR");
            return false;
        }
        return $this->getRow($this->table,"page_no=:page_no and action_no=:action_no and point_no=:point_no",
            [":page_no"=>$data['page_no'],":action_no"=>$data['action_no'],":point_no"=>$data['point_no']]);
    }
    public function updateReport($data)
    {
        if(!$this->validate($data,$this->rules()))
        {
            Logger::write("验证不通过".var_export($data,true),__METHOD__,"ERROR");
            return false;
        }
        $r = $this->getOne($data);
        if(!$r)
        {
            $b1 = $this->add($data);
            if(!$b1)
            {
                Logger::write("插入失败".var_export($data,true),__METHOD__,"ERROR");
                return false;
            }
        }else{
            $udata['counts'] = $r['counts'] + $data['counts'];
            $udata['duration'] = $r['duration'] + $data['duration'];
            $b2 = $this->update($this->table,"page_no=:page_no and action_no=:action_no and point_no=:point_no",
                [":page_no"=>$data['page_no'],":action_no"=>$data['action_no'],":point_no"=>$data['point_no']]);
            if(!$b2)
            {
                Logger::write("插入失败".var_export($data,true),__METHOD__,"ERROR");
                return false;
            }
        }
        return true;
    }


    public function getLastTable()
    {
        if(false ==   Redis::getIns()->get(Okey::currentTable()))
        {
            $tables =  Model::instance()->getTables();
            $point_report_logs = array_filter($tables,function($v){
                if(strstr($v,'point_reports_') !==false)
                    return true;
                else
                    return false;
            });
            $curTb = 'point_reports_'.(count($point_report_logs)-1);
            Redis::getIns()->set(Okey::currentTable(),$curTb);
            $tableRows = $this->tableRows($curTb);
        }else{
            $tableRows = $this->tableRows( Redis::getIns()->get(Okey::currentTable()));
        }

            if($tableRows > self::MAX_TABLE_COUNTS)
            {
                $seq = str_replace('point_reports_','',Redis::getIns()->get(Okey::currentTable()));
                $curTb = 'point_reports_'.($seq+1);
                if(!$this->tableExist($curTb))
                {
                    $from_tb = 'point_reports_0';
                    Redis::getIns()->set(Okey::currentTable(),$curTb);
                    Redis::getIns()->incrBy(Okey::tableCounts('point_reports'),1);
                    Redis::getIns()->set(Okey::reportRowCounts($curTb),0);
                    Model::instance()->createLikeTable($from_tb,$curTb);
                }
                Redis::getIns()->set(Okey::currentTable(),$curTb);
                return $curTb;
            }else{
                return Redis::getIns()->get(Okey::currentTable());
            }


    }
    public function tableRows($table)
    {
        if(false !== Redis::getIns()->get(Okey::reportRowCounts($table)))
        {
            return Redis::getIns()->get(Okey::reportRowCounts($table));
        }else{
            $c = Model::instance()->counts($table);
            Redis::getIns()->set(Okey::reportRowCounts($table),$c);
            return $c;
        }
    }
    public function autoCreateTable()
    {

        $tables =  Model::instance()->getTables();
        $point_report_logs = array_filter($tables,function($v){
            if(strstr($v,'point_reports_') !==false)
                return true;
            else
                return false;
        });
        Redis::getIns()->set(Okey::tableCounts('point_reports'),count($point_report_logs));
        foreach($point_report_logs as $table)
        {
            if(false !== Redis::getIns()->get(Okey::reportRowCounts($table)))
            {
                $counts[] = Redis::getIns()->get(Okey::reportRowCounts($table));
            }else{
                $c = Model::instance()->counts($table);
                if(false !== $c)
                {
                    $counts[] = $c;
                    Redis::getIns()->set(Okey::reportRowCounts($table),$c);
                }

            }

        }
        if(isset($counts) && count($counts)== count($point_report_logs) && min($counts) >= self::MAX_TABLE_COUNTS)
        {
            $max = count($counts);
            $from_tb = 'point_reports_0';
            $dst_tb = 'point_reports_'.($max);
            Redis::getIns()->set(Okey::currentTable(),$dst_tb);

            Redis::getIns()->incrBy(Okey::tableCounts('point_reports'),1);
            Redis::getIns()->set(Okey::reportRowCounts($dst_tb),0);
            Model::instance()->createLikeTable($from_tb,$dst_tb);
        }
        return 1;
    }


    /**
     * 获取查询表的最小时间
     * @param $table
     * @param int $start_time
     * @param int $end_time
     */
    public function getMinCreateTime($table,$page_no=0,$action_no=0,$point_no=0,$end_time=0)
    {
        $r = $this->getRow($table,"page_no=:page_no and action_no=:action_no and point_no=:point_no and create_time<=:create_time",
            [  ":page_no"=>$page_no,":action_no"=>$action_no,":point_no"=>$point_no,":create_time"=>$end_time],"min(create_time) as create_time ");
        return !empty($r['create_time'])?$r['create_time']:0;
    }

    /**
     * @desc 判断之前的表是否有数据
     * @param $table
     * @param $start_time
     * @param $end_time
     * @return bool
     */
    public function hasBeforeTableData($table,$start_time)
    {
        $seq = str_replace('point_reports_','',$table);
        if($seq == 0)
            return false;
        if( $this->minTime >= $start_time )
        {
            return true;
        }else{
            if(!$this->minTime)
            {
                return true;
            }
            return false;
        }
    }
    public function getBeforeTable($table,$page_no,$action_no,$point_no,$start_time,$end_time)
    {
        if($this->hasBeforeTableData($table,$page_no,$action_no,$point_no,$start_time,$end_time))
        {
            $seq = str_replace('point_reports_','',$table);
            return $seq > 0?'point_reports_'.($seq-1):'point_reports_'.($seq);
        }else{
            return false;
        }
    }
    public function getTableData($table,$page_no=0,$action_no=0,$point_no=0,$start_time=0,$end_time=0,$unit=0)
    {
        $list = $this->getRows($table,"page_no=:page_no and action_no=:action_no and point_no=:point_no and create_time>=:start_time and create_time<=:end_time order by create_time desc",[
            ":page_no"=>$page_no,":action_no"=>$action_no,":point_no"=>$point_no,":start_time"=>$start_time,":end_time"=>$end_time
        ],['counts','duration','create_time']);
        if(!$list)
        {
            Logger::write('该表无查询数据',__METHOD__,"INFO");
            return [];
        }

        reset($list);
        $f = current($list);
        $this->minTime = $f['create_time'];
        return $list;
    }
    public function getBuryData($page_no=0,$action_no=0,$point_no=0,$start_time=0,$end_time=0,$unit=0)
    {

        if(!$page_no || !$action_no || !$point_no)
            return false;
        $curTb = $this->getLastTable();

        $result=[];
        while(1)
        {
            if($curTb !== false)
            {
                $list = $this->getTableData($curTb,$page_no,$action_no,$point_no,$start_time,$end_time,$unit);

                $result = array_merge($result,$list);
                unset($list);
                $tb   = $this->getBeforeTable($curTb,$page_no,$action_no,$point_no,$start_time,$end_time);

                $curTb = $tb;
            }else{
                break;
            }
        }

        return Unit::instance()->getUnitDataList($result,$unit);
    }

} 