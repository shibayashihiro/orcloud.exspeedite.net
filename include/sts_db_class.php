<?php

// $Id: sts_db_class.php 5122 2023-06-02 16:39:01Z dev $
// Database abstraction class
// 8-31-2012	Duncan

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

if (!defined('MYSQLI_OPT_READ_TIMEOUT')) {
    define ('MYSQLI_OPT_READ_TIMEOUT', 11);
}

require_once( "sts_config.php" );

function db_warning_handler($errno, $errstr) { 
	global $exspeedite_db;
	
	require_once( "sts_email_class.php" );
	$email = sts_email::getInstance($exspeedite_db, false);
	$email->send_alert("sts_db: warning: ".$errno." : ".$errstr, EXT_ERROR_ERROR);
}

class sts_db {
	
	// Fill in these for your DB
	private $hostname	= "aa";
	private $username	= "aa";
	private $password	= "aa";
	private $DBname		= "aa";
	private $port		= "aa";
	private $debug = false;
	private $logging = false;
	private $last_error	= "";
	
	// Profiling
	private $profiling = false;
	private $timer;
	private $time_connect;
	private $time_query;
	
	private $link;
	public $num_rows;
	
	//! SCR# 614 - list of reserved words as of MySQL 8.0
	private $reserved_words = array(
		'ACCESSIBLE',
		'ACCOUNT',
		'ACTION',
		'ACTIVE',
		'ADD',
		'ADMIN',
		'AFTER',
		'AGAINST',
		'AGGREGATE',
		'ALGORITHM',
		'ALL',
		'ALTER',
		'ALWAYS',
		'ANALYSE',
		'ANALYZE',
		'AND',
		'ANY',
		'ARRAY',
		'AS',
		'ASC',
		'ASCII',
		'ASENSITIVE',
		'AT',
		'AUTHORS',
		'AUTOEXTEND_SIZE',
		'AUTO_INCREMENT',
		'AVG',
		'AVG_ROW_LENGTH',
		'BACKUP',
		'BEFORE',
		'BEGIN',
		'BETWEEN',
		'BIGINT',
		'BINARY',
		'BINLOG',
		'BIT',
		'BLOB',
		'BLOCK',
		'BOOL',
		'BOOLEAN',
		'BOTH',
		'BTREE',
		'BUCKETS',
		'BY',
		'BYTE',
		'CACHE',
		'CALL',
		'CASCADE',
		'CASCADED',
		'CASE',
		'CATALOG_NAME',
		'CHAIN',
		'CHANGE',
		'CHANGED',
		'CHANNEL',
		'CHAR',
		'CHARACTER',
		'CHARSET',
		'CHECK',
		'CHECKSUM',
		'CIPHER',
		'CLASS_ORIGIN',
		'CLIENT',
		'CLONE',
		'CLOSE',
		'COALESCE',
		'CODE',
		'COLLATE',
		'COLLATION',
		'COLUMN',
		'COLUMNS',
		'COLUMN_FORMAT',
		'COLUMN_NAME',
		'COMMENT',
		'COMMIT',
		'COMMITTED',
		'COMPACT',
		'COMPLETION',
		'COMPONENT',
		'COMPRESSED',
		'COMPRESSION',
		'CONCURRENT',
		'CONDITION',
		'CONNECTION',
		'CONSISTENT',
		'CONSTRAINT',
		'CONSTRAINT_CATALOG',
		'CONSTRAINT_NAME',
		'CONSTRAINT_SCHEMA',
		'CONTAINS',
		'CONTEXT',
		'CONTINUE',
		'CONTRIBUTORS',
		'CONVERT',
		'CPU',
		'CREATE',
		'CROSS',
		'CUBE',
		'CUME_DIST',
		'CURRENT',
		'CURRENT_DATE',
		'CURRENT_TIME',
		'CURRENT_TIMESTAMP',
		'CURRENT_USER',
		'CURSOR',
		'CURSOR_NAME',
		'DATA',
		'DATABASE',
		'DATABASES',
		'DATAFILE',
		'DATE',
		'DATETIME',
		'DAY',
		'DAY_HOUR',
		'DAY_MICROSECOND',
		'DAY_MINUTE',
		'DAY_SECOND',
		'DEALLOCATE',
		'DEC',
		'DECIMAL',
		'DECLARE',
		'DEFAULT',
		'DEFAULT_AUTH',
		'DEFINER',
		'DEFINITION',
		'DELAYED',
		'DELAY_KEY_WRITE',
		'DELETE',
		'DELETED',
		'DENSE_RANK',
		'DESC',
		'DESCRIBE',
		'DESCRIPTION',
		'DES_KEY_FILE',
		'DETERMINISTIC',
		'DIAGNOSTICS',
		'DIRECTORY',
		'DISABLE',
		'DISCARD',
		'DISK',
		'DISTINCT',
		'DISTINCTROW',
		'DIV',
		'DO',
		'DOUBLE',
		'DROP',
		'DUAL',
		'DUMPFILE',
		'DUPLICATE',
		'DYNAMIC',
		'EACH',
		'ELSE',
		'ELSEIF',
		'EMPTY',
		'ENABLE',
		'ENCLOSED',
		'ENCRYPTION',
		'END',
		'ENDS',
		'ENFORCED',
		'ENGINE',
		'ENGINES',
		'ENUM',
		'ERROR',
		'ERRORS',
		'ESCAPE',
		'ESCAPED',
		'EVENT',
		'EVENTS',
		'EVERY',
		'EXCEPT',
		'EXCHANGE',
		'EXCLUDE',
		'EXECUTE',
		'EXISTS',
		'EXIT',
		'EXPANSION',
		'EXPIRE',
		'EXPLAIN',
		'EXPORT',
		'EXTENDED',
		'EXTENT_SIZE',
		'FAILED_LOGIN_ATTEMPTS',
		'FALSE',
		'FAST',
		'FAULTS',
		'FETCH',
		'FIELDS',
		'FILE',
		'FILE_BLOCK_SIZE',
		'FILTER',
		'FIRST',
		'FIRST_VALUE',
		'FIXED',
		'FLOAT',
		'FLOAT4',
		'FLOAT8',
		'FLUSH',
		'FOLLOWING',
		'FOLLOWS',
		'FOR',
		'FORCE',
		'FOREIGN',
		'FORMAT',
		'FOUND',
		'FRAC_SECOND',
		'FROM',
		'FULL',
		'FULLTEXT',
		'FUNCTION',
		'GENERAL',
		'GENERATED',
		'GEOMCOLLECTION',
		'GEOMETRY',
		'GEOMETRYCOLLECTION',
		'GET',
		'GET_FORMAT',
		'GET_MASTER_PUBLIC_KEY',
		'GLOBAL',
		'GRANT',
		'GRANTS',
		'GROUP',
		'GROUPING',
		'GROUPS',
		'GROUP_REPLICATION',
		'HANDLER',
		'HASH',
		'HAVING',
		'HELP',
		'HIGH_PRIORITY',
		'HISTOGRAM',
		'HISTORY',
		'HOST',
		'HOSTS',
		'HOUR',
		'HOUR_MICROSECOND',
		'HOUR_MINUTE',
		'HOUR_SECOND',
		'IDENTIFIED',
		'IF',
		'IGNORE',
		'IGNORE_SERVER_IDS',
		'IMPORT',
		'IN',
		'INACTIVE',
		'INDEX',
		'INDEXES',
		'INFILE',
		'INITIAL_SIZE',
		'INNER',
		'INNOBASE',
		'INNODB',
		'INOUT',
		'INSENSITIVE',
		'INSERT',
		'INSERT_METHOD',
		'INSTALL',
		'INSTANCE',
		'INT',
		'INT1',
		'INT2',
		'INT3',
		'INT4',
		'INT8',
		'INTEGER',
		'INTERVAL',
		'INTO',
		'INVISIBLE',
		'INVOKER',
		'IO',
		'IO_AFTER_GTIDS',
		'IO_BEFORE_GTIDS',
		'IO_THREAD',
		'IPC',
		'IS',
		'ISOLATION',
		'ISSUER',
		'ITERATE',
		'JOIN',
		'JSON',
		'JSON_TABLE',
		'KEY',
		'KEYS',
		'KEY_BLOCK_SIZE',
		'KILL',
		'LAG',
		'LANGUAGE',
		'LAST',
		'LAST_VALUE',
		'LATERAL',
		'LEAD',
		'LEADING',
		'LEAVE',
		'LEAVES',
		'LEFT',
		'LESS',
		'LEVEL',
		'LIKE',
		'LIMIT',
		'LINEAR',
		'LINES',
		'LINESTRING',
		'LIST',
		'LOAD',
		'LOCAL',
		'LOCALTIME',
		'LOCALTIMESTAMP',
		'LOCK',
		'LOCKED',
		'LOCKS',
		'LOGFILE',
		'LOGS',
		'LONG',
		'LONGBLOB',
		'LONGTEXT',
		'LOOP',
		'LOW_PRIORITY',
		'MASTER',
		'MASTER_AUTO_POSITION',
		'MASTER_BIND',
		'MASTER_COMPRESSION_ALGORITHMS',
		'MASTER_CONNECT_RETRY',
		'MASTER_DELAY',
		'MASTER_HEARTBEAT_PERIOD',
		'MASTER_HOST',
		'MASTER_LOG_FILE',
		'MASTER_LOG_POS',
		'MASTER_PASSWORD',
		'MASTER_PORT',
		'MASTER_PUBLIC_KEY_PATH',
		'MASTER_RETRY_COUNT',
		'MASTER_SERVER_ID',
		'MASTER_SSL',
		'MASTER_SSL_CA',
		'MASTER_SSL_CAPATH',
		'MASTER_SSL_CERT',
		'MASTER_SSL_CIPHER',
		'MASTER_SSL_CRL',
		'MASTER_SSL_CRLPATH',
		'MASTER_SSL_KEY',
		'MASTER_SSL_VERIFY_SERVER_CERT',
		'MASTER_TLS_VERSION',
		'MASTER_USER',
		'MASTER_ZSTD_COMPRESSION_LEVEL',
		'MATCH',
		'MAXVALUE',
		'MAX_CONNECTIONS_PER_HOUR',
		'MAX_QUERIES_PER_HOUR',
		'MAX_ROWS',
		'MAX_SIZE',
		'MAX_UPDATES_PER_HOUR',
		'MAX_USER_CONNECTIONS',
		'MEDIUM',
		'MEDIUMBLOB',
		'MEDIUMINT',
		'MEDIUMTEXT',
		'MEMBER',
		'MEMORY',
		'MERGE',
		'MESSAGE_TEXT',
		'MICROSECOND',
		'MIDDLEINT',
		'MIGRATE',
		'MINUTE',
		'MINUTE_MICROSECOND',
		'MINUTE_SECOND',
		'MIN_ROWS',
		'MOD',
		'MODE',
		'MODIFIES',
		'MODIFY',
		'MONTH',
		'MULTILINESTRING',
		'MULTIPOINT',
		'MULTIPOLYGON',
		'MUTEX',
		'MYSQL_ERRNO',
		'NAME',
		'NAMES',
		'NATIONAL',
		'NATURAL',
		'NCHAR',
		'NDB',
		'NDBCLUSTER',
		'NESTED',
		'NETWORK_NAMESPACE',
		'NEVER',
		'NEW',
		'NEXT',
		'NO',
		'NODEGROUP',
		'NONE',
		'NOT',
		'NOWAIT',
		'NO_WAIT',
		'NO_WRITE_TO_BINLOG',
		'NTH_VALUE',
		'NTILE',
		'NULL',
		'NULLS',
		'NUMBER',
		'NUMERIC',
		'NVARCHAR',
		'OF',
		'OFFSET',
		'OJ',
		'OLD',
		'OLD_PASSWORD',
		'ON',
		'ONE',
		'ONE_SHOT',
		'ONLY',
		'OPEN',
		'OPTIMIZE',
		'OPTIMIZER_COSTS',
		'OPTION',
		'OPTIONAL',
		'OPTIONALLY',
		'OPTIONS',
		'OR',
		'ORDER',
		'ORDINALITY',
		'ORGANIZATION',
		'OTHERS',
		'OUT',
		'OUTER',
		'OUTFILE',
		'OVER',
		'OWNER',
		'PACK_KEYS',
		'PAGE',
		'PARSER',
		'PARSE_GCOL_EXPR',
		'PARTIAL',
		'PARTITION',
		'PARTITIONING',
		'PARTITIONS',
		'PASSWORD',
		'PASSWORD_LOCK_TIME',
		'PATH',
		'PERCENT_RANK',
		'PERSIST',
		'PERSIST_ONLY',
		'PHASE',
		'PLUGIN',
		'PLUGINS',
		'PLUGIN_DIR',
		'POINT',
		'POLYGON',
		'PORT',
		'PRECEDES',
		'PRECEDING',
		'PRECISION',
		'PREPARE',
		'PRESERVE',
		'PREV',
		'PRIMARY',
		'PRIVILEGES',
		'PRIVILEGE_CHECKS_USER',
		'PROCEDURE',
		'PROCESS',
		'PROCESSLIST',
		'PROFILE',
		'PROFILES',
		'PROXY',
		'PURGE',
		'QUARTER',
		'QUERY',
		'QUICK',
		'RANDOM',
		'RANGE',
		'RANK',
		'READ',
		'READS',
		'READ_ONLY',
		'READ_WRITE',
		'REAL',
		'REBUILD',
		'RECOVER',
		'RECURSIVE',
		'REDOFILE',
		'REDO_BUFFER_SIZE',
		'REDUNDANT',
		'REFERENCE',
		'REFERENCES',
		'REGEXP',
		'RELAY',
		'RELAYLOG',
		'RELAY_LOG_FILE',
		'RELAY_LOG_POS',
		'RELAY_THREAD',
		'RELEASE',
		'RELOAD',
		'REMOTE',
		'REMOVE',
		'RENAME',
		'REORGANIZE',
		'REPAIR',
		'REPEAT',
		'REPEATABLE',
		'REPLACE',
		'REPLICATE_DO_DB',
		'REPLICATE_DO_TABLE',
		'REPLICATE_IGNORE_DB',
		'REPLICATE_IGNORE_TABLE',
		'REPLICATE_REWRITE_DB',
		'REPLICATE_WILD_DO_TABLE',
		'REPLICATE_WILD_IGNORE_TABLE',
		'REPLICATION',
		'REQUIRE',
		'REQUIRE_ROW_FORMAT',
		'RESET',
		'RESIGNAL',
		'RESOURCE',
		'RESPECT',
		'RESTART',
		'RESTORE',
		'RESTRICT',
		'RESUME',
		'RETAIN',
		'RETURN',
		'RETURNED_SQLSTATE',
		'RETURNS',
		'REUSE',
		'REVERSE',
		'REVOKE',
		'RIGHT',
		'RLIKE',
		'ROLE',
		'ROLLBACK',
		'ROLLUP',
		'ROTATE',
		'ROUTINE',
		'ROW',
		'ROWS',
		'ROW_COUNT',
		'ROW_FORMAT',
		'ROW_NUMBER',
		'RTREE',
		'SAVEPOINT',
		'SCHEDULE',
		'SCHEMA',
		'SCHEMAS',
		'SCHEMA_NAME',
		'SECOND',
		'SECONDARY',
		'SECONDARY_ENGINE',
		'SECONDARY_LOAD',
		'SECONDARY_UNLOAD',
		'SECOND_MICROSECOND',
		'SECURITY',
		'SELECT',
		'SENSITIVE',
		'SEPARATOR',
		'SERIAL',
		'SERIALIZABLE',
		'SERVER',
		'SESSION',
		'SET',
		'SHARE',
		'SHOW',
		'SHUTDOWN',
		'SIGNAL',
		'SIGNED',
		'SIMPLE',
		'SKIP',
		'SLAVE',
		'SLOW',
		'SMALLINT',
		'SNAPSHOT',
		'SOCKET',
		'SOME',
		'SONAME',
		'SOUNDS',
		'SOURCE',
		'SPATIAL',
		'SPECIFIC',
		'SQL',
		'SQLEXCEPTION',
		'SQLSTATE',
		'SQLWARNING',
		'SQL_AFTER_GTIDS',
		'SQL_AFTER_MTS_GAPS',
		'SQL_BEFORE_GTIDS',
		'SQL_BIG_RESULT',
		'SQL_BUFFER_RESULT',
		'SQL_CACHE',
		'SQL_CALC_FOUND_ROWS',
		'SQL_NO_CACHE',
		'SQL_SMALL_RESULT',
		'SQL_THREAD',
		'SQL_TSI_DAY',
		'SQL_TSI_FRAC_SECOND',
		'SQL_TSI_HOUR',
		'SQL_TSI_MINUTE',
		'SQL_TSI_MONTH',
		'SQL_TSI_QUARTER',
		'SQL_TSI_SECOND',
		'SQL_TSI_WEEK',
		'SQL_TSI_YEAR',
		'SRID',
		'SSL',
		'STACKED',
		'START',
		'STARTING',
		'STARTS',
		'STATS_AUTO_RECALC',
		'STATS_PERSISTENT',
		'STATS_SAMPLE_PAGES',
		'STATUS',
		'STOP',
		'STORAGE',
		'STORED',
		'STRAIGHT_JOIN',
		'STRING',
		'SUBCLASS_ORIGIN',
		'SUBJECT',
		'SUBPARTITION',
		'SUBPARTITIONS',
		'SUPER',
		'SUSPEND',
		'SWAPS',
		'SWITCHES',
		'SYSTEM',
		'TABLE',
		'TABLES',
		'TABLESPACE',
		'TABLE_CHECKSUM',
		'TABLE_NAME',
		'TEMPORARY',
		'TEMPTABLE',
		'TERMINATED',
		'TEXT',
		'THAN',
		'THEN',
		'THREAD_PRIORITY',
		'TIES',
		'TIME',
		'TIMESTAMP',
		'TIMESTAMPADD',
		'TIMESTAMPDIFF',
		'TINYBLOB',
		'TINYINT',
		'TINYTEXT',
		'TO',
		'TRAILING',
		'TRANSACTION',
		'TRIGGER',
		'TRIGGERS',
		'TRUE',
		'TRUNCATE',
		'TYPE',
		'TYPES',
		'UNBOUNDED',
		'UNCOMMITTED',
		'UNDEFINED',
		'UNDO',
		'UNDOFILE',
		'UNDO_BUFFER_SIZE',
		'UNICODE',
		'UNINSTALL',
		'UNION',
		'UNIQUE',
		'UNKNOWN',
		'UNLOCK',
		'UNSIGNED',
		'UNTIL',
		'UPDATE',
		'UPGRADE',
		'USAGE',
		'USE',
		'USER',
		'USER_RESOURCES',
		'USE_FRM',
		'USING',
		'UTC_DATE',
		'UTC_TIME',
		'UTC_TIMESTAMP',
		'VALIDATION',
		'VALUE',
		'VALUES',
		'VARBINARY',
		'VARCHAR',
		'VARCHARACTER',
		'VARIABLES',
		'VARYING',
		'VCPU',
		'VIEW',
		'VIRTUAL',
		'VISIBLE',
		'WAIT',
		'WARNINGS',
		'WEEK',
		'WEIGHT_STRING',
		'WHEN',
		'WHERE',
		'WHILE',
		'WINDOW',
		'WITH',
		'WITHOUT',
		'WORK',
		'WRAPPER',
		'WRITE',
		'X509',
		'XA',
		'XID',
		'XML',
		'XOR',
		'YEAR',
		'YEAR_MONTH',
		'ZEROFILL'
	); 

	public function __construct( $hostname, $username, $password, $DBname, 
		$port = 3306, $profiling = false, $debug = false ) {
		
		$this->hostname = $hostname;
		$this->username = $username;
		$this->password = $password;
		$this->DBname = $DBname;
		$this->port = $port;
		$this->debug = $debug;
		$this->profiling = $profiling || $this->debug;

		// Check mysqli is available
		if( class_exists("mysqli") ) {
			$this->timer = new sts_timer();
			$this->timer->start();

			if( $this->debug ) echo "<p>".__METHOD__.": Create sts_db $this->DBname</p>";
			
			if( $this->debug ) echo "<p>".__METHOD__.": Credentials (hostname: $this->hostname
			username: $this->username
			password: $this->password
			database: $this->DBname)</p>";
			
			@$this->link = new mysqli($this->hostname, $this->username, $this->password, $this->DBname, $this->port);
			
			// Check the DB connection is established
			if ($this->link->connect_errno) {
				if( $this->debug ) echo "<p>".__METHOD__.": Connection failed to $this->DBname ".$this->link->connect_error."</p>";
				throw new Exception("Connection failed. ".$this->link->connect_error);
			} else {
				$this->timer->stop();
				$this->time_connect = $this->timer->result();

				if( $this->debug ) echo "<p>".__METHOD__.": Connect succeeded to $this->DBname Time: ".$this->time_connect." charset = ".$this->link->character_set_name()."</p>";
				
				$this->link->options(MYSQLI_OPT_CONNECT_TIMEOUT, 300);
				$this->link->options(MYSQLI_OPT_READ_TIMEOUT, 300);
				ini_set('mysqlnd.net_read_timeout', 3600);
				
				if (!$this->link->set_charset("utf8")) {
				    throw new Exception("Error loading character set utf8: ".$this->link->error);
				}
			}
			
		} else {
			if( $this->debug ) echo "<p>".__METHOD__.": Failed to create sts_db $this->DBname, mysqli class does not exist.</p>";
			throw new Exception("mysqli class does not exist.");
		}
	}
	
	// When closing this object, it closes the DB connection
	function __destruct() {
		if( $this->debug ) echo "<p>".__METHOD__.": Destroy sts_db $this->DBname, closing connection.</p>";
		if( class_exists("mysqli") && isset($this->link) ) {
			$this->link->close();
		}
	}
	
	public function set_debug( $debug ) {
		$tmp = $this->debug;
		$this->debug = $debug;
		return $tmp;
	}
	
	public function set_logging( $logging ) {
		$tmp = $this->logging;
		$this->logging = $logging;
		//$this->log_db( 'CALL TO set_logging( '.($logging ? 'true' : 'false').')' );
		//echo "<pre>".__METHOD__.": ".($logging ? 'true' : 'false')."<pre>";
		return $tmp;
	}
	
	private function log_db( $query ) {
	    $e = new Exception();
	    
	    $query2 = preg_replace('/^\t+/m', '', preg_replace('/^[ \t]*[\r\n]+/m', '', $query));

		$log_query = "INSERT INTO EXP_DB_LOG (DB_TIME_CONNECT, DB_TIME_QUERY,
			DB_NUM_ROWS, CREATED_DATE, CREATED_BY, REQUEST_URI, STACK_TRACE,
			DB_QUERY, DB_ERROR)
			VALUES ($this->time_connect, $this->time_query, $this->num_rows,
			CURRENT_TIMESTAMP, ".(isset($_SESSION['EXT_USER_CODE']) ? $_SESSION['EXT_USER_CODE'] : "0").",
			".(empty($_SERVER["REQUEST_URI"]) ? "NULL" : "'".$this->real_escape_string($_SERVER["REQUEST_URI"])."'").",
			'".$this->real_escape_string($this->generateCallTrace( $e, true ))."',
			'".$this->real_escape_string($query2)."',
			".(empty($this->last_error) ? "NULL" : "'".$this->real_escape_string($this->last_error)."'").")";
		
		$response = $this->link->query($log_query);		
	}
	
	public function schema() {
		return $this->DBname;
	}
	
    //! Check if a table exists in the database, return true/false
    public function exists( $table ) {
	    $result = false;
		if( $ignore = $this->link->query("SHOW TABLES LIKE '".$table."'") ) {
		    if($ignore->num_rows == 1) {
		        $result = true;
		    }
		}
		
		return $result;
	}
	
	// Get a stack trace for when we have an exception
	public function generateCallTrace( $e, $for_logging = false )
	{
	    $trace = explode("\n", $e->getTraceAsString());
	    // reverse array to make steps line up chronologically
	    $trace = array_reverse($trace);
	    $length = count($trace);
	    $result = array();
	   
	    for ($i = 0; $i < $length; $i++)
	    {
	        $result[] = ($i + 1)  . ')' . substr($trace[$i], strpos($trace[$i], ' ')); // replace '#someNum' with '$i)', set the right ordering
	    }
	    
	    if( $for_logging ) array_pop($result);
	   
	    return $for_logging ? implode("\n", $result) : 
	    	"<p>Stack Trace:</p>
		    <ul><li>".implode("</li><li>", $result)."</li></ul>";
	}

	// Returns one row of result in an assoc array
	public function get_one_row( $query ) {
		if( $this->profiling || $this->logging ) {
			$this->timer->start();
		}
		if( $this->debug ) {
			echo "<p>".__METHOD__.": using query_string = </p>
			<pre>";
			var_dump($query);
			echo "</pre>";
		}
		$this->last_error = "";

		try {
			if( $result = $this->link->query($query) ) {
				if( $this->debug ) echo "<p>".__METHOD__.": result: ".
				($result ? "true" : "false")." affected_rows: ".$this->link->affected_rows."</p>";
				if( $result === true ) {	// For update, insert, delete - return true/false
					$this->num_rows = 0;
					$row=true;
				} else if( $this->link->affected_rows == 0 ) {	// Failure, no affected rows
					if( $this->debug ) echo "<p>".__METHOD__.": failed: no affected rows.</p>";
					$this->last_error = "no affected rows";
					$this->num_rows = 0;
					if( $this->logging ) $this->log_db( $query );
					return false;
				} else {					// For select - return row
					$this->num_rows = $this->link->affected_rows;
					$row=$result->fetch_assoc();
					$result->close();
				}
				if( $this->profiling || $this->logging ) {
					$this->timer->stop();
					$this->time_query += $this->timer->result();
				}
				if( $this->debug ) echo "<p>".__METHOD__.": succeeded. Time: ".$this->timer->result()."</p>";
				$this->last_error = "";
				if( $this->logging ) $this->log_db( $query );
				return $row;
			} else {
				if( $this->debug ) echo "<p>".__METHOD__.": failed: ".$this->link->error."</p>";
				$this->last_error = $this->link->error;
				$this->num_rows = 0;
				if( $this->logging ) $this->log_db( $query );
				return false;
			}
		} catch (Exception $e) {
			if( $this->debug ) echo "<p>".__METHOD__.": exception: ".$e->getMessage().
				"</p>".$this->generateCallTrace( $e );
			$this->last_error = $e->getMessage();
			$this->num_rows = 0;
			$result =  false;
			if( $this->logging ) $this->log_db( $query );
		}
	}
	
	// Returns all rows in an array of assoc array
	public function get_multiple_rows( $query ) {
		if( $this->profiling || $this->logging ) {
			$this->timer->start();
		}
		if( $this->debug ) {
			echo "<h3>".__METHOD__.": using query_string = </h3>
			<pre>";
			var_dump($query);
			echo "</pre>";
			ob_flush(); flush();
		}
		$rows = false;

		set_error_handler("db_warning_handler", E_WARNING);
		try {
			if( $result = $this->link->query($query) ) {
				if( $this->debug ) {
					echo "<p>".__METHOD__.": after query, result = </p>
					<pre>";
					var_dump($result);
					echo "</pre>";
					ob_flush(); flush();
				}
				if( $result === true ) {	// For update, insert, delete - return true/false
					$this->num_rows = 0;
					$row=true;
				} else {					// For select - return rows
					$this->num_rows = $result->num_rows;
					if (method_exists('mysqli_result', 'fetch_all')) { # Compatibility layer with PHP < 5.3
						$rows=$result->fetch_all(MYSQLI_ASSOC);
					} else {
						for ($rows = array(); $tmp = $result->fetch_assoc();) $rows[] = $tmp;
					}
					if( $this->debug ) {
						echo "<p>".__METHOD__.": after fetch_all, rows = </p>
						<pre>";
						var_dump($rows);
						echo "</pre>";
						ob_flush(); flush();
					}

					$result->close();
				}
				if( $this->profiling || $this->logging ) {
					$this->timer->stop();
					$this->time_query += $this->timer->result();
				}
				if( $this->debug ) echo "<h3>".__METHOD__.": succeeded. Rows: ".$this->num_rows." Time: ".$this->timer->result()."</h3>";
				$this->last_error = "";
				restore_error_handler();
				if( $this->logging ) $this->log_db( $query );

				return $rows;
			} else {
				if( $this->debug ) echo "<p>".__METHOD__.": failed: ".$this->link->error."</p>";
				//! SCR# 628 - send email when we get Unknown column...
				if( strpos($this->link->error, 'Unknown column') !== false ) {
					require_once( "sts_email_class.php" );

					$email = sts_email::getInstance($this, false);
					$email->send_alert(__METHOD__.": failed: ".$this->link->error.
						'<br>query = '.$query, EXT_ERROR_ERROR);
				}
				$this->last_error = $this->link->error;
				$this->num_rows = 0;
				restore_error_handler();
				if( $this->logging ) $this->log_db( $query );
				return false;
			}
		} catch (Exception $e) {
			if( $this->debug ) {
				echo "<p>".__METHOD__.": after query, Exception</p>";
				ob_flush(); flush();
			}
			if( $this->debug ) echo "<p>".__METHOD__.": exception: ".$e->getMessage().
				"</p>".$this->generateCallTrace( $e );
			$this->last_error = $e->getMessage();
			$this->num_rows = 0;
			$result =  false;
			restore_error_handler();
			if( $this->logging ) $this->log_db( $query );
		}
	}

	// Returns one row of result in an assoc array
	public function multi_query( $query ) {
		if( $this->profiling || $this->logging ) {
			$this->timer->start();
		}
		if( $this->debug ) {
			echo "<p>".__METHOD__.": using query_string = </p>
			<pre>";
			var_dump($query);
			echo "</pre>";
		}

		try {
			if( $result = $this->link->multi_query($query) ) {
				if( $this->profiling || $this->logging ) {
					$this->timer->stop();
					$this->time_query += $this->timer->result();
				}
				if( $this->debug ) echo "<p>".__METHOD__.": succeeded. Time: ".$this->timer->result()."</p>";
				$this->last_error = "";
	
				do {
					if ($res = $this->link->store_result()) {
						$rows=$res->fetch_all(MYSQLI_ASSOC);
						$this->num_rows = $res->num_rows;
					$res->close();
					
					if( $this->logging ) $this->log_db( $query );
					return $rows;
					}
				} while ($this->link->more_results() && $this->link->next_result());
	
				if( $this->logging ) $this->log_db( $query );
				return false;
			} else {
				if( $this->debug ) echo "<p>".__METHOD__.": failed: ".$this->link->error."</p>";
				$this->last_error = $this->link->error;
				$this->num_rows = 0;
				if( $this->logging ) $this->log_db( $query );
				return false;
			}
		} catch (Exception $e) {
			if( $this->debug ) echo "<p>".__METHOD__.": exception: ".$e->getMessage().
				"</p>".$this->generateCallTrace( $e );
			$this->last_error = $e->getMessage();
			$this->num_rows = 0;
			$result =  false;
			if( $this->logging ) $this->log_db( $query );
		}
	}
	
	// Escape string
	public function real_escape_string( $string ) {
		return $this->link->real_escape_string( $string );
	}
	
	//! SCR# 614 - is this keyword reserved?
	public function is_reserved( $string ) {
		return in_array(strtoupper($string), $this->reserved_words) ;
	}

	//! SCR# 614 - highlight all reserved words
	public function highlight_reserved( $string ) {
		$newstring = $string;
		foreach( $this->reserved_words as $word ) {
			$newstring = preg_replace('/\b('.$word.')\b/im', '<b>'.$word.'</b>', $newstring);
		}
		
		return $newstring;
	}
	
	// Error string
	public function error() {
		return $this->last_error;
	}
	
	public function server_info() {
		if( $this->debug ) {
			echo "<p>".__METHOD__.": return = </p>
			<pre>";
			var_dump($this->link->server_info);
			echo "</pre>";
		}
		return $this->link->server_info;
	}
	
	public function server_version() {
		return $this->link->server_version;
	}
	
	// Profiling
	public function timer_results() {
		if( $this->profiling )
			return array( $this->time_connect, $this->time_query );
		else
			return false;
	}

	// Used to insert a row into a DB table
	public function insert_row( $query )
	{
		if( $this->profiling || $this->logging ) {
			$this->timer->start();
		}
		if( $this->debug ) {
			echo "<p>".__METHOD__.": using query_string = </p>
			<pre>";
			var_dump($query);
			echo "</pre>";
		}

		try {
			if( $response = $this->link->query($query) ) {
				$this->num_rows = 0;
				$result =  $this->link->insert_id;
			} else {
				if( $this->debug ) echo "<p>".__METHOD__.": failed: ".$this->link->error."</p>";
				$this->last_error = $this->link->error;
				$this->num_rows = 0;
				$result =  false;
			}
		} catch (Exception $e) {
			if( $this->debug ) echo "<p>".__METHOD__.": exception: ".$e->getMessage().
				"</p>".$this->generateCallTrace( $e );
			$this->last_error = $e->getMessage();
			$this->num_rows = 0;
			$result =  false;
		}
		
		if( $this->profiling || $this->logging ) {
			$this->timer->stop();
			$this->time_query += $this->timer->result();
		}
		if( $this->logging ) $this->log_db( $query );
		return $result;
	}

}

try {
	$exspeedite_db = new sts_db($sts_db_host, $sts_username, $sts_password,
		$sts_database, $sts_db_port, $sts_profiling, isset($sts_debug) ? $sts_debug : false);
} catch (Exception $e) {
	$exspeedite_db = false;
	echo "<h2>Error: ".$e->getMessage()."</h2>
		<ul>
		<p>There is a problem with the connection to the database. Exspeedite cannot continue without access to the database.</p>
		<p>Please contact your administrator to sort this out.</p>
		<p>Or contact Exspeedite support.</p>
		</ul>";
	die;
}
if( isset($sts_debug) && $sts_debug ) echo "<p>After create sts_db</p>";

?>