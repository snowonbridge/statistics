<?php 
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

use Workerman\Worker;
use \Workerman\WebServer;
// 检查扩展
if(!extension_loaded('pcntl'))
{
    exit("Please install pcntl extension. See http://doc3.workerman.net/install/install.html\n");
}

if(!extension_loaded('posix'))
{
    exit("Please install posix extension. See http://doc3.workerman.net/install/install.html\n");
}

// 标记是全局启动

require_once __DIR__.'/Autoloader.php';
require_once __DIR__.'/Twig/lib/Twig/Autoloader.php';
define('DEBUG',true);
define('IS_WEB_SERVER',true);

define('CFG_PATH',__DIR__.'/Config/');
define('RUNTIME_PATH',__DIR__.'/Runtime/');
define('VIEW_PATH',__DIR__.'/View/');
define('__PUBLIC__','/Public/');

// WebServer
$web = new WebServer("http://0.0.0.0:55757");
$web->name = 'worker_statistics_web';
$web->addRoot('sts.soul.com', __DIR__);
$monitor_dir = realpath(__DIR__);
//$webserver->reloadable = false;
$last_mtime = time();
$web->onWorkerStart = function(){
    global $monitor_dir;
    // watch files only in daemon mode
    if(!\Workerman\Worker::$daemonize )
    {
        // chek mtime of files per second
        \Workerman\Lib\Timer::add(1, 'check_files_change', array($monitor_dir),true);
    }
};
function check_files_change($monitor_dir)
{
    global $last_mtime;
    // recursive traversal directory
    $dir_iterator = new RecursiveDirectoryIterator($monitor_dir);
    $iterator = new RecursiveIteratorIterator($dir_iterator);
    foreach ($iterator as $file)
    {
        // only check php files

        // check mtime
        if($last_mtime < $file->getMTime())
        {
            echo $file." update and reload\n";
            // send SIGUSR1 signal to master process for reload
            posix_kill(posix_getppid(), SIGUSR1);
            $last_mtime = $file->getMTime();
            break;
        }
    }

}
\Workerman\Worker::$logFile = __DIR__.'/Runtime/webServer.log';
\Workerman\Worker::$pidFile=__DIR__.'/Runtime/webServer.pid';
\Workerman\Worker::$stdoutFile=__DIR__.'/Runtime/stdout.log';
Worker::runAll();
