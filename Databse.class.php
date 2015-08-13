<?php
// +----------------------------------------------------------------------
// | Database [ Mr.2's Database Class ]
// +----------------------------------------------------------------------
// | Author: 我才是二亮 <unstring@163.com>
// +----------------------------------------------------------------------
// | Link  : www.2liang.me
// +----------------------------------------------------------------------
class Database {

	private $host;
	private $user;
	private $pass;
	private $db;
	private $port;
	private $link;
	public  $error;
	public  $errno;

	public function __construct($host = '', $user = '', $pass = '', $db = '', $port = '3306') {
		
		$this->host = $host;
		$this->user = $user;
		$this->pass = $pass;
		$this->db   = $db;
		$this->post = $port;
		
		$this->link = mysql_connect($host . ':' . $port, $user, $pass);
		mysql_select_db($db, $this->link);
	}

	/**
	 * 获取结果集 可自定义任何参数
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function get($data) {

		if(!is_array($data)) {
			$this->error = '请使用合法数据格式';
			return false;
		}
		$sql = '';
		if(array_key_exists('filed', $data)) {
			if(is_array($data)) {
				$filed = $this->parseArray($data['filed'], 'filed');
			} else {
				$filed = $data['filed'];
			}
		} else {
			$filed = '*';
		}
		$sql .= "SELECT $filed FROM ";
		if(array_key_exists('table', $data)) {
			$table = $data['table'];
			$sql .= "$table ";
		} else {
			$this->error = '未指定数据表';
			return false;
		}

		if(array_key_exists('where', $data)) {
			$where = $data['where'];
			if(is_array($where)) {
				$where = $this->parseArray($where, 'where');
			} 
			$sql .= "WHERE $where ";
		}

		if(array_key_exists('order', $data)) {
			$order = $data['order'];
			$sql .= "ORDER BY $order ";
		}

		if(array_key_exists('limit', $data)) {
			$limit = $data['limit'];
			$sql .= "LIMIT $limit ";
		}

		$result = $this->select($sql);
		return $result;
	}

	/**
	 * 执行select原生Sql语句并返回结果集
	 * @param  string $sql sql语句
	 * @return [type]      [description]
	 */
	public function select($sql) {
		if(empty($sql)) {
			return false;
		}
		$query = mysql_query($sql, $this->link);
		if(!$query) {
			$this->errno = mysql_errno();
			$this->error = mysql_error();
			return false;
		}
		$result = array();
		while($data = mysql_fetch_assoc($query)) {
			$result[] = $data;
		}
		if(empty($result)) {
			return null;
		}
		return $result;
	}

	/**
	 * 执行一条sql,返回数据库句柄
	 * @param  [type] $sql [description]
	 * @return [type]      [description]
	 */
	public function query($sql) {
		if(empty($sql)) {
			return false;
		}
		$query = mysql_query($sql, $this->link);
		return $query;
	}

	/**
	 * 获取上一条query操作影响的记录行数
	 * @return int 影响的记录行数
	 */
	public function count() {

		$count = mysql_affected_rows($this->link);
		return $count;
	}

	/**
	 * 插入数据
	 * @param  string $table 表名
	 * @param  array  $data  插入数据
	 * @return Boolean       成功返回true,失败返回false
	 * @stauts Successful
	 */
	public function insert($table = '', $data = '') {

		if(empty($table)) {
			$this->error = '未指定数据表';
			return false;
		}

		if(empty($data) || !is_array($data)) {
			$this->error = '请插入合法数据';
			return false;
		}
		$data = $this->parseArray($data, 'data');
		$keys = $data[0];
		$values = $data[1];
		$sql = "INSERT INTO $table $keys VALUES $values";
		$result = $this->query($sql);
		if(!$result) {
			return false;
		}
		return true;
	}

	/**
	 * 更新数据
	 * @param  string $table 表名
	 * @param  array  $data  更新数据
	 * @param  array  $where 更新条件
	 * @return Boolean       成功返回true 失败返回false
	 * @status 				 successful
	 */
	public function update($table = '', $data = '', $where = '') {

		if(empty($table)) {
			$this->error = '未指定数据表';
			return false;
		}

		if(empty($data) || (array_keys($data) === range(0, count($data) - 1))) {
			$this->error = '请插入合法数据';
			return false;
		}

		if(is_array($data)) {	// 对数据进行解析
			$data = $this->parseArray($data, 'update');
		}

		if(empty($where)) {
			$where = '1';
		}

		if(is_array($where)) {
			$where = $this->parseArray($where, 'where');
		}

		$sql = "UPDATE $table SET $data WHERE $where";
		$result = $this->query($sql);
		if(!$result) {
			return false;
		}
		return true;
	}

	/**
	 * 删除数据
	 * @param  string $table 表名
	 * @param  [type] $where 更新条件 可字符串 可数组
	 * @return [type]        成功true 失败false
	 */
	public function delete($table = '', $where = '') {

		if(empty($table)) {
			$this->error = '未指定数据表';
			return false;
		}

		if(empty($where)) {
			$this->error = '条件不能为空';
			return false;
		}

		if(is_array($where)) {
			$where = $this->parseArray($where, 'where');
		}
		$sql = "DELETE FROM $table WHERE $where";
		$result = $this->query($sql);
		if(!$result) {
			return false;
		}
		return true;
	}

	/**
	 * 解析数组
	 * @param  array  $data 需要解析的数组
	 * @param  string $type 解析数组的来源
	 * @return string       解析成功的返回值
	 */
	public function parseArray($data, $type) {

		if(empty($data) || !is_array($data)) {
			return false;
		} 
		if(empty($type)) {
			return false;
		}
		$str = '';
		switch ($type) {
			case 'where':
				if(array_key_exists('_complex', $where)) {
			        $complex = parse($where['_complex'], $where['_logic']);
			    }
			    unset($where['_complex']);
			    unset($where['_logic']);
			    $str = parse($where) . " AND " . $complex;
			    break;
			
			case 'filed':
				$str = implode(',', $data);
				break;

			case 'data':
				if(array_keys($data) !== range(0, count($data) - 1)) { // 一组数据
					$str .= '(';
					$keys = '(';
					foreach ($data as $key => $value) {
						if(!is_numeric($value)) {
							$str .= "'$value', ";
						} else {
							$str .= "$value, "; 								
						}
						$keys .= "$key, ";
					}
					$str = substr($str, 0, -2);
					$keys = substr($keys, 0, -2);
					$str .= ')';
					$keys .= ')';
				} else {	// 多组数据
					$flag = 1;
					$keys = '(';
					foreach ($data as $temp) {
						$str .= '(';
						foreach ($temp as $key => $value) {
							if(!is_numeric($value)) {
								$str .= "'$value', ";
							} else {
								$str .= "$value, "; 								
							}
							if($flag == 1) {
								$keys .= "$key, ";
							}
						}

						$str = substr($str, 0, -2);
						$str .= '), ';
						if($flag == 1) {
							$keys = substr($keys, 0, -2);
							$keys .= ')';
							$flag++;
						}
					}
					$str = substr($str, 0, -2);
				}
				return array($keys, $str);
				break;

			case 'update':
				foreach ($data as $key => $value) {
					if(is_numeric($value)) {
						$str .= "$key = $value, ";
					} else {
						$str .= "$key = '$value', ";
					}
				}
				$str = substr($str, 0, -2);
				break;
			default:
				return false;
				break;
		}

		return $str;
	}

	/**
	 * 解析条件语句
	 * @param  array  $data     条件语句数组
	 * @param  string $relation 条件语句逻辑关系
	 * @return string           返回条件语句
	 */
	public function parseWhere($data, $relation = '') {

		$str = '(';
	    if(empty($relation)) {
	        $relation = 'AND';
	    }
	    foreach ($where as $key => $value) {
	        $sign = $value[0];
	        $where = $value[1];
	        if($sign == 'in') {
	            
	            $where = implode("','", $where);
	            $str .= "$key in ('$where') $relation ";
	        } else {
	            if(is_numeric($where)) {
	                $str .= "$key $sign $where $relation ";
	            } else {
	                $str .= "$key $sign '$where' $relation ";
	            }
	            
	        }
	    }
	    $str = substr($str, 0, -4);
	    $str .= ')';
	    return $str;
	}
}