<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.database.driver.pdo.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa\database\driver;

use boa\boa;
use boa\msg;

class pdo{
	private $cfg = [
		'type' => 'mysql',
		'charset' => 'utf8',
		'persist' => false,
		'option' => [],
		'host' => '127.0.0.1',
		'port' => 3306,
		'name' => '',
		'user' => null,
		'pass' => null,
	];
	private $link;
	private $type = [
		'i' => \PDO::PARAM_INT,
		'd' => \PDO::PARAM_STR,
		's' => \PDO::PARAM_STR,
		'b' => \PDO::PARAM_LOB,
		'o' => \PDO::PARAM_BOOL
	];
	private $mode = \PDO::FETCH_ASSOC;
	private $sql;

	public function __construct($cfg){
		if($cfg){
			$this->cfg = array_merge($this->cfg, $cfg);
		}

		$this->cfg['option'][\PDO::ATTR_DEFAULT_FETCH_MODE] = $this->mode;
		$this->cfg['option'][\PDO::ATTR_PERSISTENT] = $this->cfg['persist'];

		$dsn = $this->dsn($this->cfg['type']);
		try{
			$this->link = new \pdo($dsn, $this->cfg['user'], $this->cfg['pass'], $this->cfg['option']);
		}catch(\PDOException $e){
			msg::set('boa.error.101', 'pdo ('. $e->getCode() .')');
		}
	}

	public function execute($sql){
		return $this->link->exec($sql);
	}

	public function query($sql){
		$res = $this->link->query($sql);
		if($res){
			$res = $res->fetchAll();
		}
		$this->sql = $sql;
		return $res;
	}

	public function one($sql){
		$res = $this->link->query($sql);
		if($res){
			$res = $res->fetch();
			if(!$res){
				$res = [];
			}
		}
		return $res;
	}

	public function lastid($name = null){
		return $this->link->lastInsertId($name);
	}

	public function page($sql = null){
		if(!$sql){
			$sql = $this->sql;
			$sql = preg_replace('/select (.+?) from /i', 'SELECT COUNT(*) FROM ', $sql);
			$sql = preg_replace('/ limit [\d]+(\s*,\s*[\d]+)?/i', '', $sql);
			$sql = preg_replace('/ order by (.+) (asc|desc)/i', '', $sql);
		}
		$res = $this->link->query($sql);
		$rs = $res->fetch();
		$num = intval(current($rs));
		return $num;
	}

	public function begin(){
		return $this->link->beginTransaction();
	}

	public function commit(){
		return $this->link->commit();
	}

	public function rollback(){
		return $this->link->rollBack();
	}

	public function prepare($sql){
		return $this->link->prepare($sql);
	}

	public function error(){
		$errno = $this->link->errorCode();
		if($errno && $errno !== '00000'){
			$err = $this->link->errorInfo();
			return '['. $err[1] .']'. $err[2];
		}else{
			return null;
		}
	}

	public function stmt_bind($stmt, $para, $type = ''){
		foreach($para as $i => $v){
			if(is_array($v)){
				if(count($v) > 2){
					$stmt->bindParam($i+1, $v[0], $v[1], $v[2]);
				}else{
					$stmt->bindParam($i+1, $v[0], $v[1]);
				}
			}else{
				if($type){
					$t = substr($type, $i, 1);
					$t = $this->type[$t];
					$stmt->bindParam($i+1, $v, $t);
				}else{
					$stmt->bindParam($i+1, $v);
				}
			}
		}
	}

	public function stmt_one($stmt){
		return $stmt->fetch();
	}

	public function stmt_all($stmt){
		return $stmt->fetchAll();
	}

	public function stmt_lastid($stmt){
		return $this->link->lastInsertId();
	}

	public function stmt_affected($stmt){
		return $stmt->rowCount();
	}

	public function stmt_error($stmt){
		$errno = $stmt->errorCode();
		if($errno && $errno !== '00000'){
			$err = $stmt->errorInfo();
			return '['. $err[1] .']'. $err[2];
		}else{
			return null;
		}
	}

	private function dsn($type){
		$tcp = [
			'mysql' => 'host={host};port={port};dbname={name}',
			'sqlsrv' => 'Server={host},{port};Database={name}',
			'oci' => 'dbname=//{host}:{port}/{name}',
			'pgsql' => 'host={host};port={port};dbname={name}',
			'ibm' => 'DRIVER={IBM DB2 ODBC DRIVER};DATABASE={name};HOSTNAME={host};PORT={port};PROTOCOL=TCPIP',
			'sqlite' => '{name}',
			'sqlite2' => '{name}',
			'odbc' => 'Driver={Microsoft Access Driver (*.mdb)};Dbq={name}',
			'firebird' => 'dbname={host}/{port}:{name}',
			'cubrid' => 'host={host};port={port};dbname={name}',
			'4D' => 'host={host}'
		];
		$socket = [
			'mysql' => 'unix_socket={host};dbname={name}',
			'sqlsrv' => 'Server={host};Database={name}',
			'oci' => 'dbname={name}',
			'ibm' => 'DSN={name}',
			'sqlite' => ':memory:',
			'sqlite2' => ':memory:',
			'odbc' => '{name}',
			'firebird' => 'dbname={name}',
			'4D' => 'host={host}'
		];
		if($this->cfg['port'] == 0){
			$dsn = $socket[$type];
		}else{
			$dsn = $tcp[$type];
		}
		if($dsn){
			$arr = ['host', 'port', 'name'];
			foreach($arr as $key){
				$dsn = str_replace('{'. $key .'}', $this->cfg[$key], $dsn);
			}
			$dsn = $type .':'. $dsn;
			if($this->cfg['charset']){
				$dsn .= ';charset='. $this->cfg['charset'];
			}
		}
		return $dsn;
	}
}
?>