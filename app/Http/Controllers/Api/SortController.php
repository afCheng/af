<?php

namespace App\Http\Controllers\Api;

use App\Model\Item;
use App\Model\Sort;
use Dingo\Api\Exception\DeleteResourceFailedException;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\UpdateResourceFailedException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SortController extends BaseController
{
    /**
     * 分类编辑
     * @param Request $request
     * @return array
     */
    public function sortEdit(Request $request)
    {
        $rules = [
            'name' => ['required'],
        ];

        $payload = app('request')->only('name');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new StoreResourceFailedException('失败', $validator->errors());
        }

        if(empty($request->sort_id)){

            $sort = Sort::select()->where('name',$request->name)->get();
            if(count($sort) > 0){
                throw new ResourceException('类名已存在', $validator->errors());
            }
            $sort_add = new Sort();
            $sort_add->name = $request->name;
            $result = $sort_add->save();
            if(!$result){
                throw new StoreResourceFailedException('添加分类失败', $validator->errors());
            }

            return $this->result('添加成功',null);
        } else {
            $sort_save = Sort::find($request->sort_id);
            $sort_save->name = $request->name;
            $result = $sort_save->save();
            if(!$result){
                throw new UpdateResourceFailedException('修改分类失败', $validator->errors());
            }
            return $this->result('修改成功',null);
        }
    }

    /**
     * 分类列表
     * @param Request $request
     * @return array
     */
    public function sortList(Request $request)
    {
        $rules = [

        ];

        $payload = app('request')->only('');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('失败', $validator->errors());
        }

        $sort_list = Sort::all();

        if(count($sort_list) > 0){
            return $this->result('成功',$sort_list);
        }
        elseif(count($sort_list) == 0){
            $data['data'] = [];
            return $this->result('无数据',$data);
        }
        else{
            throw new ResourceException('查询失败', $validator->errors());
        }

    }

    /**
     * 删除分类
     * @param Request $request
     * @return array
     */
    public function sortDel(Request $request)
    {
        $rules = [
            'sort_id' => ['required'],
        ];

        $payload = app('request')->only('sort_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new DeleteResourceFailedException('失败', $validator->errors());
        }

        $sort_info = Item::select()->where('sort_id',$request->sort_id)->get();
        if(count($sort_info) > 0){
            throw new DeleteResourceFailedException('请先删除该分类相对应的物品', $validator->errors());
        }

        $sort_del = Sort::find($request->sort_id);
        $result = $sort_del->delete();
        if(!$result){
            throw new DeleteResourceFailedException('删除失败', $validator->errors());
        }
        return $this->result('成功',null);
    }

}
