<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

$api = app('Dingo\Api\Routing\Router');
$api->version('v1',function($api) {
    $api->group(['namespace' => 'App\Http\Controllers\Api'], function ($api) {
        $api->post('login','LoginController@login');
        
        // $api->group(['middleware' => 'api.auth'],function ($api){
            $api->get('loginout','LoginController@loginout');
            $api->post('item-list','ItemController@itemList');//物品列表
            $api->post('list','ItemController@List');//小类库存
            $api->post('big-list','ItemController@bigList');//物品上架库存
            $api->post('sort-big','ItemController@sortList');//大类库存
            $api->post('item-edit','ItemController@itemEdit');//物品编辑
            $api->post('item-del','ItemController@itemDel');//物品删除
            $api->post('item-is','ItemController@itemIs');//物品批量上架
            $api->post('sort-item','ItemController@sortItem');


            $api->post('frid-list','ItemController@fridList');//电子标签列表
            $api->post('frid-edit','ItemController@fridEdit');//电子标签编辑
            $api->post('frid-del','ItemController@fridDel');//电子标签删除

            $api->post('sort-list','SortController@sortList');//分类列表
            $api->post('sort-edit','SortController@sortEdit');//分类编辑
            $api->post('sort-del','SortController@sortDel');//分类删除

            $api->post('user-list','UserController@userList');//申领人列表
            $api->post('user-edit','UserController@userEdit');//申领人编辑
            $api->post('user-del','UserController@userDel');//申领人删除

            $api->post('manifest-list','ApplyController@manifestList');//清单列表
            $api->post('manifest-edit','ApplyController@manifestEdit');//清单编辑
            $api->post('apply-list','ApplyController@applyList');//申请物品列表
            $api->post('apply-edit','ApplyController@applyEdit');//申请物品编辑
            $api->post('apply-del','ApplyController@applyDel');//申请物品删除

            $api->post('receive-list','ReceiveController@receiveList');//领用记录列表
            $api->post('receive-edit','ReceiveController@receiveEdit');//领用记录编辑
            $api->post('receive-del','ReceiveController@receiveDel');//领用记录删除
            $api->post('receive-sel','ReceiveController@receiveSelect');//领用记录查询
            $api->post('frid-sel','ReceiveController@fridList');//扫描标签

            $api->post('restore-list','RestoreController@restoreList');//归还记录列表
            $api->post('restore-edit','RestoreController@restoreEdit');//归还记录编辑
            $api->post('restore-del','RestoreController@restoreDel');//归还记录删除
            $api->post('restore-sel','RestoreController@restoreSelect');//归还记录查询
            $api->post('receive','RestoreController@userReceive');//未归还列表

            $api->post('admin-edit','AdminController@adminEdit');//管理员编辑
            $api->post('admin-sel','AdminController@adminSelect');//管理员查询
            $api->post('admin-list','AdminController@adminList');//管理员列表
            $api->post('admin-del','AdminController@adminDel');//管理员删除
        // });

        $api->post('user-manifest','UserController@userManifest');//申领人未申领(清单)
        $api->post('user-apply','UserController@userApply');//申领人未申领(大类)
        $api->post('user-item','UserController@userItem');//申领人未申领(小类)

        $api->post('user-receive','UserController@userReceive');//申领未归还

        $api->post('user-restore','UserController@userRestore');//申领历史
        $api->post('manifest-restore','UserController@manifestRestore');//申领历史(清单)

        $api->post('item-select','ItemController@itemSelect');//物品快速查询
    });
});