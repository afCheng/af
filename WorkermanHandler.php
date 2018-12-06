<?php

namespace App\Http\Controllers\handlers;

use App\Model\BoundShop;
use App\Model\Busines;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Lib\Timer;
use Orzcc\TopClient\Facades\TopClient;
use App\Http\Controllers\Api\BusinesController;
// 心跳间隔10秒
define('HEARTBEAT_TIME', 10);

class WorkermanHandler extends Controller
{
    // 处理客户端连接
    public function onConnect($connection)
    {
        echo "new connection from ip " . $connection->getRemoteIp() . "\n";
    }

    // 处理客户端消息
    public function onMessage($connection,$data)
    {
        $connection->send('Hello, your send message is: ' . $data);
    }

    // 处理客户端断开
    public function onClose($connection)
    {
        echo "connection closed from ip {$connection->getRemoteIp()}\n";
    }

    public function onWorkerStart($worker)
    {
        Timer::add(3, function () use ($worker) {

            $curl = curl_init(); // 启动一个CURL会话
            curl_setopt($curl, CURLOPT_URL, 'http://www.ifint.net/api/server'); // 要访问的地址
            curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
            curl_setopt($curl, CURLOPT_POSTFIELDS, ''); // Post提交的数据包
            curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
            curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
            $info = curl_exec($curl); // 执行操作
            if (curl_errno($curl)) {
                echo 'Errno'.curl_error($curl);//捕抓异常
            }
            curl_close($curl); // 关闭CURL会话
            if(!empty($info)){
                $con = new AsyncTcpConnection('ws://127.0.0.1:13528');
                $con->onConnect = function ($con) {

                    $curl = curl_init(); // 启动一个CURL会话
                    curl_setopt($curl, CURLOPT_URL, 'http://www.ifint.net/api/server'); // 要访问的地址
                    curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
                    curl_setopt($curl, CURLOPT_POSTFIELDS, ''); // Post提交的数据包
                    curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
                    curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
                    $info = curl_exec($curl); // 执行操作
                    if (curl_errno($curl)) {
                        echo 'Errno'.curl_error($curl);//捕抓异常
                    }
                    curl_close($curl); // 关闭CURL会话
                    $info = json_decode($info,true);
                    if(!empty($info)){
                        foreach($info as $key=>$value){
                            $key = $key + 1 ;
                            $topclient = TopClient::connection();
                            $req = new \CainiaoWaybillIiGetRequest;
                            $param_waybill_cloud_print_apply_new_request = new \WaybillCloudPrintApplyNewRequest;
                            $param_waybill_cloud_print_apply_new_request->cp_code="YTO";
                            $sender = new \UserInfoDto;
                            $address = new \AddressDto;
                            //分割字符串
//                            $shipping = explode(',',$value['shipping_address']);
//                            $province = substr($shipping[2],0,2);
                            $address->city="金华市";
                            $address->detail="城西街道何畔山国际物流园圆通速递总部1号楼5楼东面";
                            $address->district="义乌市";
                            $address->province="浙江省";
                            $address->town="";
                            $sender->address = $address;
                            $sender->mobile="057985832855";
                            $sender->name="(".$key.")"."李传芳";
                            $sender->phone="057985832855";
                            $param_waybill_cloud_print_apply_new_request->sender = $sender;
                            $trade_order_info_dtos = new \TradeOrderInfoDto;
                            $trade_order_info_dtos->logistics_services=" ";
                            $trade_order_info_dtos->object_id="1";
                            $order_info = new \OrderInfoDto;
                            $order_info->order_channels_type="TB";
                            $order_info->trade_order_list=rand(1000000,9999999);
                            $trade_order_info_dtos->order_info = $order_info;
                            $package_info = new \PackageInfoDto;
                            $package_info->id="";
                            $items = new \Item;
                            $items->count="1";
                            $items->name="衣服";
                            $package_info->items = $items;
                            $package_info->volume="1";
                            $package_info->weight=$value['weight'];
                            $trade_order_info_dtos->package_info = $package_info;
                            $recipient = new \UserInfoDto;
                            $address = new \AddressDto;
                            //分割字符串
                            $receive = explode(',',$value['receive_address']);
                            $province2 = substr($receive[2],0,3);
                            $address->city="";
                            $address->detail=$receive[2];
                            $address->district="";
                            $address->province=$province2;
                            $address->town="";
                            $recipient->address = $address;
                            $recipient->mobile=$receive[1];
                            $recipient->name=$receive[0];
                            $recipient->phone=$receive[1];
                            $trade_order_info_dtos->recipient = $recipient;
                            $trade_order_info_dtos->template_url="http://cloudprint.cainiao.com/cloudprint/template/getStandardTemplate.json?template_id=101";
                            $trade_order_info_dtos->user_id="12";
                            $param_waybill_cloud_print_apply_new_request->trade_order_info_dtos = $trade_order_info_dtos;
                            $param_waybill_cloud_print_apply_new_request->store_code="579008";
                            $param_waybill_cloud_print_apply_new_request->resource_code="DISTRIBUTOR_978324";
                            $param_waybill_cloud_print_apply_new_request->dms_sorting="false";
                            $param_waybill_cloud_print_apply_new_request->three_pl_timing="false";
                            $req = json_encode($param_waybill_cloud_print_apply_new_request);

                            $data = "req=".$req;
                            $curl = curl_init(); // 启动一个CURL会话
                            curl_setopt($curl, CURLOPT_URL, 'http://tao.bdeust.cn/taobao/test.php'); // 要访问的地址
                            curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
                            curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
                            curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
                            curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
                            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
                            $tmpInfo = curl_exec($curl); // 执行操作
                            if (curl_errno($curl)) {
                                echo 'Errno'.curl_error($curl);//捕抓异常
                            }
                            curl_close($curl); // 关闭CURL会话

                            $b = json_decode($tmpInfo,1);

                            $array = array();
                            $array['cmd'] = "print";
                            $array['requestID'] = uniqid(time().mt_rand(1000,9999),12);
                            $array['version'] = "1.0";
                            $array['task']['taskID'] = uniqid(time().mt_rand(1000,9999),12);
                            $array['task']['preview'] = false;
                            $array['task']['printer'] = "";
                            $array['task']['previewType'] = "pdf";
                            $array['task']['documents'][0]['documentID'] = $b['modules']['waybill_cloud_print_response'][0]['waybill_code'];
                            $array['task']['documents'][0]['contents'][] =  $b['modules']['waybill_cloud_print_response'][0]['print_data'];
                            unset($array['task']['documents']['contents'][0]['data']['cpCode']);
                            unset($array['task']['documents']['contents'][0]['data']['needEncrypt']);
                            unset($array['task']['documents']['contents'][0]['data']['parent']);
                            $te = json_encode($array,JSON_UNESCAPED_UNICODE);
                            $con->send($te);

                            $odd_number = $array['task']['documents'][0]['documentID'];
                            $id = 'id='.$value['id'].'&odd_number='.$odd_number;

                            $curl = curl_init(); // 启动一个CURL会话
                            curl_setopt($curl, CURLOPT_URL, 'http://www.ifint.net/api/server2'); // 要访问的地址
                            curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
                            curl_setopt($curl, CURLOPT_POSTFIELDS, $id); // Post提交的数据包
                            curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
                            curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
                            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
                            curl_exec($curl); // 执行操作
                            if (curl_errno($curl)) {
                                echo 'Errno'.curl_error($curl);//捕抓异常
                            }
                            curl_close($curl); // 关闭CURL会话
                        }

                    }
                    else {
                        echo '无订单打印!';
                    }
                };
                $con->onMessage = function ($con, $data) {
                    echo $data;
                };
                $con->connect();
            } else {
                echo '无订单打印!!';
            };
            $time_now = time();
            foreach ($worker->connections as $connection) {
                // 有可能该connection还没收到过消息，则lastMessageTime设置为当前时间
                if (empty($connection->lastMessageTime)) {
                    $connection->lastMessageTime = $time_now;
                    continue;
                }
                // 上次通讯时间间隔大于心跳间隔，则认为客户端已经下线，关闭连接
                if ($time_now - $connection->lastMessageTime > HEARTBEAT_TIME) {
                    echo "Client ip {$connection->getRemoteIp()} timeout!!!\n";
                    $connection->close();
                }
            }
        });
    }
}
