<?php

namespace App\Http\Controllers\Api;

use App\Model\Apply;
use App\Model\Manifest;
use Dingo\Api\Exception\DeleteResourceFailedException;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\UpdateResourceFailedException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ApplyController extends BaseController
{
    /**
     * 清单列表
     * @param Request $request
     * @return array
     */
    public function manifestList(Request $request)
    {
        $rules = [
            'user_id' => ['required'],
        ];

        $payload = app('request')->only('user_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('失败', $validator->errors());
        }

        $manifest_list = Manifest::select('manifests.*','users.name as user_name')
            ->join('users','users.id','manifests.user_id')
            ->where('manifests.user_id',$request->user_id);

        if(!empty($request->t_start)){
            $manifest_list = $manifest_list->where('manifests.created_at','>=',$request->t_start);
        }

        if(!empty($request->t_end)){
            $manifest_list = $manifest_list->where('manifests.created_at','<=',$request->t_end);
        }

        if(!empty($request->t_start) && !empty($request->t_end)){
            $manifest_list = $manifest_list->whereBetween('manifests.created_at',[$request->t_start,$request->t_end]);
        }

        if(!empty($request->item_use)){
            $manifest_list = $manifest_list->where('manifests.item_use','like','%'.$request->item_use.'%');
        }

        $manifest_list = $manifest_list->orderBy('manifests.created_at','DESC')->get();

        foreach ($manifest_list as $value){
            $item = Manifest::select('applys.item_name','applys.stock','applys.unit')
                ->join('applys','applys.manifest_id','manifests.id')
                ->where('applys.manifest_id',$value['id'])
                ->DISTINCT()
                ->get();

            foreach ($item as $v){
                $value['list'] .= $v['item_name'].'、';
            }
        }

        if(count($manifest_list) > 0){
            return $this->result('成功',$manifest_list);
        }
        elseif(count($manifest_list) == 0){
            return $this->result('无数据',null);
        }
        else{
            throw new ResourceException('查询失败', $validator->errors());
        }
    }

    /**
     * 清单编辑
     * @param Request $request
     * @return array
     */
    public function manifestEdit(Request $request)
    {
        if(!empty($request->manifest_id)){
            $rules = [
                'manifest_id' => ['required'],
                'user_id' => ['required'],
                'approver' => ['required'],
                'item_use' => ['required'],
                'describe' => ['required'],
            ];

            $payload = app('request')->only('manifest_id','user_id','approver','item_use','describe');

            $validator = app('validator')->make($payload, $rules);

            if ($validator->fails()) {
                throw new StoreResourceFailedException('失败', $validator->errors());
            }

            $apply_save = Manifest::find($request->manifest_id);
            $apply_save->user_id = $request->user_id;
            $apply_save->approver = $request->approver;
            $apply_save->item_use =  $request->item_use;
            $apply_save->remarks =  $request->remarks;
            $apply_save->describe =  $request->describe;
            $apply_save->is_receive = 1;
            $result = $apply_save->save();
            if(!$result){
                throw new UpdateResourceFailedException('修改清单失败', $validator->errors());
            }

            if(!empty($request->apply)){
                $apply = $request->apply;
                foreach($apply as $value){
                    $apply_add = new Apply();
                    $apply_add->manifest_id = $request->manifest_id;
                    $apply_add->item_name = $value['item_name'];
                    $apply_add->stock = $value['stock'];
                    $apply_add->unit = $value['unit'];
                    $result = $apply_add->save();
                    if(!$result){
                        continue;
                    }
                }
            }

            return $this->result('修改成功',null);
        }
        else {
                $rules = [
                    'user_id' => ['required'],
                    'approver' => ['required'],
                    'item_use' => ['required'],
                    'describe' => ['required'],
                    'apply' => ['required'],
                ];

                $payload = app('request')->only('apply','user_id','approver','item_use','describe');

                $validator = app('validator')->make($payload, $rules);

                if ($validator->fails()) {
                    throw new StoreResourceFailedException('失败', $validator->errors());
                }

                $apply_add = new Manifest();
                $apply_add->user_id = $request->user_id;
                $apply_add->approver = $request->approver;
                $apply_add->item_use = $request->item_use;
                $apply_add->remarks = $request->remarks;
                $apply_add->describe = $request->describe;
                $apply_add->is_receive = 1;
                $result = $apply_add->save();
                if(!$result){
                    throw new StoreResourceFailedException('添加清单失败', $validator->errors());
                }
                $manifest_id = $apply_add->id;
                $apply = $request->apply;
                foreach($apply as $value){
                    $apply_add = new Apply();
                    $apply_add->manifest_id = $manifest_id;
                    $apply_add->item_name = $value['item_name'];
                    $apply_add->stock = $value['stock'];
                    $apply_add->unit = $value['unit'];
                    $result = $apply_add->save();
                    if(!$result){
                        continue;
                    }
                }
            }
        return $this->result('添加成功',null);
    }

    /**
     * 申请物品编辑
     * @param Request $request
     * @return array
     */
    public function applyEdit(Request $request)
    {
        $rules = [
            'apply_id' => ['required'],
            'item_name' => ['required'],
            'stock' => ['required'],
            'unit' => ['required'],
        ];

        $payload = app('request')->only('apply_id','item_name','stock','unit');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new StoreResourceFailedException('失败', $validator->errors());
        }

        $apply_save = Apply::find($request->apply_id);
        $apply_save->item_name = $request->item_name;
        $apply_save->stock = $request->stock;
        $apply_save->unit = $request->unit;
        $result = $apply_save->save();

        if(!$result){
            throw new UpdateResourceFailedException('修改申请物品失败', $validator->errors());
        }
        return $this->result('修改成功',null);
    }

    /**
     * 申请物品列表
     * @param Request $request
     * @return array
     */
    public function applyList(Request $request)
    {
        $rules = [
            'manifest_id' => ['required'],
        ];

        $payload = app('request')->only('manifest_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('失败', $validator->errors());
        }

        $sort_list = Apply::select()->where('manifest_id',$request->manifest_id)->get();

        if(count($sort_list) > 0){
            return $this->result('成功',$sort_list);
        }
        elseif(count($sort_list) == 0){
            return $this->result('无数据',null);
        }
        else{
            throw new ResourceException('查询失败', $validator->errors());
        }

    }

    /**
     * 申请物品删除
     * @param Request $request
     * @return array
     */
    public function applyDel(Request $request)
    {
        $rules = [
            'apply_id' => ['required'],
        ];

        $payload = app('request')->only('apply_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new DeleteResourceFailedException('失败', $validator->errors());
        }

        $sort_del = Apply::find($request->apply_id);
        $result = $sort_del->delete();
        if(!$result){
            throw new DeleteResourceFailedException('删除失败', $validator->errors());
        }
        return $this->result('成功',null);
    }
}
