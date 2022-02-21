<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-06-05 12:08:31
 * @LastEditTime: 2021-06-05 12:14:00
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: 。\app\Http\Controllers\WxApplet.php
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;


use App\Http\Controllers\Settings;

class WxApplet extends Controller
{
    // 页面基础信息
    public function pageInfo (Request $request) 
    {

        $settings = new Settings;

        $data = [
            'site' => 
                [
                    'name' => '车轮宝典',
                    'logo' => 'https://wx.qlogo.cn/mmhead/Q3auHgzwzM7Pbia89eIR85sRrRU484Rvk58X85q8qnLI5VPqL3ciabjw/0',
                    'logo_width' => '48rpx',
                    'logo_height' => '48rpx',
                    'logo_radius' => '50%'
                ],
            'tabbar' => $appletTabbar ?? []
        ];

        return response()->json(export_data(100200, $data, '查询成功'));
       
    }
}
