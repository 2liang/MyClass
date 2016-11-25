<?php
/**
 * 基础参数校验类
 * Created by PhpStorm.
 * User: 2liang
 * Date: 16/11/23
 * Time: 下午8:15
 */

class Validation {

    static public $method = array();

    /**
     * 校验方法
     * $rule = array(
     *      'username' => array(
     *          'length' => array(array(null, 10), '长度应该小于10位'),
     *          'email' => array(null, '字符串不是邮箱'),
     *          'or' => array(
     *              'length' => array(array(null, 10), '长度应该小于10位'),
     *              'qq' => array(null, '不是qq'),
     *          ),
     *      ),
     *      'password' => array(
     *          'equal' => array('repassword', '两次密码不一致')
     *      ),
     *      'remark' => '该参数必填'
     * );
     * TODO:凡是在rule中出现的必须存在,目前未处理可空的情况
     * @param array $rule
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    static public function valid(array $rule, array $data)
    {
        foreach($rule as $key => $value) {
            if (is_string($value)) {    // value为字符串 仅判断是否为空
                if (empty($data[$key])) {
                    return $value;
                }
            } else {
                // 查看是否有or
                $funcKey = array_keys($value);
                if (!in_array('or', $funcKey)) {
                    foreach ($value as $func => $param) {
                        // 判断参数是否存在
                        if (empty($data[$key])) {
                            return $param[1];
                        }
                        // 判断该规则函数是否存在
                        if (!in_array($func, self::classMethods())) {
                            throw new \Exception('校验规则[' . $func . ']' . '不存在');
                        }
                        if (!is_string($param[1])) {
                            throw new \Exception('错误信息必须为字符串');
                        }
                        if (empty($param[0])) { // 无其他参数情况
                            if (!call_user_func(array(__CLASS__, $func), $data[$key])) {
                                return $param[1];
                            }
                        } else {    // 有参数
                            if (!is_array($param[0])) { // 单参数
                                $params = array($data[$key], $param[0]);
                            } else {    // 多参数
                                $params = array_merge(array($data[$key]), $param[0]);
                            }
                            if (!call_user_func_array(array(__CLASS__, $func), $params)) {
                                return $param[1];
                            }
                        }
                    }
                } else {
                    // TODO: 暂时未考虑or的情况
                    throw new \Exception('该类暂未实现OR');
                }
            }
        }
        return true;
    }

    /**
     * 获取当前类的所有方法
     * @return array
     */
    static public function classMethods()
    {
        if (!empty(self::$method)) {
            return self::$method;
        }
        return get_class_methods(__CLASS__);
    }

    /**
     * 判断字符串长度
     * 当仅传输start则为大于等于该长度
     * 当仅传输end则为小于等于该长度包括0
     * 如果两者均传输,则为介于两者之间包括两者
     * @param string $data
     * @param int $start 最少
     * @param int $end 最长
     * @return boolean
     */
    static public function length($data = '', $start = null, $end = null)
    {
        if ($start !== null && $end !== null) {
            return strlen($data) >= $start && strlen($data) <= $end;
        }

        if ($start !== null && $end === null) {
            return strlen($data) >= $start;
        }

        if ($start === null && $end !== null) {
            return strlen($data) <= $end;
        }
        return false;
    }

    /**
     * 校验该参数值$data是否存在与house中
     * @param string $data
     * @param string $house 'edit,add'
     * @return bool
     */
    static public function exist($data = '', $house ='')
    {
        $house = explode(',', $house);
        // 容错 去掉数据两边的空格
        $params = array();
        foreach($house as $item) {
            $params[] = trim($item);
        }
        return in_array($data, $params) ? true : false;
    }

    /**
     * 校验是否是email
     * @param string $data
     * @return bool|int
     */
    static public function email($data = '')
    {
        if (empty($data)) {
            return false;
        }
        return preg_match('#^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$#i', $data);
    }

    /**
     * 校验是否是url
     * @param string $data
     * @return bool|int
     */
    static public function url($data = '')
    {
        if (empty($data)) {
            return false;
        }
        return preg_match('#^(http|https)://([A-Z0-9][A-Z0-9_-]*(?:.[A-Z0-9][A-Z0-9_-]*)+):?(d+)?/?#i', $data);
    }

    /**
     * 校验是否是手机号
     * @param string $data
     * @return bool|int
     */
    static public function phone($data = '')
    {
        if (empty($data)) {
            return false;
        }
        return preg_match('/^1[34578]\d{9}$/', $data);
    }

    /**
     * 自定义正则校验
     * @param string $data
     * @param string $reg
     * @return bool|int
     */
    static public function regular($data = '', $reg = '')
    {
        if (empty($data) || empty($reg)) {
            return false;
        }
        return preg_match($reg, $data);
    }
}
