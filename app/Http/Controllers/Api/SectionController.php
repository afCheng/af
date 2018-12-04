<?php

namespace App\Http\Controllers\Api;

use App\Model\Section;
use App\User;
use Dingo\Api\Exception\DeleteResourceFailedException;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\UpdateResourceFailedException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class SectionController extends BaseController
{
    /**
     * 部门列表
     * @param Request $request
     * @return array
     */
    public function sectionList(Request $request)
    {
        $rules = [

        ];

        $payload = app('request')->only('');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('查询部门列表失败', $validator->errors());
        }

        $section_list = Section::select();

        if(!empty($request->section_one)){
            $section_list = Section::select('id','section_one','section_two')->where('section_one',$request->section_one);
        }

        if(!empty($request->section_two)){
            $section_list =Section::select('id','section_one','section_two')->where('section_two',$request->section_two);
        }

        if(!empty($request->id)){
            $section_list =Section::select('id','section_one','section_two')->where('id',$request->id);
        }

        $section_list = $section_list->Paginate($request->number);

        if(count($section_list) > 0){
            return $this->result(BaseController::OK,'成功',$section_list);
        } else {
            throw new ResourceException('查询部门列表失败', $validator->errors());
        }
    }

    /**
     * 部门添加
     * @param Request $request
     * @return array
     */
    public function sectionAdd(Request $request)
    {
        $rules = [
            'section_one' => ['required'],
            'section_two' => ['required'],
        ];

        $payload = app('request')->only('section_one','section_two');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('添加部门列表失败', $validator->errors());
        }

        $section_add = new Section();
        $section_add->section_one = $request->section_one;
        $section_add->section_two = $request->section_two;
        $result = $section_add->save();

        if(!$result){
            throw new ResourceException('添加部门列表失败', $validator->errors());
        }

        return $this->result(BaseController::OK,'成功',null);

    }

    /**
     * 部门编辑
     * @param Request $request
     * @return array
     */
    public function sectionSave(Request $request)
    {
        $rules = [
            'id' => ['required'],
            'section_one' => ['required'],
            'section_two' => ['required'],
        ];

        $payload = app('request')->only('id','section_one','section_two');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new UpdateResourceFailedException('编辑部门列表失败', $validator->errors());
        }

        $section_add = Section::find($request->id);
        $section_add->section_one = $request->section_one;
        $section_add->section_two = $request->section_two;
        $result = $section_add->save();

        if(!$result){
            throw new UpdateResourceFailedException('编辑部门列表失败', $validator->errors());
        }

        return $this->result(BaseController::OK,'成功',null);

    }

    /**
     * 部门删除
     * @param Request $request
     * @return array
     */
    public function sectionDel(Request $request)
    {
        $rules = [
            'id' => ['required'],
        ];

        $payload = app('request')->only('id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new DeleteResourceFailedException('删除部门列表失败', $validator->errors());
        }

        $user_section = User::select()->where('section_id',$request->id)->get();
        if(count($user_section) > 0){
            throw new ResourceException('无法删除，部门下有相关人员', $validator->errors());
        }

        $section_add = Section::find($request->id);
        $result = $section_add->delete();

        if(!$result){
            throw new DeleteResourceFailedException('删除部门列表失败', $validator->errors());
        }

        return $this->result(BaseController::OK,'成功',null);

    }

    /**
     * 部门数据导入
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
                $select = Section::select()->where('section_two',$val['section_two'])->get();
                if(count($select) == 0){
                    $section = new Section();
                    $section->section_one = $val['section_one'];
                    $section->section_two = $val['section_two'];
                    $section->save();
                }
            }
            return $this->result(BaseController::OK,'导入成功',null);
        } catch (\Exception $exception) {
            throw new StoreResourceFailedException('导入失败', $validator->errors());
        }

    }
}
