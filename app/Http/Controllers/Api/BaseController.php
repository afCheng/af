<?php

namespace App\Http\Controllers\Api;

use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Facades\JWTAuth;

class BaseController extends Controller
{
    use Helpers;

    const OK = 0;
    const FAILED = -1;
    const ERROR = -2;

    public function result($status, $message, $data,$debug='')
    {
        return ['status'=>$status, 'message'=>$message, 'data'=>$data];
    }

//    private $AppKey;
//    private $AppSecret;
//    private $Nonce;
//    private $CurTime;
//    private $CheckSum;
//    const   RAND = "0123456789abcdef";

    /**
     * 生成验证码
     */
//    private function checkSumBuilder()
//    {
//        $AppKey    = '4e93ebe5a2754905f5561b80a3a5f87e';
//        $AppSecret = '4b5f24b272ba';
//        $this->AppKey    = $AppKey;
//        $this->AppSecret = $AppSecret;
//
//        //此部分生成随机字符串
//        $hex_digits = self::RAND;
//        $this->Nonce;
//        for($i=0;$i<128;$i++){			//随机字符串最大128个字符，也可以小于该数
//            $this->Nonce.= $hex_digits[rand(0,15)];
//        }
//        $this->CurTime = (string)(time());	//当前时间戳，以秒为单位
//
//        $join_string = $this->AppSecret.$this->Nonce.$this->CurTime;
//        $this->CheckSum = sha1($join_string);
//
//    }

    /**
     * post请求
     * @param $url
     * @param array $data
     * @return mixed
     */
//    public function postDataCurl($url,$data=array())
//    {
//        $this->checkSumBuilder();        //发送请求前需先生成checkSum
//        if (!empty($data)) {
//            $json = $data;
//        } else {
//            $json = "";
//        }
//        $timeout = 5000;
//
//        $client = new \GuzzleHttp\Client(['headers' => [
//            'AppKey' => $this->AppKey,
//            'Nonce' => $this->Nonce,
//            'CurTime' => $this->CurTime,
//            'CheckSum' => $this->CheckSum,
//            'Content-Type' => 'application/json;charset=utf-8;',
//        ]]);
//
//        try {
//            $response = $client->request('POST', $url, [
//                'json' => $json,
//            ]);
//        } catch (\Exception $ex) {
//            return $ex->getMessage();
//        }
//
//        return $response->getBody();
//    }

}




