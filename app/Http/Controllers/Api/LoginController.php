<?php

namespace App\Http\Controllers\Api;

use App\User;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\UpdateResourceFailedException;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginController extends BaseController
{
    use AuthenticatesUsers;

    /**
     * 刷新token
     * @return array
     */
    public function apiRefreshToken()
    {
        $old_token = JWTAuth::getToken();
        $token = JWTAuth::refresh($old_token);
        JWTAuth::invalidate($old_token);
        return $this->result($this::OK, "成功", $token);
    }

    /**
     * 登录
     * @param Request $request
     * @return array
     */
    public function login(Request $request)
    {
        $rules = [
            'name' => ['required'],
            'section_id' => ['required'],
            'password' => ['required'],
        ];

        $payload = app('request')->only('name','section_id','password');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('登陆失败', $validator->errors());
        }

        //判断用户名密码是否正确
        $user = User::where('name',$request->name)->where('password',$request->password)->where('section_id',$request->section_id)->first();
        if(is_null($user)){
            throw new ResourceException('账号或密码不正确',$validator->errors());
        } else {
            $token = JWTAuth::fromUser($user);
            $user = $user->toArray();
            $user['token'] = $token;
            return $this->result($this::OK, "成功", $user);
        }
    }

    /**
     * 我的部门
     * @param Request $request
     * @return array
     */
    public function mySection(Request $request)
    {
        $rules = [
            'name' => ['required'],
        ];

        $payload = app('request')->only('name');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('失败', $validator->errors());
        }

        $section = User::select()->with('section')->where('name',$request->name)->get();

        foreach($section as $value){
            $mySection = $value->section;
        }

        if(count($section) == 0){
            throw new ResourceException('没有相关部门',$validator->errors());
        }

        return $this->result($this::OK, "成功", $section);
    }


    /**
     * 退出登录
     * @return array
     */
    public function loginOut()
    {
        $old_token = JWTAuth::getToken();
        JWTAuth::invalidate($old_token);
        return $this->result($this::OK, "成功", null);
    }

    /**
     * 修改密码
     * @param Request $request
     */
    public function passwordSave(Request $request)
    {
//        $user = $this->getUser();

        $rules = [
            'id' => ['required'],
            'name' => ['required'],
            'password' => ['required'],
            'new_password' => ['required'],
        ];

        $payload = app('request')->only('id','name','password','new_password');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new UpdateResourceFailedException('修改密码失败', $validator->errors());
        }

        $old_password = User::select()->where('name',$request->name)->where('password',$request->password)->get();
        if(count($old_password) > 0){
            $password = User::find($request->id);
            $password->password = $request->new_password;
            $result = $password->save();
            if(!$result){
                throw new UpdateResourceFailedException('修改密码失败', $validator->errors());
            }
            return $this->result($this::OK, "修改成功", null);
        } else {
            throw new UpdateResourceFailedException('修改失败', $validator->errors());
        }

    }
}
