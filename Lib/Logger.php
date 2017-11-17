<?php
/**
 * Created by PhpStorm.
 * User: nihao
 * Date: 17-9-9
 * Time: 下午2:58
 */

namespace Workerman\Lib;


class Logger {
    /**
     *
     * @param $data
     * @param string $category
     * @param string $level
     */
    static function write($data,$category='default',$level="info")
    {
        $fileName = RUNTIME_PATH . 'app-'.date("Y-m-d",time()).'.log';
        $time =  $date = date("Y-m-d H:i:s");
        $content = "\n[{$category} {$level} {$time}] ".var_export($data,true);
        file_put_contents($fileName,$content,FILE_APPEND);
    }
} 