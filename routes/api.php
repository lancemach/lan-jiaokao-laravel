<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-01-30 09:21:17
 * @LastEditTime: 2021-06-23 14:37:02
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: .\routes\api.php
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth;
use App\Http\Controllers\WeChatCommon;
use App\Http\Controllers\Imports;
use App\Http\Controllers\Upload;
use App\Http\Controllers\Basicinfo;
use App\Http\Controllers\Sms\Aliyun\SMS;
use App\Http\Controllers\Study\Index as StudyIndex;
use App\Http\Controllers\Study\Admin as StudyAdmin;
use App\Http\Controllers\Study\Collect as StudyCollect;
use App\Http\Controllers\Study\Transcript as StudyTranscript;
use App\Http\Controllers\Menus;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Settings;
use App\Http\Controllers\Category;
use App\Http\Controllers\Symbol;
use App\Http\Controllers\Pages;
use App\Http\Controllers\Study\Drives;
use App\Http\Controllers\Ad;
use App\Http\Controllers\CardVip;
use App\Http\Controllers\Order;


use App\Http\Controllers\Pay\WeChart as WeChartPay;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

// ['middleware' => ['jwt_auth', 'permission'], ... ]  ... (需注册)

// 白名单（组）
Route::group(['middleware' => 'api_auth'], function ($request) {

    Route::group(['prefix' => 'basic'], function ($request) {
        Route::get('page/info', [Basicinfo::class, 'pageInfo']);
        Route::get('page/index', [Basicinfo::class, 'pageIndex']);
        Route::get('ad/get', [Basicinfo::class, 'getAd']);
    });

    Route::group(['prefix' => 'check'], function ($request) {
        Route::group(['prefix' => 'study'], function ($request) {
            Route::get('category/page', [StudyIndex::class, 'checkCategoryPage']);
        });
        
    });

    Route::group(['prefix' => 'sms'], function ($request) {
        Route::post('aliyun/send', [SMS::class, 'sendSms']);
    });

    Route::group(['prefix' => 'wechat'], function ($request) {
        Route::post('login', [Auth::class, 'loginWeChat']);
        Route::get('unlimited', [WeChatCommon::class, 'getUnlimited']);
    });

    Route::group(['prefix' => 'auth'], function ($request) {
        Route::post('register', [Auth::class, 'register']);
        Route::post('login', [Auth::class, 'login']);
        Route::post('login/wechat', [Auth::class, 'loginWeChat']);
        Route::post('login/smscode', [Auth::class, 'loginSmsCode']);
    });

    // JWT 权限（组）
    Route::group(['middleware' => 'jwt_auth'], function ($request) {
        // 用户（权限）
        Route::group(['prefix' => 'auth'], function ($request) {
            Route::post('create', [Auth::class, 'create']);
            Route::post('logout', [Auth::class, 'logout']);
            Route::post('refresh', [Auth::class, 'refresh']);
            Route::get('me', [Auth::class, 'me']);
        });

        Route::group(['prefix' => 'user'], function ($request) {
            Route::get('info', [Auth::class, 'permissions']);
        });

        Route::group(['prefix' => 'user'], function ($request) {
            Route::get('info', [Auth::class, 'permissions']);
            Route::get('nav', [Menus::class, 'get']);
            Route::get('admin', [Admin::class, 'get']);
        });

        Route::group(['prefix' => 'pages'], function ($request) {
            Route::get('get', [Pages::class, 'get']);
        });

        // 题库（权限）
        Route::group(['prefix' => 'study'], function ($request) {
            Route::get('check', [StudyIndex::class, 'check']);
            Route::get('collect', [StudyCollect::class, 'examinationQuestionsCollect']);
            Route::get('transcript/check', [StudyTranscript::class, 'check']);
            Route::post('transcript/add', [StudyTranscript::class, 'examinationPaper']);
            Route::get('cate/list', [Basicinfo::class, 'pageStudyCate']);
            // 数据分析
            Route::get('data/get', [Basicinfo::class, 'dataAnalysis']);
            // 学车(视频)
            Route::get('drives/get', [Drives::class, 'get']);
            Route::get('symbol/get', [Symbol::class, 'get']);
            Route::get('category/get', [Category::class, 'get']);
            Route::group(['prefix' => 'admin'], function ($request) {
                Route::post('sync', [StudyAdmin::class, 'updateQuestionBank']);
            });
            
        });


        // 后台管理（权限）
        Route::group(['prefix' => 'admin'], function ($request) {
            // 系统设置
            Route::post('password/update', [Auth::class, 'update_password']);
            Route::get('settings/get', [Settings::class, 'get']);
            Route::post('settings/update', [Settings::class, 'update']);

            // 分类
            Route::get('category/get', [Category::class, 'get']);
            Route::post('category/add', [Category::class, 'create']);
            Route::post('category/update', [Category::class, 'update']);
            Route::post('category/delete', [Category::class, 'delete']);
            Route::post('category/activate', [Category::class, 'activate']);
            
            // 学车(考题)
            Route::get('studies/get', [StudyAdmin::class, 'get']);
            Route::post('studies/add', [StudyAdmin::class, 'create']);
            Route::post('studies/update', [StudyAdmin::class, 'update']);
            Route::post('studies/delete', [StudyAdmin::class, 'delete']);
            Route::post('studies/activate', [StudyAdmin::class, 'activate']);
            // 学车(视频)
            Route::get('drives/get', [Drives::class, 'get']);
            Route::post('drives/add', [Drives::class, 'create']);
            Route::post('drives/update', [Drives::class, 'update']);
            Route::post('drives/delete', [Drives::class, 'delete']);
            Route::post('drives/activate', [Drives::class, 'activate']);

            // 分类(图标)
            Route::get('symbol/get', [Symbol::class, 'get']);
            Route::post('symbol/add', [Symbol::class, 'create']);
            Route::post('symbol/update', [Symbol::class, 'update']);
            Route::post('symbol/delete', [Symbol::class, 'delete']);
            Route::post('symbol/activate', [Symbol::class, 'activate']);


            // 广告(位)
            Route::group(['prefix' => 'ad'], function ($request) {
                Route::get('get', [Ad::class, 'get']);
                Route::post('add', [Ad::class, 'create']);
                Route::post('update', [Ad::class, 'update']);
                Route::post('delete', [Ad::class, 'delete']);
                Route::post('activate', [Ad::class, 'activate']);
                // 广告(项)
                Route::group(['prefix' => 'place'], function ($request) {
                    Route::get('get', [Ad::class, 'get'])->name('get_place');
                    Route::post('add', [Ad::class, 'create'])->name('create_place');
                    Route::post('update', [Ad::class, 'update'])->name('update_place');
                    Route::post('delete', [Ad::class, 'delete'])->name('delete_place');
                    Route::post('activate', [Ad::class, 'activate'])->name('activate_place');
                });
            });

            // 单页管理
            Route::get('pages/get', [Pages::class, 'get']);
            Route::post('pages/add', [Pages::class, 'create']);
            Route::post('pages/update', [Pages::class, 'update']);
            Route::post('pages/delete', [Pages::class, 'delete']);
            Route::post('pages/activate', [Pages::class, 'activate']);

        });

         // 会员卡（权限）
         Route::group(['prefix' => 'vip'], function ($request) {
            Route::group(['prefix' => 'card'], function ($request) {
                Route::get('get', [CardVip::class, 'get']);
                Route::post('add', [CardVip::class, 'create']);
                Route::post('open', [CardVip::class, 'open']);
            });
            
            
        });

        // 订单（权限）
        Route::group(['prefix' => 'order'], function ($request) {
            Route::get('get', [Order::class, 'get']);
            Route::post('add', [Order::class, 'create']);
            
        });

        Route::group(['prefix' => 'upload'], function ($request) {
            Route::post('image/add', [Upload::class, 'uploadsImages']);
            Route::post('image/delete', [Upload::class, 'deleteImage']);
        });
    
        Route::group(['prefix' => 'import'], function ($request) {
            // Route::post('products', [Imports::class, 'products']);
        });

        // 支付（权限）
        Route::group(['prefix' => 'pay'], function ($request) {
            Route::group(['prefix' => 'wechat'], function ($request) {
                Route::post('miniapp', [WeChartPay::class, 'miniapp']);
                // 查询订单
                Route::get('get', [WeChartPay::class, 'get']);
                // 关闭未支付订单
                Route::post('close', [WeChartPay::class, 'close']);
            });
            
            
        });
    });

    // 第三方(异步)通知
    // 支付
    Route::group(['prefix' => 'pay'], function ($request) {
        Route::group(['prefix' => 'wechat'], function ($request) {
            Route::post('notify', [WeChartPay::class, 'notify']);
        });
        
    });
    
});