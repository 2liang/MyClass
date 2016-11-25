<?php 
/**
 * 依赖注入
 * @author unstring@163.com
 * @since 2016-11-25
 */
class DI implements ArrayAccess {

	private static $_binding = array();		// 服务列表

	private static $_instances = array();	// 已经实例化的服务

	/**
	 * 获取服务
	 * @return [type] [description]
	 */
	static public function get($name)
	{
		// 首先从实例化的服务中寻找
		if (isset(self::$_instances[$name])) {
			return $_instances[$name];
		}

		// 检测有没有注册该服务
		if (!isset(self::$_binding[$name])) {
			$msg = "<<".$name.">> must be set";
			trigger_error($msg);
			return null;
		}

		$concrete = self::$_binding[$name]['class'];
		$param = self::$_binding[$name]['param'];
		$obj = null;

		// 匿名函数
		if ($concrete instanceof \Closure) {
			$obj = call_user_func_array($concrete, $param);
		} else if (is_string($concrete)) {	// 如果是字符串
			if (empty($param)) {	// 参数为空 意味着实例化不需要参数直接实例化
				$obj = new $concrete;
			} else {	// 当有参数时，使用反射，原因在于当实例化需要多个参数时，无法直接实例化(不能提前预知到底有多少个参数)
				$class = new ReflectionClass($concrete);
				$obj = $class->newInstanceArgs($param);
			}
		}

		// 如果是共享服务 直接实例化 下次直接获取
		if (self::$_binding[$name]['shared']) {
			$_instances[$name] = $obj;
		}

		return $obj;
	}

	/**
	 * 检测是否已经绑定
	 * @param  [type]  $name [description]
	 * @return boolean       [description]
	 */
	static public function has($name)
	{
		return isset(self::$_binding[$name]) or isset(self::$_instances[$name]);
	}

	/**
	 * 移除服务
	 * @param  [type] $name [description]
	 * @return [type]       [description]
	 */
	static public function remove($name)
	{
		unset(self::$_binding[$name], self::$_instances[$name]);
		return true;
	}

	/**
	 * 设置服务
	 * @param [type] $name  [description]
	 * @param [type] $class [description]
	 * @param array  $param [description]
	 */
	static public function set($name, $class, $param = array())
	{
		self::_registerService($name, $class, $param);
		return true;
	}

	/**
	 * 设置共享服务
	 * @param [type] $name  [description]
	 * @param [type] $class [description]
	 * @param array  $param [description]
	 */
	static public function setShared($name, $class, $param = array())
	{
		self::_registerService($name, $claas, $param, true);
	}

	/**
	 * 注册服务
	 * @param  [type]  $name   [description]
	 * @param  [type]  $class  [description]
	 * @param  array   $param  [description]
	 * @param  boolean $shared [description]
	 * @return [type]          [description]
	 */
	static private function _registerService($name, $class, $param = array(), $shared = false)
	{
		// 首先删除服务
		self::remove($name);
		if (!($class instanceof \Closure) && is_object($class)) {
			self::$_instances[$name] = $class;
		} else {
			self::$_binding[$name] = array('class' => $class, 'param' => $param, 'shared' => $shared);
		}
	}

	/**
	 * ArrayAccess接口，检测服务是否存在
	 * @param  [type] $offset [description]
	 * @return [type]         [description]
	 */
	public function offsetExists($offset)
	{
		return self::has($offset);
	}

	/**
	 * ArrayAccess接口，以$di[$name]方式获取服务
	 * @param  [type] $offset [description]
	 * @return [type]         [description]
	 */
	public function offsetGet($offset)
	{
		return self::get($offset);
	}

	/**
	 * ArrayAccess接口,以$di[$name]=$value方式注册服务，非共享
	 * @param  [type] $offset [description]
	 * @param  [type] $value  [description]
	 * @return [type]         [description]
	 */
	public function offsetSet($offset, $value)
	{
		return self::set($offset, $value);
	}

	/**
	 * ArrayAccess接口，以unset($di[$name])方式卸载服务
	 * @param  [type] $offset [description]
	 * @return [type]         [description]
	 */
	public function offsetUnset($offset)
	{
		return self::remove($offset);
	}
}
