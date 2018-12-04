<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Qiniu\Pili\Client;
use function Qiniu\Pili\HDLPlayURL;
use function Qiniu\Pili\HLSPlayURL;
use Qiniu\Pili\Mac;
use function Qiniu\Pili\RTMPPlayURL;
use function Qiniu\Pili\RTMPPublishURL;

class QiuniuLiveController extends BaseController
{
    public $push_domin = 'pili-publish.jiaxingyanlian.com';    //推流域名
    public $pull_domin = 'pili-live-rtmp.jiaxingyanlian.com';    //拉流域名
    public $hubName = 'emergency-drill';       //直播空间名
    public $ak = 'ggshr9YfpC-qJEOhnwKYUXcRdyYVDM3-ypDXyhs8';             //AccessKey
    public $sk = 'VpK7kIYr8npt8wzNfFsjKYcLRdxyKsJrh5E0OdPL';             //SecretKey
    private $mac ;
    private $client;
    private $hub;

    public function __construct()
    {
        $this->mac = new Mac($this->ak, $this->sk);
        $this->client = new Client($this->mac);
        $this->hub = $this->client->hub($this->hubName);
        $this->push_domin = $this->push_domin;
        $this->pull_domin = $this->pull_domin;
    }

    /**
     * 获取推流地址
     * @param $streamKey 流名
     * @return mixed
     */
    public function publishUrl(Request $request){
        return RTMPPublishURL($this->push_domin, $this->hubName, $request->streamKey, 3600, $this->ak, $this->sk);
    }

    /**
     * 获取拉流地址（RTMP直播地址）
     * @param $streamKey
     * @return mixed
     */
    public function playUrl(Request $request){
        return RTMPPlayURL($this->pull_domin, $this->hubName, $request->streamKey);
    }

    /**
     * 获取拉流地址（HlS直播地址）
     * @param $streamKey
     * @return mixed
     */
    public function playHlS(Request $request)
    {
        return HLSPlayURL($this->pull_domin, $this->hubName, $request->streamKey);
    }


    /**
     * 获取拉流地址（HDL直播地址）
     * @param $streamKey
     * @return mixed
     */
    public function playHDL(Request $request)
    {
        return HDLPlayURL($this->pull_domin, $this->hubName, $request->streamKey);
    }

    /**
     * 获取流信息
     * @param $streamKey 流名
     * @return array
     */
    public function info(Request $request){
        $stream = $this->hub->stream($request->streamKey);
        return $stream->info();
    }

    /**
     * 禁用推流和拉流
     * @param $streamKey 流名
     * @param $time 恢复推流时间时间戳，不填则永久封禁
     * @return mixed
     */
    public function disable(Request $request){
        $stream = $this->hub->stream($request->streamKey);
        return $stream->disable();
    }

    /**
     * 启用流
     * @param $streamKey 流名
     * @return mixed
     */
    public function enable(Request $request){
        $stream = $this->hub->stream($request->streamKey);
        return $stream->enable();
    }

    /**
     * 查询直播空间中的流列表。
     * @param $prefix  字符串，可选，限定只返回带以 prefix 为前缀的流名，不指定表示不限定前缀。
     * @param $limit  整数，可选，限定返回的流个数，不指定表示遵从系统限定的最大个数。
     * @param $marker  字符串，可选，上一次查询返回的标记，用于提示服务端从上一次查到的位置继续查询，不指定表示从头查询。
     */
    public function listStreams(Request $request){
        return $this->hub->listStreams($request->prefix, $request->limit,'');
    }

    /**
     * 查询直播空间中的流列表。
     * @param $prefix  字符串，可选，限定只返回带以 prefix 为前缀的流名，不指定表示不限定前缀。
     * @param $limit   整数，可选，限定返回的流个数，不指定表示遵从系统限定的最大个数。
     * @param $marker  字符串，可选，上一次查询返回的标记，用于提示服务端从上一次查到的位置继续查询，不指定表示从头查询。
     */
    public function listLiveStreams(Request $request){
        return $this->hub->listLiveStreams($request->prefix, $request->limit,'');
    }

    /**
     * 查询直播状态
     * @param $streamKey 流名
     * @return mixed
     */
    public function liveStatus(Request $request){
        $stream = $this->hub->stream($request->streamKey);
        return $stream->liveStatus();
    }

    /**
     * 创建流
     */
    public function createStream()
    {
        $ak = Config('QiNiuLive.accessKey');
        $sk = Config('QiNiuLive.secretKey');
        $hubName = Config('QiNiuLive.hubName');
        $mac = new Mac($ak,$sk);
        $client = new Client($mac);
        $hub = $client->hub($hubName);

        try{
            $streamKey="php-sdk-test".time();
            $resp=$hub->create($streamKey);
            print_r($resp);
        }catch(\Exception $e) {
            echo "Error:",$e;
        }
    }

}
