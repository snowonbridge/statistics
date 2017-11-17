<?php
/**
 * Created by PhpStorm.
 * User: nihao
 * Date: 17-9-9
 * Time: 下午2:56
 */

namespace Workerman\Lib;


class Okey {
    const EX_ONE_DAY=86400;//3600*24
    const EX_ONE_HOUR=3600;//3600
    const EX_ONE_MINUTE=60;//
    const EX_ONE_MONTH=2678400;


    public static function currentLogTable()
    {
        return "STATISTICS_1";
    }

    /**
     * @desc 埋点数据队列名称
     * @return string
     */
    public static function buryDataList()
    {
        return "STATISTICS_2_BURYDATALIST";
    }
    public static function reportRowCounts($table)
    {
        return "STATISTICS_3_{$table}";
    }
    public static function currentTable()
    {
        return "STATISTICS_5";
    }
    public static function tableCounts($serial)
    {
        return "STATISTICS_6_{$serial}";
    }
} 