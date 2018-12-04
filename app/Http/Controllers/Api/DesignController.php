<?php

namespace App\Http\Controllers\Api;

use App\Model\Answers;
use App\Model\AttachmentDesign;
use App\Model\Attachments;
use App\Model\Characters;
use App\Model\Designs;
use App\Model\Groups;
use App\Model\GroupUser;
use App\Model\Node;
use App\Model\Problems;
use App\Model\Scores;
use App\Model\Section;
use App\User;
use Dingo\Api\Exception\DeleteResourceFailedException;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\UpdateResourceFailedException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Model\Draft;

class DesignController extends BaseController
{
    /**
     * 新增流程设计
     * @param Request $request
     * @return array
     */
    public function designAdd(Request $request)
    {

        $rules = [
            'name' => ['required'],
            'description' => ['required'],
            'design_time' => ['required'],

            'node_name' => ['required'],
            'group_name' => ['required'],
            'character2' => ['required'],

        ];

        $payload = app('request')->only('name','design_time','description','node_name','describe','answer','score','problem_time','group_name','character2');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new StoreResourceFailedException('流程设计失败', $validator->errors());
        }
//        DB::beginTransaction();
//
//        try{
            //新增流程
            $design = new Designs();
            $design->name = $request->name;
            $design->description = $request->description;
            $design->design_time = $request->design_time;
            $design->status = 0;
            $result = $design->save();

            //判断新增是否成功
            if(!$result){
                throw new StoreResourceFailedException('流程设计失败', $validator->errors());
            }

            //新增环节
            $node_data = $request->node_name;
            foreach($node_data as $val) {
                $node_add = new Node();
                $node_add->name = $val['node_name'];
                $node_add->description = $val['description'];
                $node_add->a_time = $val['a_time'];
                $node_add->score_time =$val['score_time'];
                $node_add->time = $val['time'];
                $node_add->score_user = $val['score_user'];
                $node_add->design_id = $design->id;
                $node_result = $node_add->save();

                if(!$node_result){
                    throw new StoreResourceFailedException('新增环节失败', $validator->errors());
                }

//                // 直播创建频道
//                $url="https://vcloud.163.com/app/channel/create";
//                $data = $this->postDataCurl($url,array("name"=>$request->name,"type"=>0));
//                if($data->code != 200){
//                    throw new ResourceException('频道创建失败', $validator->errors());
//                }
//                $cid = $data->ret->cid;

                // 新增问题
                $problem_add = new Problems();
                $problem_add->cid = 1;
                $problem_add->node_id = $node_add->id;
                $problem_add->describe = $val['describe'];
                $problem_add->answer = $val['answer'];
                $problem_add->score = $val['score'];
                $problem_add->status = 0;
                $problem_add->problem_time = $val['problem_time'];
                $result = $problem_add->save();

                if (!$result) {
                    throw new StoreResourceFailedException('新增问题失败', $validator->errors());
                }

//                $url="https://vcloud.163.com/app/channel/update";
//                $pro = Problems::select('cid')->where('id',$request->problem_id)->get();
//                $cid = $pro->cid;
//                if(!empty($request->problem_id)){
//                    $data = json_decode($this->postDataCurl($url, array("name" => $request->name.$request->problem_id,"cid" => $cid,"type" => 0)));
//                } else {
//                    $data = json_decode($this->postDataCurl($url, array("name" => $request->name.$problem_add->id,"cid" => $cid,"type" => 0)));
//                }
//
//                if($data->code != 200){
//                    throw new ResourceException('新增或编辑直播频道失败', $validator->errors());
//                }
                //判断不为空的时候
                $attachment_id = $val['attachment_id'];
                if(!empty($attachment_id)){
                    foreach($attachment_id as $value){
                        //对该流程设计添加附件(图片、视频)
                        $attachment = new AttachmentDesign();
                        $attachment->attachment_id = $value;
                        $attachment->node_id = $node_add->id;
                        $result = $attachment->save();

                        if (!$result) {
                            throw new StoreResourceFailedException('对该流程设计添加附件(图片、视频)失败', $validator->errors());
                        }
                    }
                }
            }

        //给人员分配角色
            $user_character = $request->character2;
            if(empty($user_character)){
                throw new StoreResourceFailedException('人员分配角色不能为空', $validator->errors());
            }
//            Cache::put('user',$user_character,1440);
//            session(['user' => $user_character]);
            foreach($user_character as $value){
                $character = new Characters();
                $character->design_id = $design->id;
                $character->user_id = $value['user_id'];
                $character->character = $value['character'];
                $result = $character->save();
            }
            if (!$result) {
                throw new StoreResourceFailedException('人员分配角色失败', $validator->errors());
            }

            //对该流程设计进行分组
            $group_data = $request->group_name;
            foreach ($group_data as $val) {
                $group_add = new Groups();
                $group_add->name = $val;
                $group_add->design_id = $design->id;
                $result = $group_add->save();
            }
            if (!$result) {
                throw new StoreResourceFailedException('新增分组失败', $validator->errors());
            }

//        } catch (\Exception $e) {
//            DB::rollback();//事务回滚
//            throw new StoreResourceFailedException('流程设计失败', $validator->errors());
//        }
        if(!empty($request->draft_id)){
            $draft = Draft::find($request->draft_id);
            $result = $draft->delete();
            if (!$result) {
                throw new DeleteResourceFailedException('草稿删除失败', $validator->errors());
            }
        }
        return $this->result($this::OK, "成功", null);
    }

    /**
     * 保存草稿
     * @param Request $request
     * @return array
     */
    public function theDraft(Request $request)
    {
        $rules = [
            'user_id' => ['required'],
            'one' => ['required'],
            'two' => ['required'],
            'three' => ['required'],
            'four' => ['required'],
            'five' => ['required'],
        ];

        $payload = app('request')->only('user_id','one','two','three','four','five');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new StoreResourceFailedException('失败', $validator->errors());
        }

        $draft_info = Draft::select()->where('user_id',$request->user_id)->where('status',0)->first();
        if(is_null($draft_info)){
            $draft = new Draft();
            $draft->user_id = $request->user_id;
            $draft->one = $request->one;
            $draft->two = $request->two;
            $draft->three = $request->three;
            $draft->four = $request->four;
            $draft->five = $request->five;
            $draft->status = 0;
            $result = $draft->save();
            if (!$result) {
                throw new StoreResourceFailedException('添加草稿失败', $validator->errors());
            }
            return $this->result($this::OK, "成功", null);
        } else {
            $draft = Draft::find($request->draft_id);
            $draft->one = $request->one;
            $draft->two = $request->two;
            $draft->three = $request->three;
            $draft->four = $request->four;
            $draft->five = $request->five;
            $result = $draft->save();
            if(!$result){
                throw new StoreResourceFailedException('修改草稿失败', $validator->errors());
            }
            return $this->result($this::OK, "成功", null);
        }
    }

    /**
     * 是否有草稿
     * @param Request $request
     * @return array
     */
    public function isDraft(Request $request)
    {
        $rules = [
            'user_id' => ['required'],
        ];

        $payload = app('request')->only('user_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('失败', $validator->errors());
        }

        $draft_info = Draft::select()->where('user_id',$request->user_id)->where('status',0)->first();

        if(is_null($draft_info)){
            throw new ResourceException('没有草稿', $validator->errors());
        }

        return $this->result($this::OK, "成功", $draft_info);
    }
    /**
     * 上传素材
     * @param Request $request
     * @return array
     */
    public function attachment(Request $request)
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
            $fileTypes = ['jpg', 'jpeg', 'png', 'mp4', 'rmvb', 'avi', 'txt'];
            $isInFileType = in_array($ext, $fileTypes);
            //文件格式是否成功
            if (!$isInFileType) {
                throw new StoreResourceFailedException('附件添加格式错误', $validator->errors());
            }

            // 上传文件
            $filename = date('Ymd') . uniqid() . '.' . $ext;
            //路径
            $path = $file->storeAs('public', $filename);

            $attachments_add = new Attachments();
            $attachments_add->original_file = $filename;
            $attachments_add->file_path = $path;
            $attachments_add->description = $request->description;
            $result = $attachments_add->save();
            if (!$result) {
                throw new StoreResourceFailedException('附件添加失败', $validator->errors());
            }

        return $this->result($this::OK, "成功", $attachments_add->id);
    }

    /**
     * 删除素材
     * @param Request $request
     */
    public function attachmentDel(Request $request)
    {
        $rules = [
            'attachment_id' => ['required'],
        ];

        $payload = app('request')->only('attachment_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new DeleteResourceFailedException('删除附件失败', $validator->errors());
        }

        $attachment = Attachments::find($request->attachment_id);

        $res = Storage::delete($attachment['file_path']);

        if(!$res){
            throw new DeleteResourceFailedException('删除素材文件失败', $validator->errors());
        }

        $result = $attachment->delete();

        if(!$result){
            throw new DeleteResourceFailedException('删除失败', $validator->errors());
        }
        return $this->result($this::OK, "成功", null);
    }

    /**
     * 我可参演的演练项目
     * @return array
     */
    public function designMy(Request $request)
    {
        $rules = [

        ];

        $payload = app('request')->only('');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('查询可参演的演练项目失败', $validator->errors());
        }

        $design_my = Designs::select('designs.*')
            ->join('groups','groups.design_id','designs.id')
            ->join('group_user','group_user.group_id','groups.id')
            ->where('group_user.user_id',$request->id)
            ->where('design_time','>=',date('Y-m-d H:i:s'));

        if(!empty($request->name)){
            $design_my = $design_my->where('designs.name','like','%'.$request->name.'%');
        }

        $design_my = $design_my->get();

        if(count($design_my) > 0){
            return $this->result($this::OK, "成功",$design_my);
        } elseif(count($design_my) == 0) {
            return $this->result($this::OK, "暂无数据",null);
        } else {
            throw new ResourceException('查询可参演的演练项目失败', $validator->errors());
        }

    }

    /**
     * 我导演的演练项目
     * @return array
     */
    public function directorMy(Request $request)
    {
        $rules = [

        ];

        $payload = app('request')->only('');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('查询可参演的演练项目失败', $validator->errors());
        }

        $design_my = Designs::select('designs.*')
            ->join('characters','characters.design_id','designs.id')
            ->join('users','users.id','characters.user_id')
            ->where('characters.user_id',$request->id)
            ->where('characters.character',0);

        if(!empty($request->name)){
            $design_my = $design_my->where('designs.name','like','%'.$request->name.'%');
        }

        $design_my = $design_my->get();

        if(count($design_my) > 0){
            return $this->result($this::OK, "成功",$design_my);
        } elseif(count($design_my) == 0) {
            return $this->result($this::OK, "暂无数据",null);
        } else {
            throw new ResourceException('查询可参演的演练项目失败', $validator->errors());
        }

    }

    /**
     * 我观看的演练项目
     * @return array
     */
    public function look(Request $request)
    {
        $rules = [

        ];

        $payload = app('request')->only('');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('查询演练项目失败', $validator->errors());
        }

        $look_me = Designs::select('designs.id')
            ->join('characters','characters.design_id','designs.id')
            ->where('characters.user_id',$request->id)
            ->DISTINCT()
            ->get();

        $res = [];
        foreach ($look_me as $value)
        {
            $res[] = $value['id'];
        }

        $look = Designs::select()->whereNotIn('id',$res);


        if(!empty($request->name)) {
            $look = $look->where('designs.name','like','%'.$request->name.'%');
        }

        $look = $look->DISTINCT()->get();

        if(count($look) > 0){
            return $this->result($this::OK, "成功",$look);
        } elseif(count($look) == 0) {
            return $this->result($this::OK, "暂无数据",null);
        } else {
            throw new ResourceException('查询演练项目失败', $validator->errors());
        }
    }

    /**
     * 历史演练项目
     * @return array
     */
    public function designHistory(Request $request)
    {
        $rules = [

        ];

        $payload = app('request')->only('');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('查询历史的演练项目失败', $validator->errors());
        }

        $design_history= Designs::select();

        if(!empty($request->name)){
            $design_history = $design_history->where('name','like','%'.$request->name.'%');
        }

        $design_history = $design_history->where('status',2)->get();
        if(count($design_history) > 0){
            return $this->result($this::OK, "成功",$design_history);
        } elseif(count($design_history) == 0) {
            return $this->result($this::OK, "暂无数据",null);
        } else {
            throw new ResourceException('查询可参演的演练项目失败', $validator->errors());
        }
    }

    /**
     * 我的历史演练项目
     * @return array
     */
    public function designHistoryMy(Request $request)
    {
        $rules = [

        ];

        $payload = app('request')->only('');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('查询历史的演练项目失败', $validator->errors());
        }

        $design_history= Designs::select('designs.*')
            ->join('characters','characters.design_id','designs.id')
            ->where('characters.user_id',$request->id)
            ->where('status',2);

        if(!empty($request->name)){
            $design_history = $design_history->where('designs.name','like','%'.$request->name.'%');
        }

        $design_history = $design_history->get();

        if(count($design_history) > 0){
            return $this->result($this::OK, "成功",$design_history);
        } elseif(count($design_history) == 0) {
            return $this->result($this::OK, "暂无数据",null);
        } else {
            throw new ResourceException('查询可参演的演练项目失败', $validator->errors());
        }
    }

    /**
     * 我参与的演练项目
     * @return array
     */
    public function designMe(Request $request)
    {

        $rules = [

        ];

        $payload = app('request')->only('');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('查询历史的演练项目失败', $validator->errors());
        }

        $design_history= Designs::select('designs.*')
            ->join('characters','characters.design_id','designs.id')
            ->where('characters.user_id',$request->id);

        if(!empty($request->name)){
            $design_history = $design_history->where('designs.name','like','%'.$request->name.'%');
        }

        $design_history = $design_history->get();
        $score_user = Designs::select('designs.*')->join('nodes','nodes.design_id','designs.id')->where('nodes.score_user',$request->id)->DISTINCT()->get();
        $design_history['score_user'] = $score_user;
        if(count($design_history) > 0){
            return $this->result($this::OK, "成功",$design_history);
        } elseif(count($design_history) == 0) {
            return $this->result($this::OK, "暂无数据",null);
        } else {
            throw new ResourceException('查询可参演的演练项目失败', $validator->errors());
        }
    }

    /**
     * 开始演练
     * @param Request $request
     */
    public function start(Request $request)
    {
        $rules = [
            'design_id' => ['required'],
        ];

        $payload = app('request')->only('design_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('查询失败', $validator->errors());
        }

        $design = Designs::find($request->design_id);
        $design->status = 1;
        $result = $design->save();
        if(!$result){
            throw new StoreResourceFailedException('开始失败', $validator->errors());
        }

        $node = Node::select('id','name','description','a_time','time','score_time','score_user')->where('design_id',$request->design_id)->get();
        if(!$node){
            throw new ResourceException('查询失败', $validator->errors());
        }
//        $user = session('user');
        $start = [];
        $start['node_name'] = $node;
//        $start['user'] = $user;
        if(count($node) > 0){
            return $this->result($this::OK, "成功",$start);
        } elseif(count($node) == 0){
            return $this->result($this::OK, "暂无数据",null);
        } else {
            throw new ResourceException('查询失败', $validator->errors());
        }


    }

    /**
     * 结束演练
     * @param Request $request
     * @return array
     */
    public function end(Request $request)
    {
        $rules = [

        ];

        $payload = app('request')->only('');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('失败', $validator->errors());
        }

        if(!empty($request->user_id)){
            $user = $request->user_id;
            foreach ($user as $value){
                $user = User::find($value);
                $user->status = 0;
                $user->save();
            }
        }
        if(!empty($request->design_id)) {
            $design = Designs::find($request->design_id);
            $design->status = 2;
            $result = $design->save();
        }
        if(!$result){
            throw new UpdateResourceFailedException('结束演练失败', $validator->errors());
        }

        return $this->result($this::OK, "结束演练成功",null);
    }

    /**
     * 查看演练状态进程
     * @param Request $request
     * @return array
     */
    public function lookStatus(Request $request)
    {
        $rules = [
            'design_id' => ['required'],
        ];

        $payload = app('request')->only('design_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('失败', $validator->errors());
        }

        $status = Designs::select('design_location')->where('id',$request->design_id)->first();

        if(is_null($status)){
            throw new ResourceException('无演练状态', $validator->errors());
        }

        return $this->result($this::OK, "成功",$status);

    }

    /**
     * 存储演练进程
     * @param Request $request
     * @return array
     */
    public function saveDesign(Request $request)
    {
        $rules = [
            'design_id' => ['required'],
            'design_location' => ['required'],
        ];

        $payload = app('request')->only('design_id','design_location');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new UpdateResourceFailedException('失败', $validator->errors());
        }

        $design_location = Designs::find($request->design_id);

        $design_location->design_location = $request->design_location;

        $result = $design_location->save();

        if(!$result){
            throw new UpdateResourceFailedException('存储失败', $validator->errors());
        }

        return $this->result($this::OK, "成功",null);
    }

    /**
     * 播放素材
     * @param Request $request
     * @return array
     */
    public function playVideo(Request $request)
    {
        $rules = [
            'node_id' => ['required'],
        ];

        $payload = app('request')->only('node_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('素材播放失败', $validator->errors());
        }

//        $node = Node::select()->with('attachment')->where('id',$request->node_id)->get();

        $node = Node::select('attachments.original_file','attachments.file_path','attachments.description','nodes.*')
                        ->join('attachment_node','attachment_node.node_id','nodes.id')
                        ->join('attachments','attachments.id','attachment_node.attachment_id')
                        ->where('attachment_node.node_id',$request->node_id)
                        ->get();

        if(count($node) > 0){
            return $this->result($this::OK, "成功",$node);
        } elseif (count($node) == 0) {
            return $this->result($this::OK, "暂无数据",null);
        } else {
            throw new ResourceException('素材播放失败', $validator->errors());
        }

    }

    /**
     * 播放题目(包含参考答案、分值)
     * @param Request $request
     * @return array
     */
    public function playProblem(Request $request)
    {
        $rules = [
            'node_id' => ['required'],
        ];

        $payload = app('request')->only('node_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('题目播放失败', $validator->errors());
        }

        $node = Node::select()->where('id',$request->node_id)->with('problem')->get();

        if(count($node) > 0){
            return $this->result($this::OK, "成功",$node);
        } elseif (count($node) == 0) {
            return $this->result($this::OK, "暂无数据",null);
        } else {
            throw new ResourceException('题目播放失败', $validator->errors());
        }
    }

    /**
     * 展示答案
     * @param Request $request
     * @return array
     */
    public function playAnswer(Request $request)
    {
        $rules = [
            'problem_id' => ['required'],
        ];

        $payload = app('request')->only('problem_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('展示答案失败', $validator->errors());
        }
//        $result = [];
        $answer = Answers::select()->where('problem_id',$request->problem_id)->get();
//        $res = [];
        foreach($answer as $value){
            $list = $value->user;
//            $res[] = $list['id'];
        }
//
//        $group = Groups::select('groups.*')
//            ->join('designs','designs.id','groups.design_id')
//            ->whereIn('group_user',$res)
//            ->where('design_id',$request->design_id)
//            ->get();
//        $answer['group'] = $group;
//        $result[] = $answer;
        if(count($answer) > 0){
            return $this->result($this::OK, "成功",$answer);
        } elseif (count($answer) == 0) {
            return $this->result($this::OK, "暂无数据",null);
        } else {
            throw new ResourceException('展示答案失败', $validator->errors());
        }
    }

    /**
     * 显示排名
     * @param Request $request
     * @return array
     */
    public function accordRank(Request $request)
    {
        $rules = [
            'design_id' => ['required'],
        ];

        $payload = app('request')->only('design_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('显示排名失败', $validator->errors());
        }

        //查演练
        $design = Designs::with('nodes')->find($request->design_id);

        $groups = $design->group()->get();
        //------------------
        $result = [];
        if($groups[0]['name'] == '此演练不分组'){
            $group_user = GroupUser::select('group_user.user_id','users.name')->join('users','users.id','group_user.user_id')->where('group_id',$groups[0]['id'])->get();
            //每个组
            foreach ($group_user as $group)
            {
                $node_user_score = 0;
                //每个环节
                foreach ($design->nodes as $node)
                {
                    //每个环节的每个组长
                    $answer = $node->problem->answers()->where('user_id',$group->user_id)->first();
                    if(is_null($answer)){
                        continue;
                    }

                    $score = Scores::where('answer_id',$answer['id'])
//                        ->groupBy('id')
//                        ->orderBy('score', 'DESC')
                        ->avg('score');;
                    if(is_null($score)){
                        continue;
                    }
                    $scores = $score;
                    $node_user_score += $scores;
                }
                $result[] = array_merge($group->toArray(), [
                    'group_score' => $node_user_score
                ]);
            }

            array_multisort(array_column($result,'group_score'),SORT_DESC,$result);

            if(!$result){
                throw new ResourceException('显示排名失败', $validator->errors());
            }
            return $this->result($this::OK, "成功",$result);
        }

        //每个组
        foreach ($groups as $group)
        {
            $node_user_score = 0;
            //每个环节
            foreach ($design->nodes as $node)
            {
                //每个环节的每个组长
                $answer = $node->problem->answers()->where('user_id',$group->group_user)->first();
                if(is_null($answer)){
                    continue;
                }

                $score = Scores::where('answer_id',$answer['id'])
//                    ->groupBy('id')
//                    ->orderBy('score', 'DESC')
                    ->avg('score');
                if(is_null($score)){
                    continue;
                }
                $scores = $score;
                $node_user_score += $scores;
            }
            $result[] = array_merge($group->toArray(), [
                'group_score' => $node_user_score
            ]);
        }
        array_multisort(array_column($result,'group_score'),SORT_DESC,$result);

        if(!$result){
            throw new ResourceException('显示排名失败', $validator->errors());
        }

        return $this->result($this::OK, "成功",$result);
    }
    /**
     * 环节的分数
     * @param Request $request
     * @return array
     */
    public function scoreNode(Request $request)
    {
        $rules = [
            'design_id' => ['required'],
            'problem_id' => ['required'],
        ];

        $payload = app('request')->only('design_id','problem_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('显示失败', $validator->errors());
        }

        //查演练
        $design = Designs::with('nodes')->find($request->design_id);
        $groups = $design->group()->get();

        $result = [];
        $res = [];
        if($groups[0]['name'] == '此演练不分组'){
            $group_user = GroupUser::select('user_id')->where('group_id',$groups[0]['id'])->get();
            foreach($group_user as $user){
                $res[] = $user['user_id'];
            }
        } else {
            foreach ($groups as $group)
            {
                $user_id = $group->group_user;
                $res[] = $user_id;
            }
        }
        $scores = Answers::select('scores.answer_id','answers.answer','answers.user_id','users.name as user_name',DB::raw('AVG(scores.score) as score'))
            ->join('users','users.id','answers.user_id')
            ->join('problems','problems.id','answers.problem_id')
            ->join('scores','scores.answer_id','answers.id')
            ->whereIn('answers.user_id',$res)
            ->where('problems.id',$request->problem_id)
            ->groupBy('answers.user_id','scores.answer_id')
            ->orderBy('score', 'DESC')
            ->get()->toArray();



//        $scores = Answers::
//        join('users','users.id','answers.user_id')
//            ->join('problems','problems.id','answers.problem_id')
//            ->join('scores','scores.answer_id','answers.id')
//            ->whereIn('answers.user_id',$res)
//            ->where('problems.id',$request->problem_id)
////            ->groupBy('scores.id')
//            ->orderBy('scores.score', 'DESC')
//            ->avg('scores.score');

//        $scores['groups'] = $groups;

//        $result[] = $scores;
        return $this->result($this::OK, "成功",$scores);
    }

    /**
     * 参演组 显示抽签结果
     * @param Request $request
     * @return array
     */
    public function theDraw(Request $request)
    {
//        $user = $this->getUser();

        $rules = [
            'design_id' => ['required'],
        ];

        $payload = app('request')->only('design_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('显示抽签结果失败', $validator->errors());
        }

        $my = Groups::select()->where('design_id',$request->design_id)->get();
        foreach($my as $value){
            $me = $value->user;
            foreach ($me as $section){
                $sections = $section->section;
            }
        }

        if(count($my) > 0){
            return $this->result($this::OK, "成功",$my);
        } elseif(count($my) == 0){
            return $this->result($this::OK, "暂无数据",null);
        } else {
            throw new ResourceException('显示抽签结果失败', $validator->errors());
        }
    }

    /**
     * 参演组 显示点评
     * @param Request $request
     * @return array
     */
    public function showReview(Request $request)
    {
        $rules = [
            'problem_id' => ['required'],
        ];

        $payload = app('request')->only('problem_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('显示点评失败', $validator->errors());
        }

        $result = [];
        $scores = Problems::select()->where('id',$request->problem_id)->get();
        foreach($scores as $value){
            $answers = $value->answers;
            foreach ($answers as $answer){
                $users = $answer->user->type;
                if($users == 0){
                    $res = $answer->toArray();
                    $res['username'] = $answer->user->name;
                    $res['score'] = $answer->score->score;
                    $res['score_content'] = $answer->score->score_content;

                    array_push($result,$res);
                }

            }
        }

        if(!$result){
            throw new ResourceException('显示点评失败', $validator->errors());
        }
        return $this->result($this::OK, "成功",$result);
    }


    /**
     * 获取拉流地址
     * @param Request $request
     * @return array
     */
    public function getPullAddress(Request $request)
    {

        $rules = [
            'problem_id'   => ['required'],
        ];

        $payload = app('request')->only('problem_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('获取拉流地址有误', $validator->errors());
        }

        $pro = Problems::select('cid')->where('id',$request->problem_id)->first();
        $cid = $pro->cid;

        $url="https://vcloud.163.com/app/address";
        $data=json_decode($this->postDataCurl($url,array("cid"=>$cid)));

        if($data->code != 200){
            throw new ResourceException('获取拉流地址失败', $validator->errors());
        }

        $result = [];
        $result['httpPullUrl'] = $data->ret->httpPullUrl;
        $result['hlsPullUrl'] = $data->ret->hlsPullUrl;
        $result['rtmpPullUrl'] = $data->ret->rtmpPullUrl;
        return $this->result($this::OK,"成功", $result);
    }

    /**
     * 获取推流地址
     * @param Request $request
     * @return array
     */
    public function getPushAddress(Request $request)
    {
        $rules = [
            'problem_id'   => ['required'],
        ];

        $payload = app('request')->only('problem_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('获取推流地址有误', $validator->errors());
        }

        $les = Problems::select('cid')->where('id',$request->problem_id)->first();
        $cid = $les->cid;

        $url="https://vcloud.163.com/app/address";
        $data=json_decode($this->postDataCurl($url,array("cid"=>$cid)));

        if($data->code != 200){
            throw new ResourceException('获取推流地址失败', $validator->errors());
        }

        $result = [];
        $result['pushUrl'] = $data->ret->pushUrl;
        return $this->result($this::OK,"成功", $result);
    }

    /**
     * 人员管理搜索
     * @param Request $request
     * @return array
     */
    public function section(Request $request)
    {
        $rules = [

        ];

        $payload = app('request')->only('');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('查询失败', $validator->errors());
        }
        //查询
        $section = Section::select('users.*','sections.section_one','sections.section_two')->join('users','users.section_id','sections.id');

        if(!empty($request->section_one)){
            $section = Section::select('users.*','sections.section_one','sections.section_two')
                ->join('users','users.section_id','sections.id')
                ->where('section_one',$request->section_one);
        }

        if(!empty($request->section_two)){
            $section = Section::select('users.*','sections.section_one','sections.section_two')
                ->join('users','users.section_id','sections.id')
                ->where('section_two',$request->section_two);
        }

        if(!empty($request->section_one) && !empty($request->section_two)){
            $section = Section::select('users.*','sections.section_one','sections.section_two')
                ->join('users','users.section_id','sections.id')
                ->where('section_one',$request->section_one)
                ->where('section_two',$request->section_two);
        }

        if(!empty($request->name)){
            $section = Section::select('users.*','sections.section_one','sections.section_two')
                ->join('users','users.section_id','sections.id')
                ->where('users.name','like','%'.$request->name.'%');
        }

        if(!empty($request->name) && !empty($request->section_one)){
            $section = Section::select('users.*','sections.section_one','sections.section_two')
                ->join('users','users.section_id','sections.id')
                ->where('users.name',$request->name)
                ->where('section_one',$request->section_one);
        }

        if(!empty($request->name) && !empty($request->section_two)){
            $section = Section::select('users.*','sections.section_one','sections.section_two')
                ->join('users','users.section_id','sections.id')
                ->where('users.name',$request->name)
                ->where('section_two',$request->section_two);
        }

        $section = $section->orderBy('sections.section_one')->DISTINCT()->Paginate($request->number);

        if(count($section) > 0){
            return $this->result($this::OK, "成功", $section);
        } elseif(count($section) == 0){
            return $this->result($this::OK, "暂无数据", null);
        } else {
            throw new ResourceException('失败', $this::FAILED);
        }

    }

    /**
     * 查看分组名
     * @param Request $request
     * @return array
     */
    public function groupStart(Request $request)
    {
        $rules = [
            'design_id' => ['required'],
        ];

        $payload = app('request')->only('design_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('显示失败', $validator->errors());
        }

        $group = Groups::select()->where('design_id',$request->design_id)->get();

        if(count($group) > 0){
            return $this->result($this::OK, "成功", $group);
        } elseif (count($group) == 0){
            return $this->result($this::OK, "暂无数据", null);
        } else {
            throw new ResourceException('显示分组失败', $validator->errors());
        }
    }

    /**
     * 生成文档数据
     * @param Request $request
     * @return array
     */
    public function createdWord(Request $request)
    {
        $rules = [
            'design_id' => ['required'],
        ];

        $payload = app('request')->only('design_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('查询失败', $validator->errors());
        }

        //演练项目名称
        $design = Designs::select()->where('id',$request->design_id)->get();

        //演练项目中的环节
        foreach ($design as $node){
            $nodes = $node->nodes;
            //演练项目中的分组
            $groups = $node->group;
            //分组中的组员
            foreach($groups as $user){
                $users = $user->user;
                //组员所在的部门
                foreach($users as $section){
                    $sections = $section->section;
                }
            }
            //环节下的题目和 回答的答案
            foreach ($nodes as $problem){
                $problems = $problem->problem->answers;
                //回答问题的人
                foreach($problems as $user){
                    $users = $user->user->section;
                }
                //对各个答案进行点评
                foreach($problems as $answer){
                    $scores = $answer->score;
                }
            }
        }
        if(count($design) > 0){
            return $this->result(BaseController::OK,'成功',$design);
        } else {
            throw new ResourceException('查询失败', $validator->errors());
        }
    }

    /**
     * 更改参考答案是否显示
     * @param Request $request
     * @return array
     */
    public function problemStatus(Request $request)
    {
        $rules = [
            'problem_id' => ['required'],
        ];

        $payload = app('request')->only('problem_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new UpdateResourceFailedException('失败', $validator->errors());
        }

        $status = Problems::find($request->problem_id);
        if(!empty($request->status)){
            $status->status = $request->status;
            $result = $status->save();
            if(!$result){
                throw new UpdateResourceFailedException('失败', $validator->errors());
            }
            return $this->result($this::OK, "成功", null);
        }

        return $this->result($this::OK, "成功", $status);
    }

    /**
     * 服务器时间
     * @return array
     */
    public function timeEnd()
    {
        $date_time = date('Y-m-d H:i:s');
        return $this->result('OK','成功',$date_time);
    }

    public function designName(Request $request)
    {
        $rules = [
            'design_id' => ['required'],
        ];

        $payload = app('request')->only('design_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('显示失败', $validator->errors());
        }

        $design_name = Designs::select('name')->where('id',$request->design_id)->first();

        if(is_null($design_name)){
            throw new ResourceException('失败', $validator->errors());
        }
        return $this->result('OK','成功',$design_name);
    }

}
