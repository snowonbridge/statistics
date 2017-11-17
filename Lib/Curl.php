<?php
/**
 * Created by PhpStorm.
 * User: nihao
 * Date: 17-9-26
 * Time: 上午11:30
 */

namespace Workerman\Lib;


class Curl {

    /**
     * 基础发起curl请求函数
     * @param int $is_post 是否是post请求
     */
    public static   function postJsonToServer($url,$data,$header=array()) {
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL, $url);//抓取指定网页
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        // 来源一定要设置成来自本站
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);    // 设置超时限制防止死循环

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置为0、1控制是否返回请求头信息
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        if (!empty($data)) {

            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        if(!$header)
        {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json','Content-Length: '.strlen($data)]);
        }
        $data = curl_exec($ch);//运行curl
        curl_close($ch);
        return $data;
    }

    public static function buildData($data=[]){
        $sendData['ret']=200;
        $sendData['code']=0;
        $sendData['msg']='ok';
        $sendData['time']=time();
        $sendData['data'] = $data;
        return $sendData;
    }

    /**
     * @desc 发送用户货币数据到server
     * @param $url
     * @param $data
     * @return mixed
     */
    public static function sendJsonToServer($uid,$chip=0,$diamond=0,$roomcard=0)
    {
        $sendData['card'] = (int)$roomcard;
        $sendData['diamond'] = (int)$diamond;
        $sendData['money'] = (int)$chip;
        $sendData['isSendGood']=0;
        $url = self::SERVER_URL;
        if(strpos($url,'http://') ===false && strpos($url,'https://') ===false)
        {
            $url = 'http://'.$url;
        }
        $ret = self::postJsonToServer($url . '?cmd=10&srcid=0&desid=' . $uid, json_encode(self::buildData($sendData)));
        $ret = json_decode($ret, true);
        if(!$ret || $ret['code']!=0)
        {
            Logger::write('同步用户货币操作失败');
            return false;
        }
        return $ret;
    }
    const  SERVER_URL='http://127.0.0.1:59179/service.htm';

} 