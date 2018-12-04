<?php

namespace App\Http\Controllers\Api;

use App\Model\Answers;
use App\Model\Characters;
use App\Model\Scores;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class ScoreController extends BaseController
{
    /**
     * 对答案进行评分
     * @param Request $request
     * @return array
     */
    public function scoreAdd(Request $request)
    {
        $rules = [
            'scores' => ['required'],
        ];

        $payload = app('request')->only('scores');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new StoreResourceFailedException('评分失败', $validator->errors());
        }

        //对该答案进行评分
        $data_score = $request->scores;
        foreach($data_score as $value){
            $answer = Answers::select()->where('id',$value['answer_id'])->first();

            if(is_null($answer)){
                throw new ResourceException('没有此答案', $validator->errors());
            }

            $score_add = new Scores();
            $score_add->answer_id =$value['answer_id'];
            $score_add->score = $value['score'];
            $result = $score_add->save();

            //判断是否成功
            if(!$result){
                throw new StoreResourceFailedException('评分失败', $validator->errors());
            }
        }
        return $this->result($this::OK, "成功",null);
    }

    /**
     * 已评分的人
     * @param Request $request
     * @return array
     */
    public function scoreUser(Request $request)
    {
        $rules = [
            'problem_id' => ['required'],
            'design_id' => ['required'],
        ];

        $payload = app('request')->only('problem_id','design_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('查询评分人失败', $validator->errors());
        }

//        $score_list = Characters::select()->where('design_id',$request->design_id)->where('character',2)->get();

        $score_user = Scores::select('users.name as user_name','users.id as user_id')
            ->join('answers','answers.id','scores.answer_id')
            ->join('users','users.id','answers.user_id')
            ->where('answers.problem_id',$request->problem_id)
            ->get();

//        $score_user['count_score'] = count($score_list);

        return $this->result($this::OK, "成功",$score_user);
    }
}
