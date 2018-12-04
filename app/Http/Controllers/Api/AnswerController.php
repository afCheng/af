<?php

namespace App\Http\Controllers\Api;

use App\Model\Answers;
use App\Model\Groups;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class AnswerController extends BaseController
{
    /**
     * 答题
     * @param Request $request
     * @return array
     */
    public function answerAdd(Request $request)
    {
//        $user = $this->getUser();

        $rules = [
            'user_id' => ['required'],
            'problem_id' => ['required'],
        ];

        $payload = app('request')->only('problem_id','user_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new StoreResourceFailedException('答题失败', $validator->errors());
        }

        //判断有没有答过题目
        $problem = Answers::select()->where('user_id',$request->user_id)->where('problem_id',$request->problem_id)->first();

        if(is_null($problem)){
            throw new StoreResourceFailedException('此题你已答过！', $validator->errors());
        }

        //如果是不分组的进入下面的if
        $not_group = Groups::select()->where('id',$request->group_id)->first();

        if($not_group['name'] == '此演练不分组'){
            $answer_add = new Answers();
            $answer_add->problem_id = $request->problem_id;
            $answer_add->user_id = $request->user_id;
            if(!empty($request->answer)){
                $answer_add->answer = $request->answer;
            } else {
                $answer_add->answer = '空';
            }

            $result = $answer_add->save();
            if($result){
                return $this->result($this::OK, "成功",null);
            } else {
                throw new StoreResourceFailedException('答题失败', $validator->errors());
            }
        }

        $group_user = Groups::select()->where('group_user',$request->user_id)->where('id',$request->group_id)->get();

        if(count($group_user) <= 0){
            throw new ResourceException('请组长答题！', $validator->errors());
        } else {
            $answer_add = new Answers();
            $answer_add->problem_id = $request->problem_id;
            $answer_add->user_id = $request->user_id;
            $answer_add->answer = $request->answer;
            $result = $answer_add->save();

            //判断是否成功
            if(!$result){
                throw new StoreResourceFailedException('答题失败', $validator->errors());
            }
        }

        return $this->result($this::OK, "成功",null);
    }

    /**
     * 已回答问题的人
     * @param Request $request
     * @return array
     */
    public function answerUser(Request $request)
    {
        $rules = [
            'problem_id' => ['required'],
        ];

        $payload = app('request')->only('problem_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('查询答题人失败', $validator->errors());
        }

//        $score_list = Groups::select()->where('design_id',$request->design_id)->get();

        $answer_user = Answers::select('users.name as user_name')
            ->join('users','users.id','answers.user_id')
            ->where('answers.problem_id',$request->problem_id)
            ->get();

//        $answer_user['count_user'] = count($score_list);

        return $this->result($this::OK, "成功",$answer_user);
    }
}
