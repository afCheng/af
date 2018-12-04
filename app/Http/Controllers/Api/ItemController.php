<?php

namespace App\Http\Controllers\Api;

use App\Model\Apply;
use App\Model\Frid;
use App\Model\Item;
use App\Model\Manifest;
use App\Model\Receive;
use App\Model\Restore;
use App\Model\Sort;
use Dingo\Api\Exception\DeleteResourceFailedException;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\UpdateResourceFailedException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ItemController extends BaseController
{
    /**
     * 电子标签编辑
     * @param Request $request
     * @return array
     */
    public function fridEdit(Request $request)
    {
        $rules = [
            'frid_number' => ['required'],
        ];

        $payload = app('request')->only('frid_number');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new StoreResourceFailedException('失败', $validator->errors());
        }

        $frid_number = Frid::select()->where('frid_number',$request->frid_number)->get();
        if(count($frid_number) > 0){
            return $this->result('标签已存在',null);
        }

        $frid_add = new Frid();
        $frid_add->frid_number = $request->frid_number;
        $result = $frid_add->save();

        if(!$result){
            throw new StoreResourceFailedException('添加电子标签失败', $validator->errors());
        }

        return $this->result('添加成功',$frid_add);
    }

    /**
     * 电子标签列表
     * @param Request $request
     * @return array
     */
    public function fridList(Request $request)
    {
        $rules = [

        ];

        $payload = app('request')->only('');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('失败', $validator->errors());
        }

        $frid_list = Frid::all();
        if(count($frid_list) > 0){
            return $this->result('成功',$frid_list);
        }
        elseif(count($frid_list) == 0){
            return $this->result('无数据',null);
        }
        else{
            throw new ResourceException('查询失败', $validator->errors());
        }

    }

    /**
     * 电子标签删除
     * @param Request $request
     * @return array
     */
    public function fridDel(Request $request)
    {
        $rules = [
            'frid_id' => ['required'],
        ];

        $payload = app('request')->only('frid_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new DeleteResourceFailedException('失败', $validator->errors());
        }

        $frid_info = Item::select()->where('frid_id',$request->frid_id)->first();
        if(!is_null($frid_info)){
            throw new DeleteResourceFailedException('请先删除电子标签相对应的物品', $validator->errors());
        }

        $frid_del = Frid::find($request->frid_id);
        $result = $frid_del->delete();
        if(!$result){
            throw new DeleteResourceFailedException('删除失败', $validator->errors());
        }
        return $this->result('成功',null);
    }

    /**
     * 物品编辑
     * @param Request $request
     * @return array
     */
    public function itemEdit(Request $request)
    {
        $rules = [
            'frid_id' => ['required'],
            'item_name' => ['required'],
            'type' => ['required'],
            'stock' => ['required'],
            'sort_id' => ['required'],
//            'situation' => ['required'],
            'unit' => ['required'],
        ];

        $payload = app('request')->only('frid_id','item_name','type','stock','sort_id','unit');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new StoreResourceFailedException('失败', $validator->errors());
        }


        if(!empty($request->file('file'))){
            $file = $request->file('file');
            // 文件是否上传成功
            if (!$file->isValid()) {
                throw new StoreResourceFailedException('附件添加失败', $validator->errors());
            }

            // 获取文件相关信息
            $ext = $file->getClientOriginalExtension();     // 扩展名
            //文件格式
            $fileTypes = ['gif','jpeg','jpg','bmp','png','webp','psd','svg','tiff'];
            $isInFileType = in_array($ext, $fileTypes);
            //文件格式是否成功
            if (!$isInFileType) {
                throw new StoreResourceFailedException('附件添加格式错误', $validator->errors());
            }
            // 上传文件
            $filename = date('Ymd') . uniqid() . '.' . $ext;
            //路径
            $path = $file->storeAs('public', $filename);

            if(empty($request->item_id)){
                $item_add = new Item();
                $item_add->frid_id = $request->frid_id;
                $item_add->photo = $path;
                $item_add->item_name = $request->item_name;
                if(!empty($request->introduce)) {
                    $item_add->introduce = $request->introduce;
                }
                else{
                    $item_add->introduce = null;
                }
                $item_add->type = $request->type;
                $item_add->status = 0;
                $item_add->stock = $request->stock;
                $item_add->unit = $request->unit;
                $item_add->sort_id = $request->sort_id;
                $item_add->situation = null;
                $item_add->is_putaway = 1;
                $item_add->dangerous = $request->dangerous;
                $item_add->overdue_at = $request->overdue_at;
                $result = $item_add->save();
                if(!$result){
                    throw new StoreResourceFailedException('添加失败', $validator->errors());
                }
                return $this->result('添加成功',null);
            }
            else {
                $item_save = Item::find($request->item_id);
                $item_save->frid_id = $request->frid_id;
                $item_save->photo = $path;
                $item_save->item_name = $request->item_name;
                if(!empty($request->introduce)) {
                    $item_save->introduce = $request->introduce;
                }
                else{
                    $item_save->introduce = null;
                }
                $item_save->type = $request->type;
                $item_save->status = $request->status;
                $item_save->stock = $request->stock;
                $item_save->unit = $request->unit;
                $item_save->sort_id = $request->sort_id;
                $item_save->is_putaway = $request->is_putaway;
                $item_save->dangerous = $request->dangerous;
                $item_save->overdue_at = $request->overdue_at;
                $result = $item_save->save();
                if(!$result){
                    throw new StoreResourceFailedException('修改失败', $validator->errors());
                }

                if(!empty($request->situation)){
                    $item_up = DB::update('update items set situation = GeomFromText("'.$request->situation.'"),s_content = "'.$request->s_content.'" where id = '.$request->item_id.'');
                    if(!$item_up){
                        throw new StoreResourceFailedException('修改位置失败', $validator->errors());
                    }
                }

                return $this->result('修改成功',null);
            }
        }
        else {
            if(empty($request->item_id)){
                $item_add = new Item();
                $item_add->frid_id = $request->frid_id;
                $item_add->photo = null;
                $item_add->item_name = $request->item_name;
                if(!empty($request->introduce)) {
                    $item_add->introduce = $request->introduce;
                }
                else{
                    $item_add->introduce = null;
                }
                $item_add->type = $request->type;
                $item_add->status = 0;
                $item_add->stock = $request->stock;
                $item_add->unit = $request->unit;
                $item_add->sort_id = $request->sort_id;
                $item_add->situation = null;
                $item_add->is_putaway = 1;
                $item_add->dangerous = $request->dangerous;
                $item_add->overdue_at = $request->overdue_at;
                $result = $item_add->save();
                if(!$result){
                    throw new StoreResourceFailedException('添加失败', $validator->errors());
                }
                return $this->result('添加成功',null);
            }
            else {
                $item_save = Item::find($request->item_id);
                $item_save->frid_id = $request->frid_id;
                $item_save->item_name = $request->item_name;
                if(!empty($request->introduce)) {
                    $item_save->introduce = $request->introduce;
                }
                else{
                    $item_save->introduce = null;
                }
                $item_save->type = $request->type;
                $item_save->status = $request->status;
                $item_save->stock = $request->stock;
                $item_save->unit = $request->unit;
                $item_save->sort_id = $request->sort_id;
                $item_save->is_putaway = $request->is_putaway;
                $item_save->dangerous = $request->dangerous;
                $item_save->overdue_at = $request->overdue_at;
                $result = $item_save->save();
                if(!$result){
                    throw new StoreResourceFailedException('修改失败', $validator->errors());
                }

                if(!empty($request->situation)){
                    $item_up = DB::update('update items set situation = GeomFromText("'.$request->situation.'"),s_content = "'.$request->s_content.'" where id = '.$request->item_id.'');
                    if(!$item_up){
                        throw new StoreResourceFailedException('修改位置失败', $validator->errors());
                    }
                }

                return $this->result('修改成功',null);
            }
        }
    }

    /**
     * 批量上架
     * @param Request $request
     * @return array
     */
    public function itemIs(Request $request){
        $rules = [
            'item' => ['required'],
        ];

        $payload = app('request')->only('item');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new UpdateResourceFailedException('失败', $validator->errors());
        }

        $item = $request->item;
        foreach($item as $value){

            $item_up = DB::update('update items set situation = GeomFromText("'.$value['situation'].'"),s_content = "'.$value['s_content'].'" where id = '.$value['item_id'].'');
            if(!$item_up){
                continue;
//                throw new StoreResourceFailedException('添加位置失败', $validator->errors());
            }

            $item_save = Item::find($value['item_id']);
            $item_save->is_putaway = 0;
            $result = $item_save->save();
            if(!$result){
                continue;
            }
        }
        return $this->result('成功',null);

    }

    /**
     * 物品列表
     * @param Request $request
     * @return array
     */
    public function itemList(Request $request)
    {
        $rules = [
            'item_type' => ['required'],
            'number' => ['required'],
        ];

        $payload = app('request')->only('number','item_type');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('失败', $validator->errors());
        }

        $item = DB::table('items')
                ->selectRaw('AsText(situation) as situation,items.id as item_id,`frid_id`,frids.frid_number,`item_name`,`introduce`,`stock`,`unit`,`status`,`type`,`sort_id`,sorts.name as sort_name,`is_putaway`,`dangerous`,`overdue_at`,`photo`')
                ->join('frids','frids.id','items.frid_id')
                ->join('sorts','sorts.id','items.sort_id')
                ->where('items.type',$request->item_type)
                ->where('items.is_putaway',1)
                ->where('items.item_name','like','%'.$request->item_name.'%')
                ->DISTINCT()
                ->paginate($request->number);

        if(count($item) > 0){
            return $this->result('成功',$item);
        }
        elseif(count($item) == 0){
            return $this->result('无数据',null);
        }
        else {
            throw new ResourceException('查询失败', $validator->errors());
        }
    }

    /**
     * 库存列表
     * @param Request $request
     * @return array
     */
    public function List(Request $request)
    {
        $rules = [
            'item_type' => ['required'],
            'sort_id' => ['required'],
            'number' => ['required'],
        ];

        $payload = app('request')->only('number','item_type','sort_id');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('失败', $validator->errors());
        }

        $item = DB::table('items')
            ->selectRaw('AsText(situation) as situation,items.s_content,items.id as item_id,`frid_id`,frids.frid_number,`item_name`,`introduce`,`stock`,`unit`,`status`,`type`,`sort_id`,sorts.name as sort_name,`is_putaway`,`dangerous`,`overdue_at`,`photo`,items.created_at')
            ->join('frids','frids.id','items.frid_id')
            ->join('sorts','sorts.id','items.sort_id')
            ->where('items.type',$request->item_type)
            ->where('items.is_putaway',0)
            ->where('sorts.id',$request->sort_id);
            if($request->status == 1){
                $item = $item->where('status',1);
            }
            if($request->status == 0){
                $item = $item->where('status','<>',1);
            }
        $item = $item->DISTINCT()->paginate($request->number);

        if(count($item) > 0){
            return $this->result('成功',$item);
        }
        elseif(count($item) == 0){
            return $this->result('无数据',null);
        }
        else {
            throw new ResourceException('查询失败', $validator->errors());
        }
    }

    /**
     * 物品上架库存
     * @param Request $request
     * @return array
     */
    public function bigList(Request $request)
    {
        $rules = [
            'item_type' => ['required'],
            'number' => ['required'],
        ];

        $payload = app('request')->only('number','item_type');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('失败', $validator->errors());
        }

        $item_list = DB::table('items')
            ->selectRaw('AsText(situation) as situation,item_name,sorts.name as sort_name,SUM(stock) as stock,unit,type,introduce')
            ->join('sorts','sorts.id','items.sort_id')
            ->where('is_putaway',0);
            if(!empty($request->item_type)){
                $item_list = $item_list->where('type',$request->item_type);
            }
            if(!empty($request->item_name)){
                $item_list = $item_list->where('item_name','like','%'.$request->item_name.'%');
            }

        $item_list = $item_list->groupBy('item_name')->paginate($request->number);

        if(count($item_list) > 0){
            return $this->result('成功',$item_list);
        }
        elseif(count($item_list) == 0){
            return $this->result('无数据',null);
        }
        else {
            throw new ResourceException('查询失败', $validator->errors());
        }
    }

    /**
     * 大类库存
     * @param Request $request
     * @return array
     */
    public function sortList(Request $request)
    {
        $rules = [
            'item_type' => ['required'],
            'number' => ['required'],
        ];

        $payload = app('request')->only('number','item_type');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('失败', $validator->errors());
        }

        $item_list = DB::table('items')
            ->selectRaw('sorts.id,sorts.name as sort_name,COUNT(sort_id) as stock,unit,type')
            ->join('sorts','sorts.id','items.sort_id')
            ->where('is_putaway',0);
        if(!empty($request->item_type)){
            $item_list = $item_list->where('type',$request->item_type);
        }
        if(!empty($request->sort_name)){
            $item_list = $item_list->where('sorts.name','like','%'.$request->sort_name.'%');
        }

        $item_list = $item_list->groupBy('sorts.id')->paginate($request->number);

        if(count($item_list) > 0){
            return $this->result('成功',$item_list);
        }
        elseif(count($item_list) == 0){
            return $this->result('无数据',null);
        }
        else {
            throw new ResourceException('查询失败', $validator->errors());
        }
    }

    public function sortItem(Request $request)
    {
        $rules = [
            'number' => ['required'],
        ];

        $payload = app('request')->only('number');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('失败', $validator->errors());
        }

        $item_list = Item::
            selectRaw('sorts.id,sorts.name as sort_name,COUNT(sort_id) as stock,unit,type')
            ->join('sorts','sorts.id','items.sort_id')
            ->where('is_putaway',0);
        if(!empty($request->item_type)){
            $item_list = $item_list->where('type',$request->item_type);
        }
        if(!empty($request->sort_name)){
            $item_list = $item_list->where('sorts.name','like','%'.$request->sort_name.'%');
        }

        $item_list = $item_list->groupBy('sorts.id')->paginate($request->number);
        foreach ($item_list as $value){
            $list = Item::select('item_name')->where('sort_id', $value['id'])->DISTINCT()->get()->toArray();

            $value['list'] = $list;

        }

        if(count($item_list) > 0){
            return $this->result('成功',$item_list);
        }
        elseif(count($item_list) == 0){
            return $this->result('无数据',null);
        }
        else {
            throw new ResourceException('查询失败', $validator->errors());
        }
    }

    /**
     * 物品删除
     * @param Request $request
     * @return array
     */
    public function itemDel(Request $request)
    {
        $rules = [
            'item' => ['required'],
        ];

        $payload = app('request')->only('item');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new DeleteResourceFailedException('失败', $validator->errors());
        }

        $item = $request->item;
        foreach ($item as $value){
            $item_receive = Receive::select()->where('item_id',$value['item_id'])->get();
            if(count($item_receive) > 0){
//                continue;
                throw new DeleteResourceFailedException('物品有相对应的领用记录关联', $validator->errors());
            }

            $item_restore = Restore::select()->where('item_id',$value['item_id'])->get();
            if(count($item_restore) > 0){
//                continue;
                throw new DeleteResourceFailedException('物品有相对应的归还记录关联', $validator->errors());
            }

            $item_del = Item::find($value['item_id']);
            $result = $item_del->delete();
            if(!$result){
//                continue;
                throw new DeleteResourceFailedException('删除失败', $validator->errors());
            }
        }

        return $this->result('删除成功',null);
    }

    /**
     * 快速查询(平板)
     * @param Request $request
     * @return array
     */
    public function itemSelect(Request $request)
    {
        $rules = [
            'number' => ['required'],
            'page' => ['required'],
        ];

        $payload = app('request')->only('number','page');

        $validator = app('validator')->make($payload, $rules);

        if ($validator->fails()) {
            throw new ResourceException('失败', $validator->errors());
        }

        $item_select = DB::table('items')
            ->selectRaw('AsText(situation) as situation,items.s_content,items.id,items.item_name,items.status,items.stock,items.unit,sorts.name as sort_name,items.photo,items.introduce')
            ->join('sorts','sorts.id','items.sort_id')
            ->where('items.is_putaway',0);
            if(!empty($request->type)){
                $item_select =  $item_select->where('items.type',$request->type);
            }
            if(!empty($request->item_name)){
                $item_select =  $item_select->where('item_name','like','%'.$request->item_name.'%');
            }
            if(!empty($request->sort_name)){
                $item_select =  $item_select->where('sort_name','like','%'.$request->sort_name.'%');
            }

        $item_select = $item_select->paginate($request->number);


        if(count($item_select) > 0){
            return $this->result('成功',$item_select);
        }
        elseif(count($item_select) == 0){
            $data['data'] = [];
            return $this->result('无数据',$data);
        }
        else{
            throw new ResourceException('查询失败', $validator->errors());
        }
    }

}
