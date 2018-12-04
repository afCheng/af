<?php

namespace App\Http\Controllers\Api;

use App\Model\Database;
use App\Model\DataType;
use Dingo\Api\Exception\DeleteResourceFailedException;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DataBaseController extends BaseController
{

    /**
     * 上传资料库
     * @param Request $request
     * @return array
     */
    public function dataBase(Request $request)
    {
        $rules = [
            'description' => ['required'],
            'type_id' => ['required'],
        ];

        $payload = app('request')->only('description','type_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new StoreResourceFailedException('附件添加失败', $validator->errors());
        }

        $file = $request->file('file');
        //dd($file);
        // 文件是否上传成功
        if (!$file->isValid()) {
            throw new StoreResourceFailedException('附件添加失败', $validator->errors());
        }
        // 获取文件相关信息
        $ext = $file->getClientOriginalExtension();     // 扩展名
        //文件格式
        $fileTypes = ['docx','pdf'];
        $isInFileType = in_array($ext,$fileTypes);
        //文件格式是否成功
        if (!$isInFileType){
            throw new StoreResourceFailedException('附件添加格式错误', $validator->errors());
        }
        // 上传文件
        $filename = date('Ymd').uniqid().'.'.$ext;
        //路径
        $path = $request->file('file')->storeAs('public',$filename);
        $attachments_add = new Database();
        $attachments_add->title = $request->title;
        $attachments_add->original_file = $filename;
        $attachments_add->file_path = $path;
        $attachments_add->type_id = $request->type_id;
        $attachments_add->description = $request->description;
        $result = $attachments_add->save();
        if (!$result){
            throw new StoreResourceFailedException('附件添加失败', $validator->errors());
        }
        return $this->result(BaseController::OK,'成功',null);
    }

    /**
     * 删除
     * @param Request $request
     * @return array
     */
    public function dataBaseDel(Request $request)
    {
        $rules = [
            'id' => ['required'],
        ];

        $payload = app('request')->only('id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new DeleteResourceFailedException('删除失败', $validator->errors());
        }

        $del = Database::find($request->id);
        $filehash = $del->file_path;
        Storage::delete($filehash);
        $result = $del->delete();
        if(!$result){
            throw new DeleteResourceFailedException('删除资料失败', $validator->errors());
        }

        return $this->result(BaseController::OK,'删除成功',null);
    }

    /**
     * 资料库列表
     * @param Request $request
     * @return array
     */
    public function dataBaseList(Request $request)
    {
        $rules = [

        ];

        $payload = app('request')->only('');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('查询资料库列表失败', $validator->errors());
        }

        $database_list = DataType::select()->with('database');
        if(!empty($request->id)){
            $database_list = Database::select('data_types.name as type_name','databases.*')->join('data_types','data_types.id','databases.type_id')->where('databases.id',$request->id)->get();
            if(count($database_list) == 1){
                return $this->result($this::OK, "成功",$database_list);
            } else {
                throw new ResourceException('查询资料库失败', $validator->errors());
            }
        }
        $database_list = $database_list->Paginate($request->number);

        if(count($database_list) > 0){
            return $this->result($this::OK, "成功",$database_list);
        } elseif(count($database_list) == 0) {
            return $this->result($this::OK, "暂无数据",null);
        } else {
            throw new ResourceException('查询资料库列表失败', $validator->errors());
        }
    }

    /**
     * 添加类型
     * @param Request $request
     * @return array
     */
    public function DataTypeAdd(Request $request)
    {
        $rules = [
            'name' => ['required'],
        ];

        $payload = app('request')->only('name');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new StoreResourceFailedException('类型添加失败', $validator->errors());
        }

        $type_add = new DataType();
        $type_add->name = $request->name;
        $result = $type_add->save();

        if(!$result){
            throw new StoreResourceFailedException('类型添加失败', $validator->errors());
        }
        return $this->result($this::OK, "成功",null);
    }

    /**
     * 类型列表
     * @return array
     */
    public function typeList()
    {
        $rules = [

        ];

        $payload = app('request')->only('');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('查询失败', $validator->errors());
        }

        $type_list = DataType::select()->get();

        if(count($type_list) > 0){
            return $this->result($this::OK, "成功",$type_list);
        } elseif(count($type_list) == 0) {
            return $this->result($this::OK, "暂无数据",null);
        } else {
            throw new ResourceException('查询类型失败', $validator->errors());
        }
    }
}
