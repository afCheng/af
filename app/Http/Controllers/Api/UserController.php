<?php

namespace App\Http\Controllers\Api;

use App\Model\Characters;
use App\Model\Designs;
use App\Model\Groups;
use App\Model\GroupUser;
use App\Model\Node;
use App\Model\Section;
use App\User;
use Dingo\Api\Exception\DeleteResourceFailedException;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\UpdateResourceFailedException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends BaseController
{

    /**
     * 添加人员账号
     * @param Request $request
     * @return array
     */
    public function userAdd(Request $request)
    {
        $rules = [
            'name' => ['required'],
            'password' => ['required'],
            'section_id' => ['required'],
        ];

        $payload = app('request')->only('name','password','section_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new StoreResourceFailedException('添加人员账号失败', $validator->errors());
        }

        $user_add = new User();
        $user_add->name = $request->name;
        $user_add->password = $request->password;
        $user_add->section_id = $request->section_id;
        $user_add->type = 1;
        $result = $user_add->save();

        if(!$result){
            throw new StoreResourceFailedException('添加人员账号失败', $validator->errors());
        }

        return $this->result(BaseController::OK,'成功',null);

    }

    /**
     * 编辑人员账号
     * @param Request $request
     * @return array
     */
    public function userSave(Request $request)
    {
        $rules = [
            'id' => ['required'],
            'name' => ['required'],
            'password' => ['required'],
            'section_id' => ['required'],
        ];

        $payload = app('request')->only('id','name','password','section_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new UpdateResourceFailedException('编辑人员账号失败', $validator->errors());
        }

        $user_add = User::find($request->id);
        $user_add->name = $request->name;
        $user_add->password = $request->password;
        $user_add->section_id = $request->section_id;
        $result = $user_add->save();

        if(!$result){
            throw new UpdateResourceFailedException('编辑人员账号失败', $validator->errors());
        }

        return $this->result(BaseController::OK,'成功',null);

    }

    /**
     * 删除人员账号
     * @param Request $request
     * @return array
     */
    public function userDel(Request $request)
    {
        $rules = [
            'id' => ['required'],
        ];

        $payload = app('request')->only('id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new DeleteResourceFailedException('删除失败', $validator->errors());
        }

        $select_id = GroupUser::select()->where('user_id',$request->id)->get();
        if(count($select_id) > 0){
            throw new ResourceException('该人员参与在某个演练项目中,无法删除', $validator->errors());
        }

        $select_user = Characters::select()->where('user_id',$request->id)->get();
        if(count($select_user) > 0){
            throw new ResourceException('该人员参与在某个演练项目中,无法删除', $validator->errors());
        }

        $user_add = User::find($request->id);
        $result = $user_add->delete();

        if(!$result){
            throw new DeleteResourceFailedException('删除人员账号失败', $validator->errors());
        }

        return $this->result(BaseController::OK,'成功',null);

    }

    /**
     * 显示人员列表
     * @param Request $request
     * @return array
     */
    public function userList(Request $request)
    {
        $rules = [

        ];

        $payload = app('request')->only('');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('查询人员列表失败', $validator->errors());
        }

        $user = User::select('users.*','sections.section_one','sections.section_two')
                            ->join('sections','sections.id','users.section_id')
                            ->where('users.id',$request->id)->orderBy('sections.section_one')
                            ->DISTINCT()->Paginate($request->number);

        if(count($user) > 0){
            return $this->result(BaseController::OK,'成功',$user);
        } else {
            throw new ResourceException('查询人员列表失败', $validator->errors());
        }

    }

    /**
     * @param Request $request
     * @return array
     */
    public function peopleSection(Request $request)
    {
        $rules = [

        ];

        $payload = app('request')->only('');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('查询人员列表失败', $validator->errors());
        }

        $user = Section::select()->with('user');

        if(!empty($request->section_one)){
            $user = Section::select()->with('user')->where('section_one',$request->section_one);
        }

        if(!empty($request->section_two)){
            $user = Section::select()->with('user')->where('section_two',$request->section_two);
        }

        if(!empty($request->section_one) && !empty($request->section_two)){
            $user = Section::select()->with('user')->where('section_one',$request->section_one)->where('section_two',$request->section_two);
        }

        $user = $user->DISTINCT()->get();

        if(count($user) > 0){
            return $this->result(BaseController::OK,'成功',$user);
        } else {
            throw new ResourceException('查询人员列表失败', $validator->errors());
        }
    }

    /**
     * 人员签到列表
     * @param Request $request
     * @return array
     */
    public function userSign(Request $request)
    {
        $rules = [
            'design_id' => ['required'],
        ];

        $payload = app('request')->only('design_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('查询人员签到列表失败', $validator->errors());
        }

        $sign = Designs::select('designs.*','users.id as user_id','users.name as user_name','users.status as user_status','characters.character','sections.section_one','sections.section_two')
            ->join('characters','characters.design_id','designs.id')
            ->join('users','characters.user_id','users.id')
            ->join('sections','sections.id','users.section_id')
            ->where('designs.id',$request->design_id)
            ->get();

        $score_sign = Designs::select('users.name as user_name','users.id as user_id','users.status as user_status','sections.section_one','sections.section_two')
            ->join('nodes','nodes.design_id','designs.id')
            ->join('users','users.id','nodes.score_user')
            ->join('sections','sections.id','users.section_id')
            ->where('designs.id',$request->design_id)
            ->DISTINCT()
            ->get();

        $sign['score_user'] = $score_sign;

        if(count($sign) > 0){
            return $this->result(BaseController::OK,'成功',$sign);
        } else {
            throw new ResourceException('查询人员签到列表失败', $validator->errors());
        }

    }

    /**
     * 抽签
     * @param Request $request
     * @return array
     */
    public function drawLots(Request $request)
    {
        $rules = [
            'sign' => ['required'],
        ];

        $payload = app('request')->only('sign');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('人员签到失败', $validator->errors());
        }

        $data_user = $request->group_user;
        if(!empty($data_user)){
            foreach ($data_user as $value){
                $group_user = Groups::find($value['group_id']);
                $group_user->group_user = $value['user_id'];
                $res = $group_user->save();
                if(!$res) {
                    throw new StoreResourceFailedException('组长分配失败', $validator->errors());
                }
            }
        }

        $data = $request->sign;
        foreach($data as $v){
            $sign = new GroupUser();
            $sign->user_id = $v['user_id'];
            $sign->group_id = $v['group_id'];
            $result = $sign->save();

            if(!$result) {
                throw new StoreResourceFailedException('人员签到失败', $validator->errors());
            }
        }

        return $this->result(BaseController::OK,'成功',null);
    }

    /**
     * 签到
     * @param Request $request
     * @return array
     */
    public function signIn(Request $request)
    {
//        $user = $this->getUser();

        $rules = [

        ];

        $payload = app('request')->only('');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new UpdateResourceFailedException('签到失败', $validator->errors());
        }

        $sign = User::find($request->id);
        if($sign->status == 1){
            throw new UpdateResourceFailedException('你签到过了！', $validator->errors());
        }
        $sign->status = 1;
        $result = $sign->save();



        if(!$result){
            throw new UpdateResourceFailedException('签到失败', $validator->errors());
        }
        return $this->result(BaseController::OK,'成功',null);
    }

    /**
     * 查看自己本次演练的身份
     * @param Request $request
     * @return array
     */
    public function characterMy(Request $request)
    {
        $rules = [

        ];

        $payload = app('request')->only('');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('查询失败', $validator->errors());
        }

        $user = Characters::select()->where('user_id',$request->user_id)->where('design_id',$request->design_id)->get();

        $score_user = Node::select('score_user','design_id')->where('score_user',$request->user_id)->where('design_id',$request->design_id)->get();

        $user['score_user'] = $score_user;

        if(count($user) > 0){
            return $this->result(BaseController::OK,'成功',$user);
        } else {
            throw new ResourceException('查询失败', $validator->errors());
        }

    }

    /**
     * 人员数据导入
     * @param Request $request
     */
    public function import(Request $request)
    {
        $rules = [

        ];

        $payload = app('request')->only('');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new StoreResourceFailedException('附件失败', $validator->errors());
        }

        $file = $request->file('file');
        // 文件是否上传成功
        if (!$file->isValid()) {
            throw new StoreResourceFailedException('附件上传失败', $validator->errors());
        }

        // 获取文件相关信息
        $ext = $file->getClientOriginalExtension();     // 扩展名
        //文件格式
        $fileTypes = ['xls','xlsx'];
        $isInFileType = in_array($ext, $fileTypes);
        //文件格式是否成功
        if (!$isInFileType) {
            throw new StoreResourceFailedException('附件添加格式错误', $validator->errors());
        }

        // 上传文件
        $filename = date('Ymd') . uniqid() . '.' . $ext;
        //路径
        $path = $file->storeAs('excel', $filename);

        $filePath = 'storage/app/excel/'.iconv('UTF-8', 'GBK', $filename);
        $data = [];
        Excel::load($filePath, function($reader) use (&$data) {
            $data = $reader->get()->toArray();
        });
        try{
            foreach ($data as $val) {
                $select_section = Section::select('id')->where('section_one',$val['section_one'])->where('section_two',$val['section_two'])->first();

                $select_user = User::select()->where('name',$val['name'])->where('section_id',$select_section['id'])->get();

                if(count($select_user) == 0){
                    $user = new User();
                    $user->name = $val['name'];
                    $user->password = $val['password'];
                    $user->section_id = $select_section['id'];
                    $user->status = 0;
                    $user->type = 1;
                    $user->save();
                }
            }
            return $this->result(BaseController::OK,'导入成功',null);
        } catch (\Exception $exception) {
            throw new StoreResourceFailedException('导入失败', $validator->errors());
        }

    }
}
