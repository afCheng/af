<?php

namespace App\Http\Controllers\Api;

use App\Model\Apply;
use App\Model\Item;
use App\Model\Manifest;
use App\Model\Receive;
use App\Model\Restore;
use App\User;
use Dingo\Api\Exception\DeleteResourceFailedException;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class UserController extends BaseController
{
    /**
     * 申领人编辑
     * @param Request $request
     * @return array
     */
    public function userEdit(Request $request)
    {
        $rules = [
            'name' => ['required'],
            'sex' => ['required'],
            'id_number' => ['required'],
            'position' => ['required'],
            'call' => ['required'],
        ];

        $payload = app('request')->only('name','sex','id_number','position','call');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new StoreResourceFailedException('失败', $validator->errors());
        }

        if(!empty($request->user_id)){
            $user_save = new User();
            $user_save->name = $request->name;
            $user_save->sex = $request->sex;
            $user_save->id_number = $request->id_number;
            $user_save->position = $request->position;
            $user_save->call = $request->call;
            $result = $user_save->save();
            if(!$result){
                throw new StoreResourceFailedException('修改失败', $validator->errors());
            }
            return $this->result('修改成功',null);
        }
        else {
            $user_add = new User();
            $user_add->name = $request->name;
            $user_add->sex = $request->sex;
            $user_add->id_number = $request->id_number;
            $user_add->position = $request->position;
            $user_add->call = $request->call;
            $result = $user_add->save();
            if(!$result){
                throw new StoreResourceFailedException('添加失败', $validator->errors());
            }
            return $this->result('添加成功',null);
        }

    }

    /**
     * 申领人列表
     * @param Request $request
     */
    public function userList(Request $request)
    {
        $rules = [

        ];

        $payload = app('request')->only('');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('失败', $validator->errors());
        }

        $user_list = User::select();

        if(!empty($request->id_number)){
            $user_list = $user_list->where('id_number',$request->id_number);
        }
        if(!empty($request->name)){
            $user_list = $user_list->where('id_number',$request->name);
        }

        $user_list = $user_list->paginate($request->number);

        if(count($user_list) > 0){
            return $this->result('成功',$user_list);
        }
        elseif (count($user_list) == 0) {
            return $this->result('无数据',null);
        }
        else {
            throw new ResourceException('查询失败', $validator->errors());
        }
    }

    /**
     * 申领人删除
     * @param Request $request
     */
    public function userDel(Request $request)
    {
        $rules = [
            'user_id' => ['required'],
        ];

        $payload = app('request')->only('user_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new DeleteResourceFailedException('失败', $validator->errors());
        }

        $item_receive = Receive::select()->where('item_id',$request->user_id)->get();
        if(count($item_receive) > 0){
            throw new DeleteResourceFailedException('该身份有相对应的领用记录关联', $validator->errors());
        }

        $item_restore = Restore::select()->where('item_id',$request->user_id)->get();
        if(count($item_restore) > 0){
            throw new DeleteResourceFailedException('该身份有相对应的归还记录关联', $validator->errors());
        }

        $item_apply = Apply::select()->where('item_id',$request->user_id)->get();
        if(count($item_apply) > 0){
            throw new DeleteResourceFailedException('该身份有相对应的申请记录关联', $validator->errors());
        }

        $user_del = User::find($request->user_id);
        $result = $user_del->delete();
        if(!$result){
            throw new DeleteResourceFailedException('删除失败', $validator->errors());
        }

        return $this->result('成功',null);
    }

    /**
     * 未申领(清单)
     * @param Request $request
     * @return array
     */
    public function userManifest(Request $request)
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

        $user_select = User::select('manifests.id','users.name as user_name','manifests.created_at','manifests.describe','manifests.item_use','manifests.approver','manifests.remarks','manifests.is_receive')
            ->join('manifests','manifests.user_id','users.id')
            ->where('users.id_number',$request->id_number)
            ->where('manifests.is_receive',1)
            ->orderBy('manifests.created_at','desc')
            ->DISTINCT()
            ->paginate($request->number);

        foreach ($user_select as $value){
            $item = Manifest::select('applys.item_name','applys.stock','applys.unit')
                ->join('applys','applys.manifest_id','manifests.id')
                ->where('applys.manifest_id',$value['id'])
                ->DISTINCT()
                ->paginate(3);

            foreach ($item as $v){
                $value['list'] .= $v['item_name'].'、';
            }
        }

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

    /**
     * 未申领(大类)
     * @param Request $request
     * @return array
     */
    public function userApply(Request $request)
    {
        $rules = [
            'manifest_id' => ['required'],
            'number' => ['required'],
            'page' => ['required'],
        ];

        $payload = app('request')->only('manifest_id','number','page');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('失败', $validator->errors());
        }

        $user_select = Manifest::select('applys.item_name','applys.stock','applys.unit')
            ->join('applys','applys.manifest_id','manifests.id')
            ->where('applys.manifest_id',$request->manifest_id)
            ->DISTINCT()
            ->paginate($request->number);

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

    /**
     * 未申领(小类)
     * @param Request $request
     * @return array
     */
    public function userItem(Request $request)
    {
        $rules = [
            'item_name' => ['required'],
            'number' => ['required'],
            'page' => ['required'],
        ];

        $payload = app('request')->only('item_name','number','page');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('失败', $validator->errors());
        }

        $user_select = DB::table('items')
                            ->selectRaw('frids.frid_number,items.photo,items.id as item_id,items.item_name,items.introduce,items.status,items.dangerous,items.stock,items.unit,type,s_content,AsText(situation) as situation,sorts.name as sort_name')
                            ->join('frids','frids.id','items.frid_id')
                            ->join('sorts','sorts.id','items.sort_id')
                            ->where('items.item_name','like','%'.$request->item_name.'%')
                            ->where('items.is_putaway',0)
                            ->DISTINCT()
                            ->paginate($request->number);

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

    /**
     * 未归还
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

//        $number = [];
//
//        //先查出归还记录表里的物品编号
//        $item_number = User::select('items.item_number')
//            ->join('receives','receives.user_id','users.id')
//            ->join('items','items.id','receives.item_id')
//            ->where('users.id_number',$request->id_number)
//            ->get();
//
//        //如果只有一条
//        if(count($item_number) == 1){
//            $restore = Restore::select('items.item_number')
//                ->join('users','users.id','restores.user_id')
//                ->join('items','items.id','restores.item_id')
//                ->where('items.item_number',$item_number[0]['item_number'])
//                ->get();
//        }
//        //如果有多条
//        elseif(count($item_number) > 1){
//            foreach($item_number as $value){
//                $number[] = $value['item_number'];
//            }
//            $restore = Restore::select('items.item_number')
//                ->join('users','users.id','restores.user_id')
//                ->join('items','items.id','restores.item_id')
//                ->whereIn('items.item_number',$number)
//                ->get();
//        }
//        else{
//            throw new ResourceException('无未归还物品', $validator->errors());
//        }
//        $data = [];

        $user_select = User::select('items.id as item_id','frids.frid_number','items.item_name','sorts.name as sort_name','items.dangerous','items.type','receives.receive_at','receives.overdue_at',DB::raw('datediff(CURRENT_DATE(),receives.overdue_at) as status_at'))
            ->join('receives','receives.user_id','users.id')
            ->join('items','items.id','receives.item_id')
            ->join('frids','frids.id','items.frid_id')
            ->join('sorts','sorts.id','items.sort_id')
            ->where('receives.is_restore',0)
            ->where('users.id_number',$request->id_number)
            ->DISTINCT()
            ->paginate($request->number);
//            if(count($restore) == 1){
//                $user_select->where('items.item_number','<>',$restore[0]['item_number']);
//            }
//            elseif (count($restore) > 1){
//                foreach ($restore as $value){
//                    $data[] = $value['item_number'];
//                }
//              $user_select->whereNotIn('items.item_number',$data);
//            }

//        $user_select = $user_select->DISTINCT()->get();

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

    /**
     * 申领历史
     * @param Request $request
     * @return array
     */
    public function userRestore(Request $request)
    {
        $rules = [
            'manifest_id' => ['required'],
            'number' => ['required'],
            'page' => ['required'],
        ];

        $payload = app('request')->only('manifest_id','number','page');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('失败', $validator->errors());
        }

        $item_select = Item::select('items.id as item_id','items.item_name','type','status','frids.frid_number','items.dangerous','sorts.name as sort_name','receives.number','receives.site_use','receives.item_use','receives.remarks','receives.receive_at as r_receive_at','receives.overdue_at as r_overdue_at')
            ->join('frids','frids.id','items.frid_id')
            ->join('sorts','sorts.id','items.sort_id')
            ->join('receives','receives.item_id','items.id')
            ->join('manifests','manifests.id','receives.manifest_id')
            ->where('manifests.id',$request->manifest_id)
            ->where('receives.receive_at','<',time('Y-d-m H:i:s'))
            ->orderBy('receives.created_at','DESC')
            ->DISTINCT()
            ->paginate($request->number);

        if(count($item_select) > 0){
            return $this->result('成功',$item_select);
        }
        elseif(count($item_select) == 0){
            $data['data'] = [];
            return $this->result('无数据',$data);
        }
        else{
            throw new ResourceException('查询失败', $validator->errors());
        }
    }

    /**
     * 申领历史(清单)
     * @param Request $request
     * @return array
     */
    public function manifestRestore(Request $request)
    {
        $rules = [
            'number' => ['required'],
            'page' => ['required'],
            'id_number' => ['required'],
        ];

        $payload = app('request')->only('id_number','number','page');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('失败', $validator->errors());
        }

        $user_select = User::select('manifests.id','users.name as user_name','manifests.describe','manifests.created_at','manifests.approver','manifests.remarks','receives.recipient')
            ->join('manifests','manifests.user_id','users.id')
            ->join('receives','receives.user_id','users.id')
            ->where('manifests.is_receive',0)
            ->where('users.id_number',$request->id_number)
            ->orderBy('receives.created_at','DESC')
            ->DISTINCT()
            ->paginate($request->number);

        foreach ($user_select as $value){
            $item = Manifest::select('applys.item_name','applys.stock','applys.unit')
                ->join('applys','applys.manifest_id','manifests.id')
                ->where('applys.manifest_id',$value['id'])
                ->DISTINCT()
                ->paginate(2);

            foreach ($item as $v){
                $value['list'] .= $v['item_name'].'、';
            }
        }

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
