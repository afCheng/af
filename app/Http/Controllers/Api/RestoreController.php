<?php

namespace App\Http\Controllers\Api;

use App\Model\Item;
use App\Model\Receive;
use App\Model\Restore;
use App\User;
use Dingo\Api\Exception\DeleteResourceFailedException;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\UpdateResourceFailedException;
use function foo\func;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class RestoreController extends BaseController
{
    /**
     * 退还记录编辑
     * @param Request $request
     * @return array
     */
    public function restoreEdit(Request $request)
    {
        if(!empty($request->restore_id)){

            $rules = [
                'restore_id' => ['required'],
                'user_id' => ['required'],
                'item_id' => ['required'],
                'manifest_id' => ['required'],
                'usage' => ['required'],
                'recipient' => ['required'],
                'item_use' => ['required'],
                'is_damage' => ['required'],
                'is_overdue' => ['required'],
                'remarks' => ['required'],
                'site_use' => ['required'],
            ];

            $payload = app('request')->only('restore_id','user_id','item_id','manifest_id','usage','recipient','item_use','is_damage','is_overdue','remarks','site_use');

            $validator = app('validator')->make($payload, $rules);

            if ($validator->fails()) {
                throw new StoreResourceFailedException('失败', $validator->errors());
            }

            $restore_save = Restore::find($request->restore_id);
            $restore_save->user_id = $request->user_id;
            $restore_save->item_id = $request->item_id;
            $restore_save->manifest_id = $request->manifest_id;
            $restore_save->usage = $request->usage;
            $restore_save->recipient = $request->recipient;
            $restore_save->item_use = $request->item_use;
            $restore_save->is_damage = $request->is_damage;
            $restore_save->is_overdue = $request->is_overdue;
            $restore_save->remarks = $request->remarks;
            $restore_save->site_use = $request->site_use;
            $result = $restore_save->save();
            if(!$result){
                throw new StoreResourceFailedException('修改失败', $validator->errors());
            }
            return $this->result('修改成功',null);
        }
        else {
            $rules = [
                'restore' => ['required'],
            ];

            $payload = app('request')->only('restore');

            $validator = app('validator')->make($payload, $rules);

            if ($validator->fails()) {
                throw new StoreResourceFailedException('失败', $validator->errors());
            }

            $restore = $request->restore;
            foreach($restore as $value){

                $manifest_id = Receive::select()->where('user_id',$value['user_id'])->where('item_id',$value['item_id'])->orderBy('created_at','DESC')->get();

                $restore_add = new Restore();
                $restore_add->user_id = $value['user_id'];
                $restore_add->item_id =  $value['item_id'];
                $restore_add->manifest_id =  $manifest_id[0]['manifest_id'];
                $restore_add->usage =  $value['usage'];
                $restore_add->recipient =  $value['recipient'];
                $restore_add->item_use =  $manifest_id[0]['item_use'];
                $restore_add->is_damage =  $value['is_damage'];
                $restore_add->is_overdue =  $value['is_overdue'];
                $restore_add->remarks =  $value['remarks'];
                $restore_add->site_use =  $value['site_use'];
                $result = $restore_add->save();
                if(!$result){
                    continue;
                }

                $item_info = Item::find($value['item_id']);
                if($value['is_damage'] == 0){
                    $item_info->is_putaway = 1;
                }
                $item_info->status = 2;
                $item_info->stock = $value['usage'];
                $item_result = $item_info->save();
                if(!$item_result){
                    continue;
                }

                $receive = Receive::find($manifest_id[0]['id']);
                $receive->is_restore = 1;
                $receive_result = $receive->save();
                if(!$receive_result){
                    continue;
                }

            }
            return $this->result('添加成功',null);
        }
    }

    /**
     * 归还记录列表
     * @param Request $request
     * @return array
     */
    public function restoreList(Request $request)
    {
        $rules = [
            'number' => ['required'],
        ];

        $payload = app('request')->only('number');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('失败', $validator->errors());
        }

        $restore_list = Restore::select('restores.*','users.name as user_name','items.item_name','items.type','frids.frid_number','sorts.name as sort_name')
            ->join('users','users.id','restores.user_id')
            ->join('items','items.id','restores.item_id')
            ->join('frids','frids.id','items.frid_id')
            ->join('sorts','sorts.id','items.sort_id');

        if(isset($request->item_name) && $request->item_name === '0'){
            $restore_list = $restore_list->where('items.item_name','like','%0%');
        }

        if(!empty($request->item_name)){
            $restore_list = $restore_list->where('items.item_name','like','%'.$request->item_name.'%');
        }

        if(!empty($request->frid_id)){
            $restore_list = $restore_list->where('items.frid_id',$request->frid_id);
        }

        if(!empty($request->type)){
            $restore_list = $restore_list->where('items.type',$request->type);
        }

        if(!empty($request->sort_id)){
            $restore_list = $restore_list->where('items.sort_id',$request->sort_id);
        }

        if(!empty($request->t_start)){
            $restore_list = $restore_list->where('restores.created_at','>=',$request->t_start);
        }

        if(!empty($request->t_end)){
            $restore_list = $restore_list->where('restores.created_at','<=',$request->t_end);
        }

        if(!empty($request->t_start) && !empty($request->t_end)){
            $restore_list = $restore_list->whereBetween('restores.created_at',[$request->t_start,$request->t_end]);
        }

        $restore_list = $restore_list->orderBy('restores.created_at','DESC')->DISTINCT()->paginate($request->number);

        if(count($restore_list) > 0){
            return $this->result('成功',$restore_list);
        }
        elseif (count($restore_list) == 0){
            return $this->result('无数据',null);
        }
        else {
            throw new ResourceException('查询失败', $validator->errors());
        }
    }

    /**
     * 查询归还记录(查询)
     * @param Request $request
     * @return array
     */
    public function restoreSelect(Request $request)
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

        $restore_select = Restore::select()->with(['item' => function($query) use ($request) {
            $query->where('frid_id',$request->frid_id)
                ->orWhere('created_at',[$request->start,$request->end])
                ->orWhere('sort_id',$request->sort_id);
        }])->paginate($request->number);

        foreach ($restore_select as $value){
            $users = $value->user;
        }

        if(count($restore_select) > 0){
            return $this->result('成功',$restore_select);
        }
        elseif (count($restore_select) == 0){
            return $this->result('无数据',null);
        }
        else {
            throw new ResourceException('查询失败', $validator->errors());
        }
    }

    /**
     * 归还记录删除
     * @param Request $request
     * @return array
     */
    public function restoreDel(Request $request)
    {
        $rules = [
            'restore' => ['required'],
        ];

        $payload = app('request')->only('restore');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new DeleteResourceFailedException('失败', $validator->errors());
        }

        $restore = $request->restore;
        foreach($restore as $value){
            $restore_del = Restore::find($value['restore_id']);
            $result = $restore_del->save();
            if(!$result){
                continue;
            }
        }
        return $this->result('删除成功',null);
    }

    /**
     * 未归还记录
     * @param Request $request
     * @return array
     */
    public function userReceive(Request $request)
    {
        $rules = [
            'id_number' => ['required'],
            'number' => ['required'],
            'page' => ['required'],
        ];

        $payload = app('request')->only('id_number','number','page');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('失败', $validator->errors());
        }

        $user_select = User::select('items.id as item_id','frids.frid_number','items.item_name','sorts.name as sort_name','items.type','receives.item_use','receives.receive_at','receives.overdue_at',DB::raw('datediff(CURRENT_DATE(),receives.overdue_at) as status_at'))
            ->join('receives','receives.user_id','users.id')
            ->join('items','items.id','receives.item_id')
            ->join('frids','frids.id','items.frid_id')
            ->join('sorts','sorts.id','items.sort_id')
            ->where('receives.is_restore',0)
            ->where('users.id_number',$request->id_number);
            if(!empty($request->item_name)){
                $user_select = $user_select->where('items.item_name','like','%'.$request->item_name.'%');
            }
        $user_select = $user_select->DISTINCT()->paginate($request->number);

        if(count($user_select) > 0){
            return $this->result('成功',$user_select);
        }
        elseif(count($user_select) == 0){
            $data['data'] = [];
            return $this->result('无数据',$data);
        }
        else{
            throw new ResourceException('查询失败', $validator->errors());
        }
    }

}
