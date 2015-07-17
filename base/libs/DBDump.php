<?php
/**

ini_set ("display_errors", "1");
error_reporting(E_ALL);
set_time_limit(3000);
//dump database
$dump = new DBDump();
$dump->dumpAll();
//import database
$dump = new DBDump();
$dump->importAll();


**/
class DBDump {
	var $srcServer = '';
	var $srcUser = '';
	var $srcPass = '';
	var $srcDB = '';
	var $srcDumpPath = '';
	
	var $tgtServer = '';
	var $tgtUser = '';
	var $tgtPass = '';
	var $tgtDB = '';
	var $tgtDumpPath = '';
	
	var $conn;
	
	var $tableList;
	
	var $pageLimit = 0;
	var $page = 1000;
	
	var $importing = false;
	
	function connect() {
		if($this->importing === true) {
			$this->conn = mysql_connect($this->tgtServer, $this->tgtUser, $this->tgtPass);
			mysql_select_db($this->tgtDB);
		} else {
			$this->conn = mysql_connect($this->srcServer, $this->srcUser, $this->srcPass);
			mysql_select_db($this->srcDB);
		}
		
		mysql_query("set character_set_client = utf8");
		mysql_query("set character_set_connection= utf8");
		mysql_query("set character_set_results = utf8");
		mysql_query("set character_name = utf8");

	}
	
	function getTables() {
		$this->tableList = array();
		if($this->importing === true) {
			
			$filename = $this->tgtDumpPath . 'table-list.txt';
			$fileContent = File($filename);
			$rows = count($fileContent);
			for ($i=0;$i<$rows;$i++) {
				$row = trim($fileContent[$i]);
				$rowArr = explode(';',$row);
				$this->tableList[] = $rowArr[0];
			}

		} else {
			
			$tables = mysql_query('SHOW TABLES from '.$this->srcDB);
			while ($td = mysql_fetch_array($tables)) {
				$this->tableList[] = $td[0];
			}
			
			$filename = $this->srcDumpPath . 'table-list.txt';
			if(file_exists($filename)) {
				unlink($filename);
			}
			$fh = fopen($filename,'a');
			foreach($this->tableList as $table) {
				fwrite($fh,"".$table.';0;0;0'."\n");
			}
			fclose($fh);
			
		}
	}
	
	function updateStatus($table,$completed,$total=0) {
		if($this->importing === true) {
			$filename = $this->tgtDumpPath . 'table-list.txt';
		} else {
			$filename = $this->srcDumpPath . 'table-list.txt';		
		}
		$fileContent = File($filename);
		$rows = count($fileContent);
		for ($i=0;$i<$rows;$i++) {
			$row = trim($fileContent[$i]);
			$rowArr = explode(';',$row);
			if($table==$rowArr[0]) {
				if($total>0) $rowArr[1] = $total;
				if($this->importing===true) {
					$rowArr[3] = $completed;
				} else {
					$rowArr[2] = $completed;
				}
				$row = implode(';',$rowArr);
			}
			$fileContent[$i] = $row;
		}
		$data = implode("\n",$fileContent);
		$fh = fopen($filename,'w');
		fwrite($fh,$data);
		fclose($fh);
	}
	
	function dumpTable($tableName) {
		$ret = false;
		$filename = $this->srcDumpPath . $tableName .'.sql';
		if(file_exists($filename)) {
			unlink($filename);
		}
		
		$countRes = mysql_query('select count(1) from '.$tableName);
		$countRow = mysql_fetch_row($countRes);
		$numRows = $countRow[0];
		$this->updateStatus($tableName,0,$numRows);
		if($numRows > 0) {
			$ret = true;
			$currentRow = 0;
			$num_fields = 0;
			$rowsDone = 0; 
			
			$fh = fopen($filename,'a');
			
			while($currentRow*$this->page <= $numRows) {
				
				$tables = mysql_query('select * from '.$tableName.' limit '.$currentRow*$this->page.','.$this->page);
				$currentRow++;
				
				while ($row = mysql_fetch_row($tables)) {
					//prepare
					if($num_fields==0) $num_fields = count($row);
					for ($i=0;$i<$num_fields;$i++) {
						$row[$i] = "'".mysql_real_escape_string(trim($row[$i]))."'";
					}
					
					//save
					fwrite($fh,"".implode(',',$row)."\n");
					$rowsDone++; 
				}
				
				$this->updateStatus($tableName,$rowsDone);
				
			}
			
			fclose($fh);
			
		}

		return $ret;
	}
	
	function importTable($tableName) {
		$ret = false;
		$filename = $this->tgtDumpPath . $tableName .'.sql';
		
		$rowsDone = 0;
		
		if(file_exists($filename)) {
			$fh = fopen($filename,'r');
			
			mysql_query('truncate '.$tableName);
			
			while (!feof($fh)) {
	        $buffer = fgets($fh);
	        $buffer = trim($buffer);
	        if(!empty($buffer)) {
		        $q = 'insert into '.$tableName.' values ('.trim($buffer).');';
		        mysql_query($q);
		        $rowsDone++;
	        }
	    }
			
			$this->updateStatus($tableName,$rowsDone);
			
			fclose($fh);
			$ret = true;
		}
		return $ret;		
	}
	
	function dumpAll() {
		$this->importing = false;
		$this->connect();
		$this->getTables();
		foreach($this->tableList as $table) {
			if($this->dumpTable($table)) {
				echo 'Table done: '.$table."<br />\n";
			} else {
	  		echo 'Table fail: '.$table."<br />\n";
			}
		}
		echo 'Dump done';
	}
	
	function importAll() {
		$this->importing = true;
		$this->connect();
		$this->getTables();
		foreach($this->tableList as $table) {
			if($this->importTable($table)) {
				echo 'Table done: '.$table."<br />\n";
			} else {
	  		echo 'Table fail: '.$table."<br />\n";
			}
		}
		echo 'Dump done';
	}
		
}


