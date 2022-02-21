<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-01-18 11:27:44
 * @LastEditTime: 2021-06-23 17:03:48
 * @LastEditors: Please set LastEditors
 * @Description: 公共函数
 * @FilePath: .\app\Helpers\functions.common.php
 */


//  随机字符串
function make_random_string($min = 16, $max = 32, $string = '') 
{ 
  
    // 密码字符集，可任意添加你需要的字符 
    $chars = [
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h',
        'i', 'j', 'k', 'l','m', 'n', 'o', 'p', 'q', 'r', 's',
        't', 'u', 'v', 'w', 'x', 'y','z', 'A', 'B', 'C', 'D',
        'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L','M', 'N', 'O',
        'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y','Z',
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '_'
    ];

    if ($string === 'max') {
        $chars = array_merge($chars, [
            '!','@','#', '$', '%', '^', '&', '*', '(', ')', '-',
            '[', ']', '{', '}', '<', '>', '~', '`', '+', '=', ',',
            '.', ';', ':', '/', '?', '|'
        ]);
    }

    $length = rand($min, $max);
    // 在 $chars 中随机取 $length 个数组元素键名 
    $keys = array_rand($chars, $length);
    $random_string = ''; 
    for($i = 0; $i < $length; $i++) 
    { 
        // 将 $length 个数组元素连接成字符串 
        $random_string .= $chars[$keys[$i]]; 
    } 
    return str_shuffle($random_string); 
}

// 随机生成指定长度数字
function generate_code($length = 4) {
    return rand(pow(10, ($length-1)), pow(10, $length) - 1 );
}

// 数组扁平化
function flatten(array $array) {
    // $flat = array();
    // array_walk_recursive($array, function($a) use (&$flat) { $flat[] = $a; });
    // return $flat;

    $flat = array();
    foreach ($array as $key => $value) {
        if (is_array($value)){
            $flat = array_merge($flat, flatten($value));
        } else {
            $flat[$key] = $value;
        }
    }
    return $flat;
}



// 查找指定数组
function logs_data_details($array = [], $value = '', $key = '', $is_key = 0) {
    $search = array_filter($array, function($t) use ($value, $key) { return $t[$key] == $value; });
    return $is_key ? array_keys($search)[0] : array_values($search)[0];
}

function object_array($object, $assoc = false)
{
    return json_decode(json_encode($object), $assoc);
}

// 是否拼接字符串
function isJointString($str = '', $joint_str = 'AND')
{
    return $str ? "  $joint_str" : ' ';
}

// 本地路径拼接指定字符串
function isLocalPath($path = '')
{
    $pattern = "/^(http|https):\/\/.*$/i";
    if (!empty($path) && ! preg_match($pattern, $path)) {
        $path = LOCAL_STORE . $path;
    }
    return $path;
}

// 字符串中是否有指定字符串，否 => 拼接
function isHasStringReplace($strings = '', $joint_str = '', $target = [])
{

    foreach ($target as $v)
    {
        if (strpos($strings, $v) !== false && strpos($strings, $joint_str . $v) === false) {
            $strings = str_replace($v, $joint_str . $v, $strings);
        }
    }
    return $strings;
}


// 筛选数组值变化
function array_values_change($array = [], $data = [], $filter = [])
{
 
    $except = ['updated_time' => 0];
    if ($filter) {
        $except = array_merge($except, $filter);
    }

    $diff = array_diff_assoc(array_diff_key($array, $except), array_diff_key($data, $except));
    $symbol = ';';
    $string = '';
    if (! $diff) {
        return $string;
    }
    foreach ($diff as $key => $value) {
        $string .= $key . ':' . (!empty($data[$key]) ? $data[$key] : '') . '=>' . $value . $symbol;
    }
    return rtrim($string, $symbol);
}

// 无线分类
function genTree($items, $in_field = 'parent_id', $idField = 'id', $put_field = 'son') { 
    // $items小标id必须和$idField相等
    foreach ($items as $item) 
        $items[$item[$in_field]][$put_field][$item[$idField]] = &$items[$item[$idField]]; 
    return isset($items[0][$put_field]) ? $items[0][$put_field] : [];
}

/**
 * 无限极树形数据
 * @param      $data   待分类的数据
 * @param      $id     数据起点id
 * @param      $deep   节点深度   $flat => 数组扁平化 
 */
function treeMagicData($data, $param = [], $idField = 'id', $pidField = 'parent_id') {
    extract(array_merge(['order' => 'desc', 'flat' => 0, 'deep' => 0, 'level' => 1, 'relation' => 'son', 'id' => 0, 'field' => []], $param));
    $tree = [];
    foreach ($data as $k => $row) {
        if ($row[$order === 'desc' ? $pidField : $idField] === $id && ($deep === 0 ? true : $level <= $deep)) {
            if (!empty($flat)) {
                if (in_array($relation, array_keys($row)) && $row[$relation]) {
                    $relation_array = $row[$relation];
                    unset($row[$relation]);
                }
                
                $row['level'] = $level;
                $params = ['order' => $order, 'id' => $row[$order === 'desc' ? $idField : $pidField], 'field' => $field, 'flat' => $flat, 'deep' => $deep, 'level' => $level + 1, 'relation' => $relation];
                if (!empty($relation_array) && COUNT($relation_array)) {
                    $treeMagicData = treeMagicData($relation_array, $params, $idField , $pidField);
                    $tree = array_merge($tree, $treeMagicData);
                }
                array_push($tree, $row);
            } else {
                if (empty($row[$pidField]) && $row[$pidField] !== 0) {
                    $row[$pidField] = $row[$idField];
                }
                $row['level'] = $level;
                $params = ['order' => $order, 'id' => $row[$order === 'desc' ? $idField : $pidField], 'flat' => $flat, 'deep' => $deep, 'level' => $level + 1, 'relation' => $relation];
                $treeMagicData = treeMagicData($data, $params, $idField , $pidField);
                if ($treeMagicData) {
                    $row[$relation] = $treeMagicData;
                }
                $tree[] = $row;
            }
            
        }   
    }
    return $tree;
}

function arraySimple($data = [], $except = ['key' => '', 'value' => '']) {
    $array = [];
    foreach ($data as $v) {
        if (!empty($v[$except['key']]) && $v[$except['key']] === $except['value']) {
            $array[] = $v;
        }
    }
    return $array;
}

// 数组排序
function arraySortField($array = [], $sort_field = 'id') {
    if (empty($array) || COUNT($array) < 1 || empty($array[0][$sort_field])) {
        return $array;
    }
    $list = [];
    foreach ($array as $item)
        $list[$item[$sort_field]] = $item;
    return $list;
}

// 验证网址
function is_url($v) {
	return preg_match("#(http|https)://(.*\.)?.*\..*#i", $v);
}

/**
 * 将数值金额转换为中文大写金额
 * @param $amount float 金额(支持到分)
 * @param $type   int   补整类型,0:到角补整;1:到元补整
 * @return mixed 中文大写金额
 */
function convertAmountToCn($amount, $type = 1) {
    // 判断输出的金额是否为数字或数字字符串
    if(!is_numeric($amount)){
        return "要转换的金额只能为数字!";
    }
 
    // 金额为0,则直接输出"零元整"
    if($amount == 0) {
        return "人民币零元整";
    }
 
    // 金额不能为负数
    if($amount < 0) {
        return "要转换的金额不能为负数!";
    }
 
    // 金额不能超过万亿,即12位
    if(strlen($amount) > 12) {
        return "要转换的金额不能为万亿及更高金额!";
    }
 
    // 预定义中文转换的数组
    $digital = array('零', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖');
    // 预定义单位转换的数组
    $position = array('仟', '佰', '拾', '亿', '仟', '佰', '拾', '万', '仟', '佰', '拾', '元');
 
    // 将金额的数值字符串拆分成数组
    $amountArr = explode('.', $amount);
 
    // 将整数位的数值字符串拆分成数组
    $integerArr = str_split($amountArr[0], 1);
 
    // 将整数部分替换成大写汉字
    $result = '人民币';
    $integerArrLength = count($integerArr);     // 整数位数组的长度
    $positionLength = count($position);         // 单位数组的长度
    $zeroCount = 0;                             // 连续为0数量
    for($i = 0; $i < $integerArrLength; $i++) {
        // 如果数值不为0,则正常转换
        if($integerArr[$i] != 0){
            // 如果前面数字为0需要增加一个零
            if($zeroCount >= 1){
                $result .= $digital[0];
            }
            $result .= $digital[$integerArr[$i]] . $position[$positionLength - $integerArrLength + $i];
            $zeroCount = 0;
        }else{
            $zeroCount += 1;
            // 如果数值为0, 且单位是亿,万,元这三个的时候,则直接显示单位
            if(($positionLength - $integerArrLength + $i + 1)%4 == 0){
                $result = $result . $position[$positionLength - $integerArrLength + $i];
            }
        }
    }
 
    // 如果小数位也要转换
    if($type == 0) {
        // 将小数位的数值字符串拆分成数组
        $decimalArr = str_split($amountArr[1], 1);
        // 将角替换成大写汉字. 如果为0,则不替换
        if($decimalArr[0] != 0){
            $result = $result . $digital[$decimalArr[0]] . '角';
        }
        // 将分替换成大写汉字. 如果为0,则不替换
        if($decimalArr[1] != 0){
            $result = $result . $digital[$decimalArr[1]] . '分';
        }
    }else{
        $result = $result . '整';
    }
    return $result;
}

// 数字转大写(简单汉字)
function strNatInt($num = 0)
{
    $dict = ["零", "一", "二", "三", "四", "五", "六", "七", "八", "九", "十"];
    return $dict[$num];
}

function getUnserializeValue($data, $key) {
    return unserialize($data)[ $key] ?: '';
}

// 判断某个值在二维数组里
function deep_in_array($value, $array, $back = 'boolean') {

    foreach($array as $k => $item) {   
        if(!is_array($item)) {   
            if ($item == $value) {  
                return $back === 'boolean' ? true : $array[$k];  
            } else {  
                continue;   
            }  
        }   
            
        if(in_array($value, $item)) {  
            return $back === 'boolean' ? true : $array[$k];      
        } else if(deep_in_array($value, $item)) {  
            return $back === 'boolean' ? true : $array[$k];      
        }  
    }   
    return false;   
}
// 导出格式
function export_data($code = 100200, $data = [], $msg = '数据请求成功') {
    return [
        'errcode' => $code,
        'errmsg' => is_array($msg) ? implode($msg[array_keys($msg)[0]]) : $msg,
        'data' => empty($data) ? [] : $data
    ];
}
