<?php
/**
 * Created by PhpStorm.
 * User: nihao
 * Date: 17-9-9
 * Time: 下午2:55
 */

namespace Workerman\Lib;


class Code
{
    const CODEERRPARAM = -109; //参数错误
    const CODEERRAUTH = -110;
    const CODEEIPLIMIT = -112;
    const CODEINVALIDTOKEN = -113;
    const CODERETFAILED = -114;
    const CODETHIRDREGFAILED = -115;
    const CODETHIRDTIMEOUT = -116;
    const CODETHIRDINVALIDSIGN = -117;
    const CODEQUICKLOGINFAILED = -119;
    const CODEUSERNOEXISTS = -120;//用户不存在
    const CODEUSERAUTHFAILED = -121;//在线效验失败
    const CODEIPINVALID = -122;//IP黑名单
    const CODEUSERSEAL = -123;//用户被封
    const CODEPLATFORMCFGMISSING = -124;//配置缺失
    const CODEUNIDNOTEXITS = -125;//平台错误
    const CODEMETHODNOTEXITS = -126;//方法不存在
    const CODENOTCOMPANYIP = -127;//非公司IP
    const CODENOTSIGNFAILED = -128;//签名验证失败
    const CODENOCONFIG = -129;//配置缺失
    const CODEINVALIDEMAIL = -130;//邮箱格式错误
    const CODEUSETOOLFAILED = -131;//使用道具失败
    const CODENOMONEY = -132;//-132：金币不足
    const CODECANTUSEABLE = -133; //道具不可用
    const CODENOTOOL = -134;//道具不足
    const CODEREQUESTTOOMANY = -135;//请求次数过多
    const SUCCESS = 1;//正常

    const DATAEXCEPTION=-236;//数据异常
    const OPERATEEXCEPTION=-238;//操作出现未知异常
    const NOT_ALLOWED_OPERATION=-239;//条件不住不允许操作
    const  FORMAT_EXCEPTION=-240;//数据格式解析错误


    const CODEGOODNOEXISTS = -141;//商品不存在
    const CODEGOODISNOSELL = -142;//购买的是非卖品
    const CODEGOODISNOEXCHAGE = -143;//不能兑换
    const CODEGOODERRTYPE = -144;//商品购买类型错误
    const CODEGOODERRPRICE = -145;//商品价格错误
    const CODEGOODERRCATE = -146;//商品分类错误
    const CODETOOLUSEOVERFLOW = -147;//一次使用道具过多
    const CODENOTVIP = -148;//vip等级不符

}