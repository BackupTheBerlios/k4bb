<?php
define('DBA', 'MySQL');
class Blogger {
	
	protected $dba = '';
	
	public function __construct() {
		if(!$connection = DBA)
			exit('damn');

		$dba = new $connection;
		$dba->Connect('x', 'x', 'x', 'x');
	}
}
interface DBA {

	const E_INVALID_SERVER	= 'Unable to connect to database server';
	const E_INVALID_DB		= 'Unable to select database';
	const E_INVALID_QUERY	= 'Invalid query';
	const E_INVALID_TYPE	= 'Invalid result type';

	public function Connect($db, $host, $user, $pass);
	public function GetRow();
	public function GetNumRows();
	public function GetRows();
}
class MySQL implements DBA {
	public function Connect($db, $host, $user, $pass) {
		echo 'heya';
	}
	public function GetRow() {

	}
	public function GetNumRows() {

	}
	public function GetRows() {

	}
}
$blogger = new Blogger;

?>