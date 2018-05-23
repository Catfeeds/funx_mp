<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/23 0023
 * Time:        17:10
 * Describe:
 */
    /**
     * 将数据进行json编码
     */
    function JSON($data)
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * ajax 返回 json 数据
     */
    function jsonResponse($data)
    {
        header("Content-Type : application/json; charset=utf-8");
        echo self::JSON($data);
        exit;
    }

    /**
     * ajax 返回成功消息
     */
    function success($message, $data = array())
    {
        self::jsonResponse(array(
            'code'      => 'success',
            'message'   => $message,
            'data'      => $data,
        ));
    }

    /**
     * ajax 返回错误消息
     */
    function error($message, $data = array())
    {
        self::jsonResponse(array(
            'code'      => 'error',
            'message'   => $message,
            'data'      => $data,
        ));
    }

    /**
     * 检测手机号码
     */
    function isMobile($value)
    {
        if (11 !== mb_strlen($value)) {
            return false;
        }

        $arr = array(
            130, 131, 132, 133, 134, 135, 136, 137, 138, 139,
            145, 146, 147, 148, 149,
            150, 151, 152, 153, 154, 155, 156, 157, 158, 159,
            166,
            171, 172, 173, 174, 175, 176, 177, 178,
            180, 181, 182, 183, 184, 185, 186, 187, 188, 189,
            198, 199,
        );

        return in_array(mb_substr($value, 0, 3), $arr);
    }

    /**
     * 检测身份证号码
     */
     function isCardno($value)
    {
        $city = array(
            '11', '12', '13', '14', '15', '21', '22',
            '23', '31', '32', '33', '34', '35', '36',
            '37', '41', '42', '43', '44', '45', '46',
            '50', '51', '52', '53', '54', '61', '62',
            '63', '64', '65', '71', '81', '82', '91',
        );

        if (!preg_match('/^([\d]{17}[xX\d]|[\d]{15})$/', $value)) {
            return false;
        }

        if (!in_array(substr($value, 0, 2), $city)) {
            return false;
        }

        $value  = preg_replace('/[xX]$/i', 'a', $value);
        $length = strlen($value);

        if ($length == 18) {
            $birthday = substr($value, 6, 4) . '-' . substr($value, 10, 2) . '-' . substr($value, 12, 2);
        } else {
            $birthday = '19' . substr($value, 6, 2) . '-' . substr($value, 8, 2) . '-' . substr($value, 10, 2);
        }

        if (date('Y-m-d', strtotime($birthday)) != $birthday) {
            return false;
        }

        if ($length == 18) {
            $sum = 0;
            for ($i = 17; $i >= 0; $i--) {
                $substr = substr($value, 17 - $i, 1);
                $sum    += (pow(2, $i) % 11) * (($substr == 'a') ? 10 : intval($substr, 11));
            }

            if($sum % 11 != 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * 检测变量是否为空
     */
    function isBlank($value)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                if (!empty($v)) {
                    return false;
                }
            }
            return true;
        }

        return empty($value);
    }

    /**
     * 判断是否是 email
     */
    function isEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) != false;
    }

    /**
     * 检测是否是合法的用户名
     */
    function isUesrName($username)
    {
        return (bool)preg_match("/^[A-Za-z][A-Za-z0-9]{6,32}$/", $username);
    }

    function isPassWord($password)
    {
        return (bool)preg_match("/^\w{6,32}$/", $password);
    }

    /**
     * 检测是否是中文姓名
     */
    function isChineseName($value)
    {
        // 姓名只能为中文
        if (!preg_match("/^\p{Han}+$/u", $value)) {
            return false;
        }

        // 姓名只能 2-4 个字
        $strlen = mb_strlen($value);
        if (($strlen < 2) || ($strlen > 4)) {
            return false;
        }

        return true;
    }

    /**
     * 上周一
     */
    function lastWeekMonday($hour = 0, $minute = 0, $second = 0)
    {
        return mktime($hour, $minute, $second, date('n'), date('j') - date('N') - 6, date('Y'));
    }

    /**
     * 上周日
     */
    function lastWeekSunday($hour = 0, $minute = 0, $second = 0)
    {
        return mktime($hour, $minute, $second, date('n'), date('j') - date('N'), date('Y'));
    }

    /**
     * 本周一
     */
    function weekMonday($hour = 0, $minute = 0, $second = 0)
    {
        return mktime($hour, $minute, $second, date('n'), date('j') - date('N') + 1, date('Y'));
    }

    /**
     * 本周日
     */
    function weekSunday($hour = 0, $minute = 0, $second = 0)
    {
        return mktime($hour, $minute, $second, date('n'), date('j') - date('N') + 7, date('Y'));
    }

    /**
     * 上个月第一天
     */
    function lastMonthFirst($hour = 0, $minute = 0, $second = 0)
    {
        return mktime($hour, $minute, $second, date('n') - 1, 1, date('Y'));
    }

    /**
     * 上个月最后一天
     */
    function lastMonthLast($hour = 0, $minute = 0, $second = 0)
    {
        return mktime($hour, $minute, $second, date('n'), 0, date('Y'));
    }

    /**
     * 本月第一天
     */
    function monthFirst($hour = 0, $minute = 0, $second = 0)
    {
        return mktime($hour, $minute, $second, date('n'), 1, date('Y'));
    }

    /**
     * 本月最后一天
     */
     function monthLast($hour = 0, $minute = 0, $second = 0)
    {
        return mktime($hour, $minute, $second, date('n'), date('t'), date('Y'));
    }

    /**
     * 人民币小写转大写
     * 代码源自互联网, 出处(http://ustb80.blog.51cto.com/6139482/1035327)
     *
     * @param string $number 数值
     * @param string $int_unit 币种单位，默认"元"，有的需求可能为"圆"
     * @param bool $is_round 是否对小数进行四舍五入
     * @param bool $is_extra_zero 是否对整数部分以0结尾，小数存在的数字附加0,比如1960.30，
     *             有的系统要求输出"壹仟玖佰陆拾元零叁角"，实际上"壹仟玖佰陆拾元叁角"也是对的
     * @return string
     */
     function num2rmb($number = 0, $int_unit = '元', $is_round = TRUE, $is_extra_zero = FALSE)
    {

        if (0 == $number) {
            return '零';
        }

        // 将数字切分成两段
        $parts = explode('.', $number, 2);
        $int   = isset($parts[0]) ? strval($parts[0]) : '0';
        $dec   = isset($parts[1]) ? strval($parts[1]) : '';

        // 如果小数点后多于2位，不四舍五入就直接截，否则就处理
        $dec_len = strlen($dec);
        if (isset($parts[1]) && $dec_len > 2) {
            $dec = $is_round
                ? substr(strrchr(strval(round(floatval("0.".$dec), 2)), '.'), 1)
                : substr($parts[1], 0, 2);
        }

        // 当number为0.001时，小数点后的金额为0元
        if (empty($int) && empty($dec)) {
            return '零';
        }

        // 定义
        $chs     = array('0','壹','贰','叁','肆','伍','陆','柒','捌','玖');
        $uni     = array('','拾','佰','仟');
        $dec_uni = array('角', '分');
        $exp     = array('', '万');
        $res     = '';

        // 整数部分从右向左找
        for ($i = strlen($int) - 1, $k = 0; $i >= 0; $k++) {
            $str = '';
            // 按照中文读写习惯，每4个字为一段进行转化，i一直在减
            for ($j = 0; $j < 4 && $i >= 0; $j++, $i--) {
                $u   = $int{$i} > 0 ? $uni[$j] : ''; // 非0的数字后面添加单位
                $str = $chs[$int{$i}] . $u . $str;
            }

            $str = rtrim($str, '0');// 去掉末尾的0
            $str = preg_replace("/0+/", "零", $str); // 替换多个连续的0

            if (!isset($exp[$k])) {
                $exp[$k] = $exp[$k - 2] . '亿'; // 构建单位
            }

            $u2  = $str != '' ? $exp[$k] : '';
            $res = $str . $u2 . $res;
        }

        // 如果小数部分处理完之后是00，需要处理下
        $dec = rtrim($dec, '0');

        // 小数部分从左向右找
        if (!empty($dec)) {
            // $res .= $int_unit;
            $res .= '';

            // 是否要在整数部分以0结尾的数字后附加0，有的系统有这要求
            if ($is_extra_zero) {
                if (substr($int, -1) === '0') {
                    $res.= '零';
                }
            }

            for ($i = 0, $cnt = strlen($dec); $i < $cnt; $i++) {
                $u = $dec{$i} > 0 ? $dec_uni[$i] : ''; // 非0的数字后面添加单位
                $res .= $chs[$dec{$i}] . $u;
            }
            $res = rtrim($res, '0');// 去掉末尾的0
            $res = preg_replace("/0+/", "零", $res); // 替换多个连续的0
        } else {
            //这里不用显示"元整"
            $res .= '';
            // $res .= $int_unit . '整';
        }

        return $res;
    }
