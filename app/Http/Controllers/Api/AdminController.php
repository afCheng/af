<?php

namespace App\Http\Controllers\Api;

use App\Model\Admin;
use Dingo\Api\Exception\DeleteResourceFailedException;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\UpdateResourceFailedException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AdminController extends BaseController
{
    /**
     * 管理员编辑
     * @param Request $request
     * @return array
     */
    public function adminEdit(Request $request)
    {
        $rules = [
            'name' => ['required'],
            'password' => ['required'],
            'is_admin' => ['required'],
        ];

        $payload = app('request')->only('name','password','is_admin');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new StoreResourceFailedException('失败', $validator->errors());
        }

        if(!empty($request->admin_id)){
            $admin_save = Admin::find($request->admin_id);
            $admin_save->name = $request->name;
            $admin_save->password = $request->password;
            $admin_save->is_admin = $request->is_admin;
            $result = $admin_save->save();
            if(!empty($result)){
                throw new UpdateResourceFailedException('修改失败', $validator->errors());
            }
            return $this->result('修改成功',null);
        }
        else {
            $admin_add = new Admin();
            $admin_add->name = $request->name;
            $admin_add->password = $request->password;
            $admin_add->is_admin = $request->is_admin;
            $result = $admin_add->save();
            if(!$result){
                throw new StoreResourceFailedException('添加失败', $validator->errors());
            }
            return $this->result('添加成功',null);
        }
    }

    /**
     * 查询管理员
     * @param Request $request
     * @return array
     */
    public function adminSelect(Request $request)
    {
        $rules = [
            'admin_id' => ['required'],
        ];

        $payload = app('request')->only('admin_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('失败', $validator->errors());
        }

        $admin_info = Admin::select()->where('id',$request->admin_id)->first();
        if(is_null($admin_info)){
            throw new ResourceException('查询失败', $validator->errors());
        }
        return $this->result('成功',$admin_info);
    }

    /**
     * 管理员列表
     * @return array
     */
    public function adminList()
    {
        $rules = [

        ];

        $payload = app('request')->only('');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('失败', $validator->errors());
        }

        $admin_list = Admin::select('name')->where('is_admin',0)->get();

        if(count($admin_list) > 0){
            return $this->result('成功',$admin_list);
        }
        elseif(count($admin_list) == 0){
            return $this->result('暂无数据',null);
        }
        else{
            throw new ResourceException('查询失败', $validator->errors());
        }
    }

    /**
     * 管理员删除
     * @param Request $request
     * @return array
     */
    public function adminDel(Request $request)
    {
        $rules = [
            'admin_id' => ['required'],
        ];

        $payload = app('request')->only('admin_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new DeleteResourceFailedException('失败', $validator->errors());
        }

        $admin_del = Admin::find($request->admin_id);
        $result = $admin_del->delete();
        if(!$result){
            throw new DeleteResourceFailedException('删除失败', $validator->errors());
        }
        return $this->result('删除成功',null);
    }

}
