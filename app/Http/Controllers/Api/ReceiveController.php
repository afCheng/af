<?php

namespace App\Http\Controllers\Api;

use App\Model\Apply;
use App\Model\Frid;
use App\Model\Item;
use App\Model\Manifest;
use App\Model\Receive;
use Dingo\Api\Exception\DeleteResourceFailedException;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\UpdateResourceFailedException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ReceiveController extends BaseController
{
    /**
     * 扫描标签
     * @param Request $request
     * @return array
     */
    public function fridList(Request $request)
    {
        $rules = [
//            'number' => ['required'],
        ];

        $payload = app('request')->only('');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('失败', $validator->errors());
        }

        $frid = [];

        $frid_list = $request->frid_list;

        foreach($frid_list as $value){
            $frid_item = Frid::select('frids.frid_number','items.item_name','items.id as item_id','items.photo','sorts.name as sort_name','items.sort_id','items.stock as amount','items.status')
                ->join('items','items.frid_id','frids.id')
                ->join('sorts','sorts.id','items.sort_id')
                ->where('frids.frid_number',$value)
                ->distinct()
                ->get();

            array_push($frid,$frid_item);
        }

        if(count($frid_item) > 0){
            return $this->result('成功',$frid);
        }
        elseif (count($frid_item) == 0){
            return $this->result('无数据',null);
        }
        else {
            throw new ResourceException('查询失败', $validator->errors());
        }

    }

    /**
     * 领用记录编辑
     * @param Request $request
     * @return array
     */
    public function receiveEdit(Request $request)
    {
        if(!empty($request->receive_id)){
            $rules = [
                'receive_id' => ['required'],
                'user_id' => ['required'],
                'item_id' => ['required'],
                'manifest_id' => ['required'],
                'number' => ['required'],
                'site_use' => ['required'],
                'item_use' => ['required'],
                'approver' => ['required'],
                'recipient' => ['required'],
                'receive_at' => ['required'],
                'overdue_at' => ['required'],
            ];

            $payload = app('request')->only('receive_id','user_id','item_id','manifest_id','number','site_use','item_use','approver','recipient','receive_at','overdue_at');

            $validator = app('validator')->make($payload, $rules);

            if ($validator->fails()) {
                throw new StoreResourceFailedException('失败', $validator->errors());
            }

            $receive_save = Receive::find($request->receive_id);
            $receive_save->user_id = $request->user_id;
            $receive_save->item_id = $request->item_id;
            $receive_save->manifest_id = $request->manifest_id;
            $receive_save->number = $request->number;
            $receive_save->site_use = $request->site_use;
            $receive_save->item_use = $request->item_use;
            $receive_save->approver = $request->approver;
            $receive_save->recipient = $request->recipient;
            $receive_save->remarks = $request->remarks;
            $receive_save->receive_at = $request->receive_at;
            $receive_save->overdue_at = $request->overdue_at;
            $result = $receive_save->save();
            if(!$result){
                throw new StoreResourceFailedException('修改失败', $validator->errors());
            }

            return $this->result('修改成功',null);
        }
        else {
            $rules = [
                'receive' => ['required'],
            ];

            $payload = app('request')->only('receive');

            $validator = app('validator')->make($payload, $rules);

            if ($validator->fails()) {
                throw new StoreResourceFailedException('失败', $validator->errors());
            }
            $receive = $request->receive;
            foreach($receive as $value){

                $number = Item::select('stock')->where('id',$value['item_id'])->first();

                $receive_add = new Receive();
                $receive_add->user_id = $value['user_id'];
                $receive_add->item_id = $value['item_id'];
                $receive_add->manifest_id = $value['manifest_id'];
                $receive_add->number = $number['stock'];
                $receive_add->site_use = $value['site_use'];
                $receive_add->item_use = $value['item_use'];
                $receive_add->approver = $value['approver'];
                $receive_add->recipient = $value['recipient'];
                $receive_add->remarks = $value['remarks'];
                $receive_add->is_restore = 0;
                $receive_add->receive_at = $value['receive_at'];
                $receive_add->overdue_at = $value['overdue_at'];
                $result = $receive_add->save();
                if(!$result){
                    throw new StoreResourceFailedException('添加失败', $validator->errors());
                }

                $item_info = DB::table('items')->where('id',$value['item_id'])->update(['status'=>1]);

            }

            $apply_info = DB::table('manifests')->where('id',$request->manifest_id)->update(['is_receive'=>0]);

            return $this->result('添加成功',null);
        }

    }

    /**
     * 领用记录列表
     * @param Request $request
     * @return array
     */
    public function receiveList(Request $request)
    {
        $rules = [
            'number' => ['required'],
        ];

        $payload = app('request')->only('number');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('失败', $validator->errors());
        }

        $receive_list = Receive::select('receives.*','users.name as user_name','items.item_name','items.type','frids.frid_number','sorts.name as sort_name')
            ->join('users','users.id','receives.user_id')
            ->join('items','items.id','receives.item_id')
            ->join('frids','frids.id','items.frid_id')
            ->join('sorts','sorts.id','items.sort_id');

            if(isset($request->item_name) && $request->item_name === '0'){
                $receive_list = $receive_list->where('items.item_name','like','%0%');
            }

            if(!empty($request->item_name)){
                $receive_list = $receive_list->where('items.item_name','like','%'.$request->item_name.'%');
            }

            if(!empty($request->frid_id)){
                $receive_list = $receive_list->where('items.frid_id',$request->frid_id);
            }

            if(!empty($request->type)){
                $receive_list = $receive_list->where('items.type',$request->type);
            }

            if(!empty($request->sort_id)){
                $receive_list = $receive_list->where('items.sort_id',$request->sort_id);
            }

            if(!empty($request->t_start)){
                $receive_list = $receive_list->where('receives.receive_at','>=',$request->t_start);
            }

            if(!empty($request->t_end)){
                $receive_list = $receive_list->where('receives.receive_at','<=',$request->t_end);
            }

            if(!empty($request->t_start) && !empty($request->t_end)){
                $receive_list = $receive_list->whereBetween('receives.receive_at',[$request->t_start,$request->t_end]);
            }

        $receive_list = $receive_list->orderBy('receives.created_at','DESC')->DISTINCT()->paginate($request->number);

        if(count($receive_list) > 0){
            return $this->result('成功',$receive_list);
        }
        elseif (count($receive_list) == 0){
            return $this->result('无数据',null);
        }
        else {
            throw new ResourceException('查询失败', $validator->errors());
        }
    }

    /**
     * 按物品（申请人）查询领用记录
     * @param Request $request
     * @return array
     */
    public function receiveSelect(Request $request)
    {
        $rules = [
            'frid_id' => ['required'],
            'number' => ['required'],
        ];

        $payload = app('request')->only('frid_id','number');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('失败', $validator->errors());
        }

        $receive_select = Receive::select()->with(['item' => function($query) use ($request) {
            $query->where('frid_id',$request->frid_id)
                ->orWhere('created_at',[$request->start,$request->end])
                ->orWhere('sort_id',$request->sort_id);
        }])->paginate($request->number);

        foreach ($receive_select as $value){
            $users = $value->user;
        }

        if(count($receive_select) > 0){
            return $this->result('成功',$receive_select);
        }
        elseif (count($receive_select) == 0){
            return $this->result('无数据',null);
        }
        else {
            throw new ResourceException('查询失败', $validator->errors());
        }

    }

    /**
     * 领用记录删除
     * @param Request $request
     * @return array
     */
    public function receiveDel(Request $request)
    {
        $rules = [
            'receive' => ['required'],
        ];

        $payload = app('request')->only('receive');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new DeleteResourceFailedException('失败', $validator->errors());
        }

        $receive = $request->receive;
        foreach ($receive as $value){
            $receive_del = Receive::find($value['receive_id']);
            $result = $receive_del->delete();
            if(!$result){
                continue;
//                throw new DeleteResourceFailedException('删除失败', $validator->errors());
            }
        }
        return $this->result('成功',null);
    }

}
