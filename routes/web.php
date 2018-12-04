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
        $api->group(['middleware' => 'api.auth'], function ($api) {

        });
        /*登录*/
        $api->post('login','LoginController@login');
        /*获取token*/
        $api->post('jwt-token','LoginController@apiRefreshToken');
        /*退出登陆*/
        $api->post('login-out','LoginController@loginOut');
        /*修改密码*/
        $api->post('password-save','LoginController@passwordSave');
        /*我的部门*/
        $api->post('my-section','LoginController@mySection');

        /*部门搜索*/
        $api->post('section','DesignController@section');

        /*可参演的演练项目*/
        $api->post('design-my','DesignController@designMy');
        /*我参演的演练项目*/
        $api->post('design-me','DesignController@designMe');
        /*历史的演练项目*/
        $api->post('design-history','DesignController@designHistory');
        /*我的历史演练项目*/
        $api->post('design-history-my','DesignController@designHistoryMy');
        /*我导演的演练项目*/
        $api->post('director-my','DesignController@directorMy');
        /*我观看的演练项目*/
        $api->post('my-look','DesignController@look');

        /*查看自己本次身份*/
        $api->post('my-character','UserController@characterMy');

        $api->post('design-name','DesignController@designName');
        /*开始演练*/
        $api->post('start','DesignController@start');
        /*结束演练*/
        $api->post('end','DesignController@end');
        /*查看演练状态进程*/
        $api->post('look-status','DesignController@lookStatus');
        /*存储演练状态进程*/
        $api->post('save-design','DesignController@saveDesign');
        /*查看组名*/
        $api->post('group-start','DesignController@groupStart');

        /*
         * 超级管理员
         * */

        /*资料库列表*/
        $api->post('database-list','DataBaseController@dataBaseList');
        /*资料库添加资料*/
        $api->post('database','DataBaseController@dataBase');
        /*资料库删除资料*/
        $api->post('database-del','DataBaseController@dataBaseDel');
        /*添加资料类型*/
        $api->post('data-type','DataBaseController@DataTypeAdd');
        /*类型列表*/
        $api->post('type-list','DataBaseController@typeList');

        /*人员列表*/
        $api->post('user-list','UserController@userList');

        $api->post('people-section','UserController@peopleSection');

        /*添加人员账号*/
        $api->post('user-add','UserController@userAdd');
        /*编辑人员账号*/
        $api->post('user-save','UserController@userSave');
        /*删除人员账号*/
        $api->post('user-del','UserController@userDel');
        /*人员导入*/
        $api->post('user-import','UserController@import');

        /*部门列表*/
        $api->post('section-list','SectionController@sectionList');
        /*部门添加*/
        $api->post('section-add','SectionController@sectionAdd');
        /*部门编辑*/
        $api->post('section-save','SectionController@sectionSave');
        /*部门删除*/
        $api->post('section-del','SectionController@sectionDel');
        /*部门导入*/
        $api->post('section-import','SectionController@import');

        /*新增流程设计*/
        $api->post('design-add','DesignController@designAdd');
        /*签到*/
        $api->post('sign-in','UserController@signIn');
        /*显示排名*/
        $api->post('accord-rank','DesignController@accordRank');

        $api->post('score-node','DesignController@scoreNode');
        /*上传素材*/
        $api->post('attachment','DesignController@attachment');
        /*删除素材*/
        $api->post('attachment-del','DesignController@attachmentDel');

        /*
         * 导演端
         * */

        /*人员签到列表*/
        $api->post('user-sign','UserController@userSign');
        /*抽签*/
        $api->post('draw-lots','UserController@drawLots');
        /*播放素材*/
        $api->post('play-video','DesignController@playVideo');
        /*播放题目*/
        $api->post('play-problem','DesignController@playProblem');
        /*展示答案*/
        $api->post('play-answer','DesignController@playAnswer');
        /*对答案进行评分*/
        $api->post('score-add','ScoreController@scoreAdd');

        /*更改参考答案是否显示*/
        $api->post('problem-status','DesignController@problemStatus');

        /*已评分的人*/
        $api->post('score-user','ScoreController@scoreUser');
        /*已回答问题的人*/
        $api->post('answer-user','AnswerController@answerUser');
        /*
         * 参演组
         * */
        /*答题*/
        $api->post('answer-add','AnswerController@answerAdd');
        /*显示抽签结果*/
        $api->post('the-draw','DesignController@theDraw');
        /*显示点评*/
        $api->post('show-review','DesignController@showReview');

        $api->post('created-word','DesignController@createdWord');
        $api->post('time-end','DesignController@timeEnd');
        $api->post('word','wordController@word');

        $api->get('download','wordController@download');

        /*流程设计问题*/
        $api->post('design-answer','DesignController@designAnswer');

        /*七牛直播*/
        //创建
        $api->post('create-stream','QiuniuLiveController@createStream');
        //获取
        $api->post('get-publish','QiuniuLiveController@publishUrl');
        $api->post('get-play','QiuniuLiveController@playUrl');
        $api->post('get-HLS','QiuniuLiveController@playHLS');
        $api->post('get-HDL','QiuniuLiveController@playHDL');
        $api->post('info','QiuniuLiveController@info');
        $api->post('enable','QiuniuLiveController@enable');
        $api->post('disable','QiuniuLiveController@disable');
        $api->post('list','QiuniuLiveController@listStreams');
        $api->post('list-live','QiuniuLiveController@listLiveStreams');

        $api->post('the-draft','DesignController@theDraft');
        $api->post('is-draft','DesignController@isDraft');
    });
});