<?php

namespace App\Http\Controllers\Api;

use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BaseController extends Controller
{
    use Helpers;

    /**
     * 格式化接口返回值
     *
     * @param \Exception|object $dataOrException
     * @param string $message
     * @return array
     */
    public static function result($message = '',$dataOrException)
    {

        $type = \gettype($dataOrException);                                                                             // 判断数据类型
        switch($type)
        {
            case 'array':
                return static::formatResult(0, $message, $dataOrException);
            case 'NULL':
                return static::formatResult(0, $message, new \stdClass);                                         // new \stdClass  返回结果是 {}
            case 'integer':
                return static::formatResult($dataOrException, $message, null);
            case 'object':
                break;
            default:
                return static::errorResult();
        }
        $instance = \get_class($dataOrException);                                                                       // 获取数据的class
        switch($instance)
        {
            case 'stdClass':                                                                                            // 基类
            case 'Illuminate\Support\Collection':                                                                       // 用DB返回的数据
            case 'Illuminate\Database\Eloquent\Collection':                                                             // 用模型返回的数据
            case 'Illuminate\Pagination\LengthAwarePaginator':
                return static::formatResult(0, $message, $dataOrException);
        }
        $parent = $instance;                                                                                            //如果以上都没有，就找父类的class（可能有两种，自己定义的Model类和报错）
        while (true) {
            switch ($parent) {
                case 'Dingo\Api\Exception\ResourceException':                                                           //dingoApi报的错
                    return static::formatResult(
                        $dataOrException->getStatusCode(),
                        $dataOrException->getMessage() . '   ' . $dataOrException->getErrors()->first(),
                        null
                    );
                case 'PDOException':                                                                                    //数据库报的错
                    return static::formatResult(
                        601,
                        config('app.debug') ? $dataOrException->getMessage() : '数据库查询错误',
                        null
                    );
                case 'Exception':                                                                                       //服务器报的错和其他没有考虑的到的错
                    return static::returnData($dataOrException,'500');
                case 'Tymon\JWTAuth\Exceptions\TokenExpiredException':                                                  //刷新token失效报的错
                    return static::returnData($dataOrException,'602');
                case 'Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException':                                //token的错（没带token请求接口或token过期）
                    return static::returnData($dataOrException,'603');
                case 'Illuminate\Database\Eloquent\Model':                                                              //自己定义的Model类，都是继承Illuminate\Database\Eloquent\Model的
                    return static::formatResult(0, $message, $dataOrException);
            }
            $parent = get_parent_class($parent);    //查父类的class
            if(!$parent){
                break;
            }
        }
        return static::errorResult();
    }

    private static function formatResult($status, $message, $data)
    {
        return ['status' => $status, 'message' => $message, 'data'=> $data];
    }

    private static function errorResult()
    {
        return static::formatResult(500, 'Server Response Error', null);
    }

    private static function returnData($dataOrException,$code)
    {
        $status = $dataOrException->getCode();          //获取错误的错误码
        $message = $dataOrException->getMessage();      //获取错误的错误信息
        if(!is_int($status) || $status == 0){           //排除报错的错误码刚好是0的可能
            $status = $code;
        }
        if (!is_string($message)) {
            $message = strval($message);                //错误信息转换成字符串
        }
        return static::formatResult($status, $message, null);
    }


}




