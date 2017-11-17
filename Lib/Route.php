<?php
/**
 * Created by PhpStorm.
 * User: nihao
 * Date: 17-9-11
 * Time: 上午9:49
 */

namespace Workerman\Lib;


use Workerman\Protocols\Http;

class Route {

    public  static function run()
    {

        $default_index = '/index/index';
        if(false === ( $pos =strpos($_SERVER['REQUEST_URI'],'?') ))
        {
            $route = $_SERVER['REQUEST_URI'];
        }else{
            $route = substr($_SERVER['REQUEST_URI'],0,$pos);
        }

        if($route =='/')
            $route = $default_index;
        list($c,$a) = explode('/',trim($route,'/'));

        $c = str_replace('-',' ',$c);
        $c = ucwords($c);
        $c = str_replace(' ','',$c);
        $a = str_replace('-',' ',$a);
        $a = ucwords($a);
        $a = lcfirst(str_replace(' ','',$a));
        $c = '\\Workerman\\Controller\\'.$c;
        if(!class_exists($c))
        {
            \Workerman\Protocols\Http::end('未找到这个类'.$c);
        }
        $class = new $c();
        if(!method_exists($class,$a))
        {
            \Workerman\Protocols\Http::end('未找到这个类方法'.$a);
        }

        $result = $class->$a();

        if(is_array($result) ||  is_object($result))
        {

            \Workerman\Protocols\Http::end(json_encode($result));
        }else{

            \Workerman\Protocols\Http::end($result);
        }
    }
} 