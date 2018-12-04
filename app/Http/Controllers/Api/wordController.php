<?php

namespace App\Http\Controllers\Api;

use App\Model\Designs;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\Style\TOC;

class wordController extends BaseController
{
    public function word(Request $request)
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

        $phpWord = new PhpWord();
        //设置默认样式
        $phpWord->setDefaultFontName('仿宋');//字体
        $phpWord->setDefaultFontSize(16);//字号

        //添加页面
        $section = $phpWord->addSection();

        //添加目录
        $styleTOC  = ['tabLeader' => TOC::TAB_LEADER_DOT];
        $styleFont = ['spaceAfter' => 60, 'name' => 'Tahoma', 'size' => 12];
        $section->addTOC($styleFont, $styleTOC);

        $section->addText('演练名称:'.$design[0]['name']);
        $section->addTextBreak();//换行符
        $section->addText('演练详情:'.$design[0]['description']);
        $section->addTextBreak();//换行符
        $section->addText('演练时间:'.$design[0]['design_time']);
        $section->addTextBreak();//换行符
        foreach($design[0]['nodes'] as $value){
            $section->addText('环节名称:'.$value['name']);
            $section->addText('题目:'.$value['problem']['describe']);
            $section->addText('参考答案:'.$value['problem']['answer']);
            $section->addText('分值:'.$value['problem']['score']);
            $section->addText('限时:'.$value['problem']['problem_time'].'分钟');
            $section->addTextBreak();//换行符
            foreach($value['problem']['answers'] as $v){
                $section->addTextBreak();//换行符
                $section->addText('回答人:'.$v['user']['name'].' '.$v['user']['section']['section_one'].' '.$v['user']['section']['section_two']);
                $section->addText('答案:'.$v['answer']);
                $section->addText('得分:'.$v['score']['score']);
                $section->addTextBreak();//换行符
            }
        }

        foreach($design[0]['group'] as $v){
            $section->addTextBreak();//换行符
            $section->addText('组名:'.$v['name']);
            $section->addTextBreak();//换行符
            $section->addText('组员:');
            foreach ($v['user'] as $value){
                $section->addText($value['name'].' '.$value['section']['section_one'].' '.$value['section']['section_two']);
            }
        }

        //生成的文档为Word2007
        $writer = IOFactory::createWriter($phpWord, 'Word2007');

        try{
            $writer->save(storage_path('app/word/'.$design[0]['name'].'.docx'));
            return $this->result($this::OK, "成功",null);
        } catch (\Exception $e) {
            return $this->result($this::ERROR, "失败",null);
        }
    }

    public function download(Request $request)
    {
        $design_name = Designs::select()->where('id',$request->design_id)->first();
        if(is_null($design_name)){
            return $this->result($this::ERROR, "下载失败",null);
        }
//        $file = storage_path().'/app/word/'.$design_name['name'];
        $file_name = $design_name['name'].'.docx';
        $headers = array(
            'Content-Type: application/docx',
        );
        return response()->download(realpath(storage_path('/app/word')).'/'.$file_name,
            $file_name,$headers);
    }
}
