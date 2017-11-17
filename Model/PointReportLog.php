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

class PointReportLog extends Model{

    /**
     * show variables like '%secure%';
     * outfile ,load infile需要配置两个变量，ON,priv_path为DATA_DIR
     */
    const DATA_DIR = '/var/lib/mysql-files/';
    const  MIN_TABLE_COUNTS=1;
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

    /**
     * 创建分表，表数量为2的倍数，数据存储方式为：table_no = uid/tables;
     * @return int
     */
    public function autoCreateTable()
    {

        $tables =  Model::instance()->getTables();
        $point_report_logs = array_filter($tables,function($v){
            if(strstr($v,'point_report_log_') !==false)
                return true;
            else
                return false;
        });
        Redis::getIns()->set(Okey::tableCounts('point_report_log'),count($point_report_logs));
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
        $tables = $point_report_logs;
        $i = 1;
        while($i <= $tables)
        {
           $i = $i *2;
        }
        if(isset($counts) && count($point_report_logs) == count($counts) && ($tables- $i/2 != 0))
        {//表数量不是2的指数个次
            Logger::write("数据表point_report_log系列创建表有缺少",__METHOD__,"ERROR");
            if(isset($counts) && count($point_report_logs) == count($counts) )
            {
                $from_tb = 'point_report_log_0';
                $max = $total= count($counts);
                $j=0;
                while($j < $tables- $i/2)
                {
                    $dst_tb = 'point_report_log_'.($max+$j);

                    Redis::getIns()->set(Okey::reportRowCounts($dst_tb),0);
                    Redis::getIns()->incrBy(Okey::tableCounts('point_report_log'),1);
                    $this->createLikeTable($from_tb,$dst_tb);
                    $j++;
                }
            }
            return 1;
        }elseif(isset($counts) && count($point_report_logs) == count($counts) && min($counts) >= self::MIN_TABLE_COUNTS )
        {
            $max = $total= count($counts);
            $from_tb = 'point_report_log_0';
            $j=0;
            while($j < $max)
            {
                $dst_tb = 'point_report_log_'.($total+$j);

                Redis::getIns()->set(Okey::reportRowCounts($dst_tb),0);
                Redis::getIns()->incrBy(Okey::tableCounts('point_report_log'),1);
                $this->createLikeTable($from_tb,$dst_tb);
                $j++;
            }

            return 1;
        }else{
            Logger::write("创建表出现异常 counts:$counts ,".count($point_report_logs).":".count($counts),__METHOD__,"ERROR");
            return 0;
        }

    }
    public function syncData($num=1000)
    {
        $tables =  Model::instance()->getTables();
        $point_report_logs = array_filter($tables,function($v){
            if(strstr($v,'point_report_log_') !==false)
                return true;
            else
                return false;
        });

        $tables = $point_report_logs;
        $i = 1;
        while($i <= $tables)
        {
            $i = $i *2;
        }
        if( $tables- $i/2 != 0)
        {//表数量不是2的指数个次,这个时候先不同步，
           sleep(1);
            return false;
        }else
        {
            $max = $total= count($tables);
            $j=0;
            //$max是2的倍数，只需要小于就行
            while($j < $max/2)
            {
                $from_tb = 'point_report_log_'.($j);
                $dst_tb = 'point_report_log_'.($total+$j);


                $this->syncTableData($from_tb,$dst_tb,$num);
                $j++;
            }

            return 1;
        }
    }
    public function syncTableData($from_tb,$dst_tb,$num=1000)
    {
        /**
         *  select * from point_reports_1  where MOD(page_no,2) = 1 into outfile "/var/lib/mysql-files/fromtable_2.txt";
        show variables like '%secure_file_priv%';
        load   data   infile   "/var/lib/mysql-files/fromtable_2.txt"   into   table   point_reports_0;
         */
        $tbs = $this->getTables();
        $tbs = array_filter($tbs,function($v){
            if(strstr($v,'point_report_log_') !==false)
                return true;
            else
                return false;
        });
        if($num >0)
        {
            $limit = "limit 0,{$num}";
        }else{
            $limit='';
        }
        $file = $this->getDataFile($from_tb,$dst_tb);
        $total_counts = count($tbs);
        $tb_no = str_replace('point_report_log_','',$dst_tb);
        $sql="select * from `{$from_tb}`  where MOD(`uid`,$total_counts) = $tb_no $limit into outfile `{$file}`";
        $this->execRawSql($sql);
        $sql="load   data   infile   `{$file}`   into   table   `{$dst_tb}`";
        $this->execRawSql($sql);
    }
    public function getDataFile($from_tb,$dst_tb)
    {
        if(!file_exists(self::DATA_DIR))
        {
            mkdir(self::DATA_DIR,0755,true);
        }

        $file =  self::DATA_DIR."data_{$from_tb}_to_{$dst_tb}.txt";
        if(file_exists($file))
        {
            @unlink($file);
        }
        return $file;
    }
    public function rules()
    {
        return [
            ["uid","required"],
            ["page_no","required"],
            ["action_no","required"],
            ["point_no","required"],
            ["counts","required"],
            ["duration","required"],
            ["create_time","required"],
        ];
    }
    public function add($data)
    {
        $data = isset($data['create_time'])?strtotime(date("Y-m-d H:00:00",$data['create_time'])):mktime(date("H"),0,0,date("m"),date("d"),date("Y"));
        $lastTb = $this->getUidTable($data['uid']);
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

    /**
     * 获取uid所在的表
     * @param $table
     * @param int $start_time
     * @param int $end_time
     */
    public function getUidTable($uid)
    {
        $tables =  Model::instance()->getTables();

        $tables = array_filter($tables,function($v){
            if(strstr($v,'point_report_log_') !==false)
                return true;
            else
                return false;
        });
        if(count($tables) <= 1)
            return 'point_report_log_0';
        $seq = $uid%count($tables);
        $tb = 'point_report_log_'.$seq;

        return $tb;
    }


    public function getTableData($uid,$page_no=0,$action_no=0,$point_no=0,$start_time=0,$end_time=0,$unit=0)
    {
        $table = $this->getUidTable($uid);

        $list = $this->getRows($table,"uid=:uid and page_no=:page_no and action_no=:action_no and point_no=:point_no and create_time>=:start_time and create_time<=:end_time order by create_time desc",[
            ":uid"=>$uid,":page_no"=>$page_no,":action_no"=>$action_no,":point_no"=>$point_no,":start_time"=>$start_time,":end_time"=>$end_time
        ],["page_no","action_no","point_no","counts","duration"]);
        if(!$list)
        {
            Logger::write('该表无查询数据',__METHOD__,"INFO");
            return false;
        }
        if($unit == Unit::UNIT_HOUR)
        {
            $unit = Unit::UNIT_DAY;
        }
        $list=Unit::instance()->getUserUnitDataList($list,$unit);

        return $list;
    }
} 