<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-01-18 11:27:44
 * @LastEditTime: 2021-06-18 10:24:34
 * @LastEditors: Please set LastEditors
 * @Description: 系统常量
 * @FilePath: .\app\Helpers\base.common.php
 */


// 时间戳
define('SYSTEM_TIME', time());

// 本地储存
define('LOCAL_STORE', '/uploads/');

// 文件类型 
define('DOCUMENT_TYPE', [
    1 => ['text', '文本'],
    2 => ['image', '图片'],
    3 => ['video', '视频'],
    4 => ['voice', '音频'],
    5 => ['application', '应用']
]);

// 密钥剪纸
define('SECRET_KEY', ['KeyId' => 'accessKeyId', 'KeySecret' => 'accessKeySecret']);
// 默认定义键值
define('DEFAULT_SECRET_KEY', ['KeyId' => 'param_val', 'KeySecret' => 'param_key']);

// 模块
define('MODULES', [
    1 => ['configs', '系统'],
    2 => ['admin', '管理员'],
    3 => ['menu', '菜单'],
    4 => ['group', '组织'],
    5 => ['users', '用户'],
    6 => ['category', '分类'],
    7 => ['logs', '日志'],
    8 => ['upload', '上传'],
    9 => ['wechat', '微信用户'],
    10 => ['study_questions', '驾考题库'],
    11 => ['study_symbol', '交规图标'],
    12 => ['study_drives', '驾考视频'],
    13 => ['single_page', '单页面'],
    14 => ['ad_place', '广告'],
    15 => ['pay', '支付'],
    16 => ['order', '订单'],
    17 => ['card_vip', '会员卡']
]);

// 操作权限
define('ACTION_PERMISSION', [
    1 => ['add', '新增'],
    2 => ['query', '查询'],
    3 => ['get', '详情'],
    4 => ['update', '修改'],
    5 => ['delete', '删除'],
    6 => ['import', '导入'],
    7 => ['export', '导出']
]);

// 性别
define('GENDER', ['未知', '男', '女']);

// 登录类型
define('LOGIN_TYPE', [
    1 => '中后台',
    2 => '微信小程序'
]);

// 数据生命周期
define('LIFE', [
    1 => '正常',
    2 => '未审核',
    3 => '未审中',
    4 => '拒绝审核',
    5 => '禁用',
    6 => '删除'
]);

// 分页
define('PAGINATOR', [
    'page' => 1,
    'limit' => 20
]);

// 正则
define('REGEX', [
    'phone' => '/^(?:(?:\+|00)86)?1[3-9]\d{9}$/', // 宽松电话号码
    'code' => '/^\d{6}$/', // 6位数字验证码
    'siteURl' => '/^(((ht|f)tps?):\/\/)?[\w-]+(\.[\w-]+)+([\w.,@?^=%&:/~+#-]*[\w@?^=%&/~+#-])?$/' //网址(url,支持端口和"?+参数"和"#+参数)
]);

// 短信字符串
define('SMSSTR', '_code.');

// 短信模板(Aliyun => DY)
define('SMSDYTEMP', [
    // 鉴权验证码
    'code' => [
        'template_id' => 'SMS_214595143',
        'sign' => '任我行',
        'expires_in' => 60 * 5,
        'expires_cycle' => 60
    ]
]);

// 短信操作类型
define('CODETODO', [
    // 鉴权验证码
    'login' => '会员登录'
]);

// 题库布尔类型
define('QUESTION_BOOLE', [
    '对' => 'A',
    '错' => 'B'
]);

// 题库类型
define('QUESTION_TYPE', [
    [
        1 => ['小车', 'C1'],
        2 => ['货车', 'A2'],
        3 => ['客车', 'A1'],
        4 => ['摩托车', 'D'],
    ]
]);

// 题库查询类型
define('QUESTION_RULE', [
    'random' => 0,
    'errors' => 1,
    'collect' => 2,
    'undone' => 3
]);



