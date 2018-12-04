<?php

namespace App\Http\Controllers\Api;


use App\Model\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginController extends BaseController
{
    //登录
    public function login(Request $request)
    {
        $user = Admin::where('name', $request->name)->where('password', $request->password)->first();

        if($user){
            $token = JWTAuth::fromUser($user);
            return $this->authenticated($token,$user);
        }

        return $this->sendFailedLoginResponse($request);
    }

    public function authenticated($token,$data){
        return $this->response->array([
            'token' => $token,
            'status_code' => 200,
            'message' => 'User Authenticated Success',
            'data' => $data,
        ]);
    }

    public function sendFailedLoginResponse($data){
        return $this->response->array([
            'status_code' => 402,
            'message' => 'Password or user name error ！',
            'data' => $data,
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function loginout(Request $request)
    {
        $old_token = JWTAuth::getToken();
        JWTAuth::invalidate($old_token);
        return $this->response->array([
            'status_code' => 200,
            'message' => '成功',
        ]);

    }
}
