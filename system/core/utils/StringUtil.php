<?php

/**
 * 字符串操作工具类文件
 *
 * @author banyanCheung <banyan@ibos.com.cn>
 * @link http://www.ibos.com.cn/
 * @copyright Copyright &copy; 2012-2013 IBOS Inc
 */
/**
 * 字符串操作工具类,提供字符串操作的所需方法，如过滤，截取，查找，加解密等
 *
 * @package application.core.utils
 * @version $Id: string.php -1   $
 * @author banyanCheung <banyan@ibos.com.cn>
 */

namespace application\core\utils;

use application\extensions\Tree;
use application\modules\department\model\Department;
use application\modules\message\utils\Expression;
use application\modules\user\model\User;

class StringUtil
{
    const FORM_TOKEN_NAME = 'formtoken';

    /**
     * 检测一个字符串是否email格式
     * @param string $email
     * @return boolean
     */
    public static function isEmail($email)
    {
        return strlen($email) > 6 && preg_match("/^[\w\-\.]+@[\w\-]+(\.\w+)+$/", $email);
    }

    /**
     * 检测一个字符串是否手机格式
     * @param string $str
     * @return boolean
     */
    public static function isMobile($str)
    {
        // 这不科学，换一个
        // return preg_match( "/^1\\d{10}/", $str );
        return preg_match("/^1\\d{10}$/", $str);
    }

    /**
     * 字符串方式实现 preg_match("/(s1|s2|s3)/", $string, $match)
     * @param string $string 源字符串
     * @param array $arr 要查找的字符串 如array('s1', 's2', 's3')
     * @param boolean $returnValue 是否返回找到的值
     * @return boolean
     */
    public static function istrpos($string, $arr, $returnValue = false)
    {
        if (empty($string)) {
            return false;
        }
        foreach ((array)$arr as $v) {
            if (strpos($string, $v) !== false) {
                $return = $returnValue ? $v : true;
                return $return;
            }
        }
        return false;
    }

    /**
     * 对字符串或者输入进行 addslashes 操作
     * @param string $string
     * @param integer $force
     * @return mixed
     */
    public static function iaddSlashes($string, $force = 1)
    {
        if (is_array($string)) {
            $keys = array_keys($string);
            foreach ($keys as $key) {
                $val = $string[$key];
                unset($string[$key]);
                $string[addslashes($key)] = self::iaddSlashes($val, $force);
            }
        } else {
            $string = addslashes($string);
        }
        return $string;
    }

    /**
     * 对字符串进行加密和解密
     * @param string $string
     * @param string $operation DECODE 解密 | ENCODE  加密
     * @param string $key 当为空的时候,取全局密钥
     * @param integer $expiry 有效期,单位秒
     * @return string
     * @author Ring
     */
    public static function authCode($string, $operation = 'DECODE', $key = '', $expiry = 0)
    {
        $ckeyLength = 4;
        $key = md5($key != '' ? $key : Ibos::app()->setting->get('authkey'));
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckeyLength ? ($operation == 'DECODE' ?
            substr($string, 0, $ckeyLength) :
            substr(md5(microtime()), -$ckeyLength)) : '';

        $cryptkey = $keya . md5($keya . $keyc);
        $keyLength = strlen($cryptkey);

        $string = $operation == 'DECODE' ?
            base64_decode(substr($string, $ckeyLength)) :
            sprintf('%010d', $expiry ? $expiry + time() : 0) .
            substr(md5($string . $keyb), 0, 16) . $string;
        $stringLength = strlen($string);

        $result = '';
        $box = range(0, 255);

        $rndkey = array();
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $keyLength]);
        }

        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for ($a = $j = $i = 0; $i < $stringLength; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }

        if ($operation == 'DECODE') {
            if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc . str_replace('=', '', base64_encode($result));
        }
    }

    /**
     * 产生随机码
     * @param integer $length 要多长
     * @param integer $numberic 数字还是字符串
     * @return string $hash 返回字符串
     */
    public static function random($length, $numeric = 0)
    {
        $seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
        $seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
        $hash = '';
        $max = strlen($seed) - 1;
        for ($index = 0; $index < $length; $index++) {
            $hash .= $seed{mt_rand(0, $max)};
        }
        return $hash;
    }

    /**
     * HTML转义字符
     * @param mixed $string 数组或字符串
     * @param mixed $flags htmlspecialchars函数的标记
     * @link http://www.php.net/manual/zh/function.htmlspecialchars.php
     * @return string 返回转义好的字符串
     */
    public static function ihtmlSpecialChars($string, $flags = null)
    {
        if (is_array($string)) {
            foreach ($string as $key => $val) {
                $string[$key] = self::ihtmlSpecialChars($val, $flags);
            }
        } else {
            if ($flags === null) {
                $string = str_replace(array('&', '"', "'", '<', '>'), array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;'), $string);
                if (strpos($string, '&amp;#') !== false) {
                    $string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4}));)/', '&\\1', $string);
                }
            } else {
                if (PHP_VERSION < '5.4.0') {
                    $string = htmlspecialchars($string, $flags);
                } else {
                    if (strtolower(CHARSET) == 'utf-8') {
                        $charset = 'UTF-8';
                    } else {
                        $charset = 'ISO-8859-1';
                    }
                    $string = htmlspecialchars($string, $flags, $charset);
                }
            }
        }
        return $string;
    }

    /**
     * 参数解释见ihtmlSpecialChars
     * 这个方法的作用跟上面那个一样，但是是通过引用的方式处理数据
     * 目的是：只对数组中的某些元素处理，如：
     * $postData = array(
     *      'subject'=>'js代码',
     *      'formula'=>'数学表达式:日期>2天（这里有一个特殊字符“>”）'
     * )
     * 这个时候就调用ihtmlSpecialCharsUseReference($postData['subject']);
     * @param mixed $data
     * @param mixed $flags
     */
    public static function ihtmlSpecialCharsUseReference(&$data, $flags = null)
    {
        $data = self::ihtmlSpecialChars($data, $flags);
    }

    /**
     * 根据中文裁减字符串
     * @param string $string - 字符串
     * @param integer $length - 长度
     * @param string $doc - 缩略后缀 default=' ...'
     * @return string 返回带省略号被裁减好的字符串
     */
    public static function cutStr($string, $length, $dot = ' ...')
    {
        $strlen = self::iStrLen($string);
        if ($strlen <= $length) {
            return $string;
        }
        $pre = chr(1);
        $end = chr(1);
        $string = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array($pre . '&' . $end, $pre . '"' . $end, $pre . '<' . $end, $pre . '>' . $end), $string);
        /*
         * 这里存在bug
         * 如果传过来的string中包含后转义后的hmtl标签（&amp;）
         * 经过上一行代码后string的字符串长度会变短
         * 而在下面的代码
         * while ($n < $strlen) {
         *      $t = ord($string[$n]);
         * 会造成string越界
         *
         * 解决办法是重新计算string的长度
         */
        $strlen = self::iStrLen($string);
        $strCut = '';
        if (strtolower(CHARSET) == 'utf-8') {
            $n = $tn = $noc = 0;
            while ($n < $strlen) {
                $t = ord($string[$n]);
                if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                    $tn = 1;
                    $n++;
                    $noc++;
                } elseif (194 <= $t && $t <= 223) {
                    $tn = 2;
                    $n += 2;
                    $noc += 2;
                } elseif (224 <= $t && $t <= 239) {
                    $tn = 3;
                    $n += 3;
                    $noc += 2;
                } elseif (240 <= $t && $t <= 247) {
                    $tn = 4;
                    $n += 4;
                    $noc += 2;
                } elseif (248 <= $t && $t <= 251) {
                    $tn = 5;
                    $n += 5;
                    $noc += 2;
                } elseif ($t == 252 || $t == 253) {
                    $tn = 6;
                    $n += 6;
                    $noc += 2;
                } else {
                    $n++;
                }
                if ($noc >= $length) {
                    break;
                }
            }
            if ($noc > $length) {
                $n -= $tn;
            }
            $strCut = substr($string, 0, $n);
        } else {
            for ($i = 0; $i < $length; $i++) {
                $strCut .= ord($string[$i]) > 127 ? $string[$i] . $string[++$i] : $string[$i];
            }
        }
        $strCut = str_replace(array($pre . '&' . $end, $pre . '"' . $end, $pre . '<' . $end, $pre . '>' . $end), array('&amp;', '&quot;', '&lt;', '&gt;'), $strCut);
        $pos = strrpos($strCut, chr(1));
        if ($pos !== false) {
            $strCut = substr($strCut, 0, $pos);
        }
        return $strCut . $dot;
    }

    /**
     * 针对uft-8进行特殊处理的strlen函数 (原名dstrlen)
     * @param string $str
     * @return integer 字符串的长度
     * @author Ring
     */
    public static function iStrLen($str)
    {
        if (strtolower(CHARSET) != 'utf-8') {
            return strlen($str);
        }
        $count = 0;
        for ($index = 0; $index < strlen($str); $index++) {
            $value = ord($str[$index]);
            if ($value > 127) {
                $count++;
                if ($value >= 192 && $value <= 223) {
                    $index++;
                } elseif ($value >= 224 && $value <= 239) {
                    $index = $index + 2;
                } elseif ($value >= 240 && $value <= 247) {
                    $index = $index + 3;
                }
            }
            $count++;
        }
        return $count;
    }

    /**
     * 查找是否包含在内
     *
     * @param string|array $string 目标范围
     * @param  string|array $id 所有值
     * @return boolean
     * @author Ring
     */
    public static function findIn($string, $id)
    {
        if (is_array($string)) {
            $string = implode(',', $string);
        }
        if (is_array($id)) {
            $id = implode(',', $id);
        }

        $string = trim($string, ",");
        $newId = trim($id, ",");
        if ($newId == '' || $newId == ',') {
            return false;
        }
        $idArr = array_filter(explode(",", $newId));
        $strArr = array_filter(explode(",", $string));
        if (array_intersect($strArr, $idArr)) {
            return true;
        }
        return false;
    }

    /**
     * 判断给定的参数是否是一个有效的IP地址 原名(isip)
     * @param string $ip ip地址字符串
     * @return boolean
     * @author Ring
     */
    public static function isIp($ip)
    {
        if (!strcmp(long2ip(sprintf("%u", ip2long($ip))), $ip)) {
            return true;
        }
        return false;
    }

    /**
     * 判断一个字符串是否在另一个字符串中存在
     *
     * @param string 原始字串 $string
     * @param string 查找 $find
     * @return boolean
     */
    public static function strExists($string, $find)
    {
        return !(strpos($string, $find) === false);
    }

    /**
     * 返回一个形如10.0.*.*这样的IP 原名(get_sub_ip)
     * @param string $ip ip地址字符串
     * @return string
     * @author Ring
     */
    public static function getSubIp($ip = '')
    {
        if (empty($ip)) {
            $ip = $clientIp = Ibos::app()->setting->get('clientip');
        }
        $reg = '/(\d+\.)(\d+\.)(\d+)\.(\d+)/';
        return preg_replace($reg, "$1$2*.*", $ip);
    }

    /**
     * 返回显示IP的字符串 原名(ip_show)
     * @param string $str
     * @return string
     * @author Ring
     */
    public static function displayIp($str)
    {
        if (self::isIp($str)) {
            return self::getSubIp($str);
        }
        return $str;
    }

    /**
     * 特别处理数组连接成字符串 带单引号 原名(dimplode)
     * @param array $array
     * @return string
     * @author Ring
     */
    public static function iImplode($array)
    {
        if (!empty($array)) {
            $array = array_map('addslashes', $array);
            return "'" . implode("','", is_array($array) ? $array : array($array)) . "'";
        } else {
            return '';
        }
    }

    /**
     * 分割参数字符串返回数组。方便url组件创建URL
     * <code>
     *        $param = 'a=3&b=4';
     *        $splitParam = StringUtil::splitParam($param);
     * </code>
     * @param string $param
     * @return array
     */
    public static function splitParam($param)
    {
        $return = array();
        if (!empty($param)) {
            $params = explode('&', trim($param));
            foreach ($params as $data) {
                list($key, $value) = explode('=', $data);
                $return[$key] = $value;
            }
        }
        return $return;
    }

    /**
     * 处理sql语句
     * @param string $sql 原始的sql
     * @return array
     */
    public static function splitSql($sql)
    {
        $sql = str_replace("\r", "\n", $sql);
        $ret = array();
        $num = 0;
        $queriesArr = explode(";\n", trim($sql));
        unset($sql);
        foreach ($queriesArr as $querys) {
            $queries = explode("\n", trim($querys));
            foreach ($queries as $query) {
                $val = substr(trim($query), 0, 1) == "#" ? null : $query;
                if (isset($ret[$num])) {
                    $ret[$num] .= $val;
                } else {
                    $ret[$num] = $val;
                }
            }
            $num++;
        }
        return $ret;
    }

    /**
     * 把密码字符串转换为可显示的形式
     * <code>
     * $password = '19881014';
     * echo StringUtil::passwordMask($password);
     * // returns '1********14';
     * </code>
     * @param string $password
     * @return string
     */
    public static function passwordMask($password)
    {
        return !empty($password) ? $password{0} . '********' . substr($password, -2) : '';
    }

    /**
     * 过滤日志字符串
     * @param mixed $str
     * @return mixed
     */
    public static function clearLogString($str)
    {
        if (!empty($str)) {
            if (!is_array($str)) {
                $str = self::ihtmlSpecialChars(trim($str));
                $str = str_replace(array("\t", "\r\n", "\n", "   ", "  "), ' ', $str);
            } else {
                foreach ($str as $key => $val) {
                    $str[$key] = self::clearLogString($val);
                }
            }
        }
        return $str;
    }

    /**
     * 封装Tree类，快速获取有层级的分类树
     * <code>
     *    $format = "<option value='\$catid' \$selected>\$spacer\$name</option>";
     *  $data = Ibos:app()->setting->get('cache/positioncategory');
     *  $trees = StringUtil::getTree($data,$format);
     * </code>
     * @param array $data 树数据
     * @param string $format 生成的格式字符串
     * @param integer $id 被选中的ID，比如在做树型下拉框的时候需要用到
     * @param string $nbsp 间隔，取决于$spacer放在格式字符串的那里
     * @param array $icon 修饰符号，可以换成图片
     * @return string
     */
    public static function getTree($data, $format = "<option value='\$catid' \$selected>\$spacer\$name</option>", $id = 0, $nbsp = '&nbsp;&nbsp;&nbsp;&nbsp;', $icon = array('&nbsp;&nbsp;', '&nbsp;&nbsp;', '&nbsp;&nbsp;'))
    {
        $tree = new Tree();
        //-- 生成前台显示样式 --
        $tree->init($data);
        //-- 样式处理 --
        $tree->icon = $icon;
        $tree->nbsp = $nbsp;
        $trees = $tree->get_tree(0, $format, $id);
        return $trees;
    }

    /**
     * 获取分与不分前缀的id
     * @param mixed $ids
     * @param boolean $index 按前缀索引
     * @return array
     */
    public static function getId($ids, $index = false)
    {
        $newIds = array();
        $idList = is_array($ids) ? $ids : explode(',', $ids);
        foreach ($idList as $idstr) {
            if (!empty($idstr)) {
                if ($index) {
                    $prefix = substr($idstr, 0, 1);
                    $newIds[$prefix][] = substr($idstr, 2);
                } else {
                    $newIds[] = substr($idstr, 2);
                }
            }
        }
        return $newIds;
    }

    /**
     * 获取有前缀的id字符串所包含的uid数组
     * @param mixed $ids
     * @return array
     */
    public static function getUid($ids)
    {
        $uids = array();
        $idList = is_array($ids) ? $ids : array($ids);
        foreach ($idList as $idstr) {
            if (!empty($idstr)) {
                $identifier = $idstr{0};
                $uid = self::getUidByIdentifier($identifier, $idstr);
                $uids = array_merge($uids, $uid);
            }
        }
        return array_unique($uids);
    }

    /**
     * 封装id加上前缀
     * <code>
     * $id = array(23,24,25,26);
     * print_r(StringUtil::wrapId($id));
     * return 'u_23,u_24,u_25,u_26';
     * </code>
     * @param array $ids id数组\
     * @param string $identifier 前缀
     * @param string $glue 分隔符
     * @return string
     */
    public static function wrapId($ids, $identifier = 'u', $glue = ',')
    {
        if (empty($ids)) {
            return '';
        }
        $id = is_array($ids) ? $ids : explode(',', $ids);
        $wrapId = array();
        foreach ($id as $tempId) {
            if (!empty($tempId)) {
                $wrapId[] = $identifier . '_' . $tempId;
            }
        }
        return implode($glue, $wrapId);
    }

    /**
     * 根据id标识符查找所包含的uid
     * 如getUidByIdentifier( 'u','u_1' );
     * @param string $identifier 标识符,eg:u,d,p,r,c
     * @param string $str 完整id字符串
     * @return array 返回uid组成的数组
     */
    public static function getUidByIdentifier($identifier, $str, $findC = false, $returnDisable = false, $returnRelated = true, $pre = true)
    {
        $id = true === $pre ? substr($str, 2) : $str;
        if (strcmp($identifier, 'u') == 0) :
            return array($id);
        elseif (strcmp($identifier, 'd') == 0):
            return User::model()->fetchAllUidByDeptids($id, $returnDisable, $returnRelated);
        elseif (strcmp($identifier, 'p') == 0):
            return User::model()->fetchAllUidByPositionIds($id, $returnDisable, $returnRelated);
        elseif (strcmp($identifier, 'r') == 0):
            return User::model()->fetchAllUidByRoleids($id, $returnDisable, $returnRelated);
        elseif ($findC && strcmp($identifier, 'c') == 0):
            return User::model()->fetchUidA($returnDisable);
        endif;
        return array();
    }

    /**
     * 通过'u_1,d_1,p_1,r_1'或者array('u_1','d_1','p_1','r_1')这样的字符串或者数组获取uid
     * @param array|string $udpX
     * @return array
     */
    public static function getUidAByUDPX($udpX, $findC = false, $returnDisable = false, $returnRelated = true)
    {
        $udpA = is_array($udpX) ? $udpX : explode(',', $udpX);
        if ($findC) {
            $diff = array_intersect($udpA, array('c_0', 'alldept'));
            if (!empty($diff)) {
                return User::model()->fetchUidA($returnDisable);
            }
        }
        $uidA = $uArray = $dArray = $pArray = $rArray = array();
        foreach ($udpA as $row) {
            $pre = substr($row, 0, 1);
            if (strcmp($pre, 'u') == 0) {
                $uArray[] = substr($row, 2);
            }
            if (strcmp($pre, 'd') == 0) {
                $dArray[] = substr($row, 2);
            }
            if (strcmp($pre, 'p') == 0) {
                $pArray[] = substr($row, 2);
            }
            if (strcmp($pre, 'r') == 0) {
                $rArray[] = substr($row, 2);
            }
        }
        if (!empty($uArray)) {
            $uidA = array_merge($uidA, $uArray);
        }
        if (!empty($dArray)) {
            $uidFromD = User::model()->fetchAllUidByDeptids($dArray, $returnDisable, $returnRelated);
            $uidA = array_merge($uidA, $uidFromD);
        }
        if (!empty($pArray)) {
            $uidFromP = User::model()->fetchAllUidByPositionIds($pArray, $returnDisable, $returnRelated);
            $uidA = array_merge($uidA, $uidFromP);
        }
        if (!empty($rArray)) {
            $uidFromR = User::model()->fetchAllUidByRoleids($rArray, $returnDisable, $returnRelated);
            $uidA = array_merge($uidA, $uidFromR);
        }
        return array_unique($uidA);
    }

    /**
     * 封装Intval函数，加上数组支持
     * @param integer $int
     * @param boolean $allowArray
     * @return integer
     */
    public static function iIntval($int, $allowArray = false)
    {
        $ret = intval($int);
        if ($int == $ret || !$allowArray && is_array($int))
            return $ret;
        if ($allowArray && is_array($int)) {
            foreach ($int as &$v) {
                $v = self::iIntval($v, true);
            }
            return $int;
        } elseif ($int <= 0xffffffff) {
            $l = strlen($int);
            $m = substr($int, 0, 1) == '-' ? 1 : 0;
            if (($l - $m) === strspn($int, '0987654321', $m)) {
                return $int;
            }
        }
        return $ret;
    }

    /**
     * 获取文件扩展名
     * @param $fileName 文件名
     * @since IBOS1.0
     */
    public static function getFileExt($fileName)
    {
        return addslashes(strtolower(substr(strrchr($fileName, '.'), 1, 10))) . '';
    }

    /**
     *
     * 正则替换和过滤内容
     *
     * @param  $html
     * @author jason
     */
    public static function pregHtml($html)
    {
        $p = array("/<[a|A][^>]+(topic=\"true\")+[^>]*+>#([^<]+)#<\/[a|A]>/",
            "/<[a|A][^>]+(data=\")+([^\"]+)\"[^>]*+>[^<]*+<\/[a|A]>/",
            "/<[img|IMG][^>]+(src=\")+([^\"]+)\"[^>]*+>/");
        $t = array('topic{data=$2}', '$2', 'img{data=$2}');
        $html = preg_replace($p, $t, $html);
        $html = strip_tags($html, "<br/>");
        return $html;
    }

    /**
     * 获取字符串的长度
     *
     * 计算时, 汉字或全角字符占1个长度, 英文字符占0.5个长度
     *
     * @param string $str
     * @param boolean $filter 是否过滤html标签
     * @return int 字符串的长度
     */
    public static function getStrLength($str, $filter = false)
    {
        if ($filter) {
            $str = html_entity_decode($str, ENT_QUOTES);
            $str = strip_tags($str);
        }
        return (strlen($str) + mb_strlen($str, 'UTF8')) / 4;
    }

    /**
     * 用于过滤标签，输出没有html的干净的文本
     * @param string text 文本内容
     * @return string 处理后内容
     */
    public static function filterCleanHtml($text)
    {
        return \CHtml::encode($text);
    }

    /**
     *
     * @param type $str
     * @param type $allowableTags
     * @return type
     */
    public static function realStripTags($str, $allowableTags = "")
    {
        $str = stripslashes(htmlspecialchars_decode($str));
        return strip_tags($str, $allowableTags);
    }

    /**
     * 用于过滤不安全的html标签，输出安全的html
     * @param string $text 待过滤的字符串
     * @param string $type 保留的标签格式
     * @return string 处理后内容
     */
    public static function filterDangerTag($text, $type = 'html')
    {
        // 无标签格式
        $textTags = '';
        //只保留链接
        $linkTags = '<a>';
        //只保留图片
        $imageTags = '<img>';
        //只存在字体样式
        $fontTags = '<i><b><u><s><em><strong><font><big><small><sup><sub><bdo><h1><h2><h3><h4><h5><h6>';
        //标题摘要基本格式
        $baseTags = $fontTags . '<p><br><hr><a><img><map><area><pre><code><q><blockquote><acronym><cite><ins><del><center><strike>';
        //兼容Form格式
        $formTags = $baseTags . '<form><input><textarea><button><select><optgroup><option><label><fieldset><legend>';
        //内容等允许HTML的格式
        $htmlTags = $baseTags . '<meta><ul><ol><li><dl><dd><dt><table><caption><td><th><tr><thead><tbody><tfoot><col><colgroup><div><span><object><embed><param>';
        //专题等全HTML格式
        $allTags = $formTags . $htmlTags . '<!DOCTYPE><html><head><title><body><base><basefont><script><noscript><applet><object><param><style><frame><frameset><noframes><iframe>';
        //过滤标签
        $text = self::realStripTags($text, ${$type . 'Tags'});
        // 过滤攻击代码
        if ($type != 'all') {
            // 过滤危险的属性，如：过滤on事件lang js
            while (preg_match('/(<[^><]+)(ondblclick|onclick|onload|onerror|unload|onmouseover|onmouseup|onmouseout|onmousedown|onkeydown|onkeypress|onkeyup|onblur|onchange|onfocus|action|background|codebase|dynsrc|lowsrc)([^><]*)/i', $text, $mat)) {
                $text = str_ireplace($mat[0], $mat[1] . $mat[3], $text);
            }
            while (preg_match('/(<[^><]+)(window\.|javascript:|js:|about:|file:|document\.|vbs:|cookie)([^><]*)/i', $text, $mat)) {
                $text = str_ireplace($mat[0], $mat[1] . $mat[3], $text);
            }
        }
        return $text;
    }

    /**
     * 过滤字符串
     * @param string $string 要过滤的字符串
     * @param string $delimiter 分割符
     * @param bool $unique 是否过滤重复值
     * @return string 过滤后的字符串
     */
    public static function filterStr($string, $delimiter = ',', $unique = true)
    {
        $filterArr = array();
        $strArr = explode($delimiter, $string);
        foreach ($strArr as $str) {
            if (!empty($str)) {
                $filterArr[] = trim($str);
            }
        }
        return implode($delimiter, $unique ? array_unique($filterArr) : $filterArr);
    }

    /**
     * 解析成api显示格式
     * @param type $html
     * @return type
     */
    public static function parseForApi($html)
    {
        $html = self::filterDangerTag($html);
        $html = str_replace(array('[SITE_URL]', '&nbsp;'), array(Ibos::app()->setting->get('siteurl'), ' '), $html);
        //@提到某人处理
        $html = preg_replace_callback("/@([\w\x{2e80}-\x{9fff}\-]+)/u", "self::parseWapAtByUname", $html);
        return $html;
    }

    /**
     *
     * @param type $name
     * @return type
     */
    public static function parseWapAtByUname($name)
    {
        /* $info = static_cache( 'user_info_uname_' . $name[1] );
          if ( !$info ) {
          $info = model( 'User' )->getUserInfoByName( $name[1] );
          if ( !$info ) {
          $info = 1;
          }
          static_cache( 'user_info_uname_' . $name[1], $info );
          }
          if ( $info && $info['is_active'] && $info['is_audit'] && $info['is_init'] ) {
          return '<a href="' . U( 'wap/Index/weibo', array( 'uid' => $info['uid'] ) ) . '" >' . $name[0] . "</a>";
          } else {
          return $name[0];
          } */
    }

    /**
     * 表情替换(用于页面显示)
     * @param string $html
     * @return string
     */
    public static function replaceExpression($html)
    {
        return preg_replace_callback("/(\[.+?\])/is", 'self::parseExpression', $html);
    }

    /**
     * 解析数据成网页端显示格式
     * @param type $html
     * @return type
     */
    public static function parseHtml($html)
    {
        $html = htmlspecialchars_decode($html);
        //链接替换
        $html = str_replace('[SITE_URL]', Ibos::app()->setting->get('siteurl'), $html);
        // 外网链接地址处理
        if (!self::hadFormattedUrl($html)) {
            $html = preg_replace_callback('/((?:https?|ftp):\/\/(?:www\.)?(?:[a-zA-Z0-9][a-zA-Z0-9\-]*\.)?[a-zA-Z0-9][a-zA-Z0-9\-]*(?:\.[a-zA-Z0-9]+)+(?:\:[0-9]*)?(?:\/[^\x{2e80}-\x{9fff}\s<\'\"“”‘’,，。]*)?)/u', 'self::parseUrl', $html);
        }
        //表情处理
        $html = preg_replace_callback("/(\[.+?\])/is", 'self::parseExpression', $html);
        //话题处理
        $html = str_replace("＃", "#", $html);
        $html = preg_replace_callback("/#([^#]*[^#^\s][^#]*)#/is", 'self::parseTheme', $html);
        //@提到某人处理

        $html = preg_replace_callback("/@([\w\x{2e80}-\x{9fff}\-]+)/u", 'self::parseAtByUserName', $html);
        return $html;
    }

    /**
     * 表情替换 [格式化微博与格式化评论专用]
     *
     * @param array $data
     * @return mixed
     */
    private static function parseExpression($data)
    {
        if (preg_match("/#.+#/i", $data[0])) {
            return $data[0];
        }
        $allExpression = Expression::getAllExpression();
        $info = isset($allExpression[$data[0]]) ? $allExpression[$data[0]] : false;
        if ($info) {
            return preg_replace("/\[.+?\]/i", "<img class='exp-img' src='" . STATICURL . "/image/expression/" . $info['icon'] . "' />", $data[0]);
        } else {
            return $data[0];
        }
    }

    /**
     * img 标签表情转换为普通表情
     * Example：StringUtil::imgToExpression('<img class='exp-img' src='/static/image/expression/df_ldln.gif' />') 返回 [df_ldln]
     *
     * @param string $imgStr img 标签字符串
     * @return string 普通表情字符串
     */
    public static function imgToExpression($imgStr)
    {
        $imgPattern = '@<img .+?src=[\'"]/static/image/expression/(.+?)\.gif[\'"] />@i';
        return preg_replace($imgPattern, '[\1]', $imgStr);;
    }

    /**
     * 根据用户昵称获取用户ID [格式化微博与格式化评论专用]
     * @param array $name
     * @return string
     */
    private static function parseAtByUserName($name)
    {
        $info = Cache::get('userInfoRealName_' . md5($name[1]));
        if (!$info) {
            $info = User::model()->findByRealname($name[1]);
            Cache::set('userInfoRealName_' . md5($name[1]), $info);
        }
        if ($info) {
            return '<a class="anchor" data-toggle="usercard" data-param="uid=' . $info['uid'] . '" href="' . $info['space_url'] . '" target="_blank">' . $name[0] . "</a>";
        } else {
            return $name[0];
        }
    }

    /**
     * 话题替换 [格式化微博专用]
     * @param array $data
     * @return string
     */
    private static function parseTheme($data)
    {
        // 如果话题被锁定，则不带链接
        $lock = Ibos::app()->db->createCommand()
            ->select('lock')
            ->from('{{feed_topic}}')
            ->where(sprintf("topicname = '%s'", $data[1]))
            ->queryScalar();
        if (!$lock) {
            return "<a class='wb-source' href=" . Ibos::app()->urlManager->createUrl('weibo/topic/detail', array('k' => urlencode($data[1]))) . ">" . $data[0] . "</a>";
        } else {
            return $data[0];
        }
    }

    /**
     * 检查是否有已格式化的URL，有返回true，否则false
     * @param $html
     * @return boolean
     */
    private static function hadFormattedUrl($html)
    {
        return strpos($html, 'o-url') !== false;
    }

    /**
     * 格式化微博,替换链接地址
     * @param string $url
     */
    public static function parseUrl($url)
    {
        $str = '<div class="url">';
        if (preg_match("/(youku.com|youtube.com|ku6.com|sohu.com|mofile.com|sina.com.cn|tudou.com|yinyuetai.com)/i", $url[0], $hosts)) {
            // TODO: 语言包
            $str .= '<a href="' . $url[0] . '" target="_blank" data-node-type="wbUrl" class="o-url-video">视频</a>';
        } else if (strpos($url[0], 'taobao.com')) {
            $str .= '<a href="' . $url[0] . '" target="_blank" data-node-type="wbUrl" class="o-url-taobao">淘宝</a>';
        } else {
            $str .= '<a href="' . $url[0] . '" target="_blank" data-node-type="wbUrl" class="o-url-web">网页</a>';
        }

        $str .= '</div>';
        return $str;
    }

    /**
     * 格式化微博内容中url内容的长度
     * @param string $match 匹配后的字符串
     * @return string 格式化后的字符串
     */
    public static function formatFeedContentUrlLength($match)
    {
        static $i = 97;
        $result = '{iurl==' . chr($i) . '}';
        $i++;
        $GLOBALS['replaceHash'][$result] = $match[0];
        return $result;
    }

    public static function replaceUrl($content)
    {
        //$content = preg_replace_callback('/((?:https?|ftp):\/\/(?:[a-zA-Z0-9][a-zA-Z0-9\-]*)*(?:\/[^\x{2e80}-\x{9fff}\s<\'\"“”‘’,，。]*)?)/u', '_parse_url', $content);
        $content = str_replace('[SITE_URL]', Ibos::app()->setting->get('siteurl'), $content);
        $content = preg_replace_callback('/((?:https?|mailto|ftp):\/\/([^\x{2e80}-\x{9fff}\s<\'\"“”‘’，。}]*)?)/u', 'self::parseUrl', $content);
        return $content;
    }

    /**
     * 生成GUID(用户唯一ID)
     * @return string
     */
    public static function createGuid()
    {
        $charid = strtoupper(md5(uniqid(mt_rand(), true)));
        $hyphen = chr(45); // "-"
        $guid = substr($charid, 0, 8) . $hyphen
            . substr($charid, 8, 4) . $hyphen
            . substr($charid, 12, 4) . $hyphen
            . substr($charid, 16, 4) . $hyphen
            . substr($charid, 20, 12);
        return $guid;
    }

    /**
     * 组合选人框的值
     * @param string $deptid 部门id
     * @param string $positionid 岗位Id
     * @param string $uid 用户id
     * @return type
     */
    public static function joinSelectBoxValue($deptid, $positionid, $uid, $role = null)
    {
        $tmp = array();
        if (!empty($deptid)) {
            if ($deptid == 'alldept') {
                return 'c_0';
            }
            $tmp[] = self::wrapId($deptid, 'd');
        }
        if (!empty($positionid)) {
            $tmp[] = self::wrapId($positionid, 'p');
        }
        if (!empty($uid)) {
            $tmp[] = self::wrapId($uid, 'u');
        }
        if (!empty($role)) {
            $tmp[] = self::wrapId($role, 'r');
        }
        return implode(',', $tmp);
    }

    /**
     * 处理选人框部门、岗位、人员数据
     * @param string $scope 选人框数据
     * @return array
     */
    public static function handleSelectBoxData($scope, $returnChild = true)
    {
        $data = self::getId($scope, true);
        $result = array(
            'deptid' => '',
            'positionid' => '',
            'roleid' => '',
            'uid' => '',
        );
        if (isset($data['c'])) {
            $result['deptid'] = 'alldept';
            return $result;
        }
        if (isset($data['d'])) {
            $deptidString = implode(',', $data['d']);
            if (true === $returnChild) {
                $deptidString = Department::model()->fetchChildIdByDeptids($deptidString, true);
            }
            $result['deptid'] = $deptidString;
        }
        if (isset($data['p'])) {
            $result['positionid'] = implode(',', $data['p']);
        }
        if (isset($data['u'])) {
            $result['uid'] = implode(',', $data['u']);
        }
        if (isset($data['r'])) {
            $result['roleid'] = implode(',', $data['r']);
        }
        return $result;
    }

    /**
     * 解决unserialize的中文编码问题
     * @param string $str
     * @return array | boolean
     */
    public static function utf8Unserialize($str)
    {
        if (is_array($str)) {
            return false;
        }
        $arr = @unserialize($str);
        if (false === $arr) {
            $after = preg_replace_callback('!s:(\d+):"(.*?)";!s', function ($matches) {
                return 's:' . strlen($matches[2]) . ':"' . $matches[2] . '";';
            }, $str);
            return @unserialize($after);
        } else {
            return $arr;
        }
    }

    /**
     * 获取文件大小，传入数组或者逗号字符串
     * @param mixed $sizeMixed 文件大小
     * @return array
     */
    public static function ConvertBytes($sizeMixed)
    {
        $sizeArray = is_array($sizeMixed) ? $sizeMixed : explode(',', $sizeMixed);
        return array_map(function ($temp) {
            return eval("return " .
                preg_replace('/pb?/i', ' * 1024 * 1024 * 1024 * 1024 * 1024 '
                    , preg_replace('/tb?/i', ' * 1024 * 1024 * 1024 * 1024 '
                        , preg_replace('/gb?/i', ' * 1024 * 1024 * 1024 '
                            , preg_replace('/mb?/i', ' * 1024 * 1024 '
                                , preg_replace('/kb?/i', ' * 1024 '
                                    , preg_replace('/b?/i', '', $temp))))))
                . ";");
        }, $sizeArray);
    }

    /**
     * 删除字符串中的 emoji 表情
     *
     * @param $text
     * @return string
     */
    public static function removeEmoji($text)
    {
        $clean_text = "";

        // Match Emoticons
        $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
        $clean_text = preg_replace($regexEmoticons, '', $text);

        // Match Miscellaneous Symbols and Pictographs
        $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
        $clean_text = preg_replace($regexSymbols, '', $clean_text);

        // Match Transport And Map Symbols
        $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
        $clean_text = preg_replace($regexTransport, '', $clean_text);

        // Match Miscellaneous Symbols
        $regexMisc = '/[\x{2600}-\x{26FF}]/u';
        $clean_text = preg_replace($regexMisc, '', $clean_text);

        // Match Dingbats
        $regexDingbats = '/[\x{2700}-\x{27BF}]/u';
        $clean_text = preg_replace($regexDingbats, '', $clean_text);

        return $clean_text;
    }

    /**
     * 判断所给文件是否图片文件
     *
     * @param string $filename
     * @return boolean
     */
    public static function isImageFile($filename)
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        static $imageExt = array('jpg', 'jpeg', 'gif', 'png', 'bmp');
        return in_array($extension, $imageExt) ? true : false;
    }

    /**
     * 过滤不安全的 HTML 代码
     *
     * @param string $text
     * @param array $options
     * @return string
     */
    public static function purify($text, $options = array())
    {
        $purifier = new \CHtmlPurifier();
        $purifier->setOptions($options);

        return $purifier->purify($text);
    }

    /**
     * 获取系统指定编码的子字符串。首先尝试使用 mbstring，然后是 iconv，最后使用 strlen
     *
     * @param string $pValue
     * @param int $pStart
     * @param int $pLength
     * @return mixed
     */
    public static function subString($pValue = '', $pStart = 0, $pLength = 0)
    {
        $charset = Ibos::app()->charset;

        if (function_exists('mb_substr')) {
            return mb_substr($pValue, $pStart, $pLength, $charset);
        }

        if (function_exists('iconv_substr')) {
            return iconv_substr($pValue, $pStart, $pLength, $charset);
        }

        // else substr
        return substr($pValue, $pStart, $pLength);
    }

    /**
     * Get character count. First try mbstring, then iconv, finally strlen
     *
     * @param string $value
     * @return int Character count
     */
    public static function strLength($value)
    {
        $charset = Ibos::app()->charset;

        if (function_exists('mb_strlen')) {
            return mb_strlen($value, $charset);
        }

        if (function_exists('iconv_strlen')) {
            return iconv_strlen($value, $charset);
        }

        // else strlen
        return strlen($value);
    }

    /**
     * 生成一个唯一 id
     *
     * @return string
     */
    public static function generateUniqueId()
    {
        $token = sha1(uniqid(mt_rand(), true));
        return $token;
    }

    /**
     * 生成 form_token，并将其保存在 session 中
     *
     * @return string
     */
    public static function generateFormToken()
    {
        $token = self::generateUniqueId();
        Ibos::app()->session->add(self::FORM_TOKEN_NAME, $token);
        return $token;
    }

    /**
     * 将 form_token 保存在 input 标题中，并返回
     *
     * @return mixed
     */
    public static function returnFormTokenInput()
    {
        $token = self::generateFormToken();
        $tokenInput = sprintf('<input type="hidden" name="formtoken" value="%s" />', $token);
        return $tokenInput;
    }

    /**
     * 验证用户提供的 form_token 是否准确
     *
     * @param $formToken
     * @return bool
     */
    public static function checkFormToken($formToken)
    {
        $token = Ibos::app()->session->get(self::FORM_TOKEN_NAME);
        if (!empty($token) && $token == $formToken) {
            Ibos::app()->session->remove(self::FORM_TOKEN_NAME);
            return true;
        }

        return false;
    }

    /**
     * 将字符串转换为 JSON 格式
     *
     * @todo Yii 内置的 CJSON::encode 兼容性更好，而 PHP 内置的 json_encode 性能更好；
     *
     * @see http://www.yiichina.com/question/990
     * @param string $text 需要 JSON 编码的字符串
     * @return string 格式化后的 JSON 字符串
     */
    public static function jsonEncode($text)
    {
        $strType = gettype($text);

        if ($strType == 'object') {
            $jsonData = \CJSON::encode($text);
        } else {
            $jsonData = json_encode($text);
        }

        return $jsonData;
    }
    
    /**
     * 生成 where in 条件
     *
     * @param string $columnName 字段名称，最好使用 `xxx` 这种格式
     * @param array $valueArr
     * @return string
     */
    public static function generateInCondition($columnName, array $valueArr)
    {
        $ids = implode("','", $valueArr);
        $condition = sprintf("%s IN ('%s')", $columnName, $ids);

        return $condition;
    }

    /**
     * 同时返回除法和取余结果，除法结果只保留整数
     * div 指的是：division（除法），mod 指的是：modulo（模运算）
     * Example：10 / 3 return array(3, 1)
     *
     * @param integer|float $num1
     * @param integer|float $num2
     * @return array array('除法结果', '取余结果')
     */
    public static function calcDivAndMod($num1, $num2)
    {
        return array(
            (int)$num1 / $num2,
            $num1 % $num2,
        );
    }

    /**
     * 计算开始时间和结束时间的相差时间，并输出可读性较高的字符串。
     * Example：
     * diffTimeForHuman(0, 100, false) return 1 分钟
     * diffTimeForHuman(0, 100, true) return 1 分钟 40 秒
     *
     * @param integer $startTime 开始时间
     * @param integer $endTime 结束时间
     * @param bool $detail 是否返回详细数据。
     * @return string
     */
    public static function diffTimeForHuman($startTime, $endTime, $detail = false)
    {
        // 非法时间
        if (($endTime - $startTime) <= 0) {
            return '';
        }

        // 常用时间：这里的 S 指的是 Second，秒的意思
        $minuteS = 60;
        $hourS = $minuteS * 60;
        $dayS = $hourS * 24;

        $diffTime = $endTime - $startTime;
        $modulo = 0;

        if ($diffTime >= $dayS) {
            // 相差时间大于等于一天
            list($division, $modulo) = self::calcDivAndMod($diffTime, $dayS);
            $readableTimeStr = sprintf('%d %s', $division, Ibos::lang('Day', 'date'));
        } elseif ($diffTime >= $hourS) {
            // 相差时间大于一个小时
            list($division, $modulo) = self::calcDivAndMod($diffTime, $hourS);
            $readableTimeStr = sprintf('%d %s', $division, Ibos::lang('Hour', 'date'));
        } elseif ($diffTime >= $minuteS) {
            // 相差时间大于一分钟
            list($division, $modulo) = self::calcDivAndMod($diffTime, $minuteS);
            $readableTimeStr = sprintf('%d %s', $division, Ibos::lang('Min', 'date'));
        } else {
            // 相差时间大于一秒
            $readableTimeStr = sprintf('%d %s', $diffTime, Ibos::lang('Sec', 'date'));
        }

        if ($detail === true && $modulo != 0) {
            $readableTimeStr .= sprintf(' %s', self::diffTimeForHuman(0, $modulo, true));
        }

        return $readableTimeStr;
    }

    /**
     * 过滤SQL注入特殊字符串和符号
     * @param $valor
     * @return string
     */
    public static function SQLfilter($valor)
    {
        $valor = str_ireplace("SELECT","",$valor);
        $valor = str_ireplace("COPY","",$valor);
        $valor = str_ireplace("DELETE","",$valor);
        $valor = str_ireplace("DROP","",$valor);
        $valor = str_ireplace("DUMP","",$valor);
        $valor = str_ireplace(" OR ","",$valor);
        $valor = str_ireplace("%","",$valor);
        $valor = str_ireplace("LIKE","",$valor);
        $valor = str_ireplace("--","",$valor);
        $valor = str_ireplace("^","",$valor);
        $valor = str_ireplace("[","",$valor);
        $valor = str_ireplace("]","",$valor);
        $valor = str_ireplace("\\","",$valor);
        $valor = str_ireplace("!","",$valor);
        $valor = str_ireplace("¡","",$valor);
        $valor = str_ireplace("?","",$valor);
        $valor = str_ireplace("=","",$valor);
        $valor = str_ireplace("&","",$valor);
        $valor = str_ireplace("#","",$valor);
        $valor = str_ireplace("'","",$valor);
        return $valor;
    }

    /**
     * 过滤XSS
     * @param $data
     * @return string
     */
    public static function xssFilter($data)
    {
        // Fix &entity\n;
        $data = str_replace(array('&amp;', '&lt;', '&gt;'), array('&amp;amp;', '&amp;lt;', '&amp;gt;'), $data);
        $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
        $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
        $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');
        // Remove any attribute starting with "on" or xmlns
        $data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);
        // Remove javascript: and vbscript: protocols
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);
        // Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);
        // Remove namespaced elements (we do not need them)
        $data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);
        do {
            // Remove really unwanted tags
            $old_data = $data;
            $data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
        } while ($old_data !== $data);
        // we are done...
        return $data;
    }

    /**
     *校验日期格式是否正确
     * @param string $date 日期
     * @param string $conStr 日期间隔字符串
     * @return bool   true 正确日期格式  false 不正确
     */
    public static function checkDateIsValid($date,$conStr = '-')
    {
        $unixTime = strtotime($date);
        //strtotime转换不对，日期格式显然不对。
        if (!$unixTime) {
            return false;
        }
        list($y, $m, $d) = explode($conStr, $date);
        //校验日期的有效性，只要满足其中一个格式就OK
        if (checkdate($m, $d, $y)) {
            return true;
        }
        return false;
    }

    /**
     * utf8字符转换成Unicode字符
     * @param [type] $utf8_str Utf-8字符
     * @return [type]      Unicode字符
     */
    public static function  utf8ToUnicode($utf8_str) {
        $unicode = 0;
        $unicode = (ord($utf8_str[0]) & 0x1F) << 12;
        $unicode |= (ord($utf8_str[1]) & 0x3F) << 6;
        $unicode |= (ord($utf8_str[2]) & 0x3F);
        return dechex($unicode);
    }

    /**
     * Unicode字符转换成utf8字符
     * @param [type] $unicode_str Unicode字符
     * @return [type]       Utf-8字符
     */
    public static function UnicodeToUtf8($unicode_str) {
        $utf8_str = '';
        $code = intval(hexdec($unicode_str));
        //这里注意转换出来的code一定得是整形，这样才会正确的按位操作
        $ord_1 = decbin(0xe0 | ($code >> 12));
        $ord_2 = decbin(0x80 | (($code >> 6) & 0x3f));
        $ord_3 = decbin(0x80 | ($code & 0x3f));
        $utf8_str = chr(bindec($ord_1)) . chr(bindec($ord_2)) . chr(bindec($ord_3));
        return $utf8_str;
    }
}
