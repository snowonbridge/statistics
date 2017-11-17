<?php
/**
 * Created by PhpStorm.
 * User: nihao
 * Date: 17-9-9
 * Time: 下午2:29
 */

require_once 'Autoloader.php';
define('CFG_PATH',__DIR__.'/Config/');
define('RUNTIME_PATH',__DIR__.'/Runtime/');
define('DEBUG',true);
\Workerman\Worker::$logFile = __DIR__.'/Runtime/worker.log';
\Workerman\Worker::$pidFile=__DIR__.'/Runtime/worker.pid';
\Workerman\Worker::$stdoutFile=__DIR__.'/Runtime/stdout_cli.log';
//处理队列中任务的服务进程
$work = new \Workerman\Worker();
$work->count = 4;
$work->name = 'worker_statistics_cli';

$work->onWorkerStart = function($worker)
{
    \Workerman\Lib\Timer::add(0.01,function(){
        \Workerman\Controller\Task::instance()->addLog();
    },null,true);
//
    \Workerman\Lib\Timer::add(3600*6,function(){
        \Workerman\Controller\Task::instance()->autoCreateTable();
    },null,true);
    \Workerman\Lib\Timer::add(3600*6,'deleteLogs',null,true);
};
//定时删除日志文件
 function deleteLogs()
{
    $dir = RUNTIME_PATH;
    $it = new \DirectoryIterator($dir);
    foreach($it as  $file)
    {
        if(!$file->isDot())
        {
            if(strpos($file->getFilename(),'app-') !==false)
            {

                if($file->getCTime() <=strtotime('-3 day'))
                {
                    unlink($file->getPathname());
                }

            }
        }
        if($file->getFilename() == 'webServer.log' || $file->getFilename() == 'worker.log')
        {
            file_put_contents($file->getPathname(),'');
        }
    }
}

\Workerman\Worker::runAll();
