<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );

$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "Test11 - Inspection Report";
require_once( "include/header_inc.php" );

if( isset($_GET['NOTIFY'])) {
	require_once( "include/sts_email_class.php" );
	$email = new sts_email( $exspeedite_db, $sts_debug );
	
	$email->notify_shipment_event( 9798, 'dropped' );
} else
if( isset($_GET['ZIP'])) {
	require_once( "include/sts_zip_class.php" );
	$zip = new sts_zip( $exspeedite_db, true );
	$result = $zip->validate_google( [ 'ZIP_CODE' => '75055', 'COUNTRY' => 'USA' ]);
	echo "<pre>";
	var_dump($result);
	echo "</pre>";
	$result = $zip->validate_google( [ 'ZIP_CODE' => 'L9E 1V9', 'COUNTRY' => 'Canada' ]);
	echo "<pre>";
	var_dump($result);
	echo "</pre>";
	
} else
if( isset($_GET['PCM'])) {
	require_once( "PCMILER/exp_get_miles.php" );
	$pcm = new sts_pcmiler_api( $exspeedite_db, true );
	$result = $pcm->validate_address( [ 'ZIP_CODE' => '6480', 'COUNTRY' => 'USA' ]);
	echo "<pre>";
	var_dump($result);
	echo "</pre>";
	
	$result = $pcm->validate_address( [ 'ZIP_CODE' => 'V3S 0B4', 'COUNTRY' => 'Canada' ]);
	echo "<pre>";
	var_dump($result);
	echo "</pre>";
	
} else
if( isset($_GET['BVD'])) {
	require_once( "include/sts_card_bvd_class.php" );
	require_once( "include/sts_card_class.php" );

	$bvd = sts_card_bvd::getInstance( $exspeedite_db, $sts_debug );
    $start = date('Y-m-d', strtotime('first day of this month'));
    $end = date('Y-m-d', strtotime('last day of this month'));
			    

	$bvd->bvd_fetch( $_GET['BVD'], $start, $end );
	
} else
if( isset($_GET['DP'])) {
	$now = date("m/d/Y H:i");
	
	echo '<div class="container" role="main">

	<p>Enter date</p>
	<div style="position: relative">
	<div class="col-sm-6">
         <div class="form-group">
            <div class="input-group ETA" code="42">
               <input type="text" class="form-control" value="'.$now.'">
               <span class="input-group-addon">
               <span class="glyphicon glyphicon-calendar"></span>
               </span>
            </div>
         </div>
      </div>

	</div>
	
	<script language="JavaScript" type="text/javascript"><!--
		$(document).ready( function () {
	
			$(\'.ETA\').datetimepicker({
		      //language: \'en\',
		      format: \'MM/DD/YYYY HH:mm\',
		      //autoclose: true,
		      //pickTime: false
		    }).on(\'dp.change\', function(e) {
			    console.log( $(this).children(\'input\').val(), $(this).attr(\'code\') );
			});
	});
	//--></script>
	';

} else
if( isset($_GET['PIPEDRIVE'])) {
	require_once( "include/sts_pipedrive_class.php" );
	$pipedrive = sts_pipedrive::getInstance( $exspeedite_db, $sts_debug );
	
	$pipedrive->get_label_id();
	//$pipedrive->archive_lead( '6f7ae4e0-418a-11ec-a445-a7502a3705b0', false );
	//$pipedrive->get_leads();
	//$pipedrive->get_organiztion( 3 );
	
} else
if( isset($_GET['MAIL'])) {
	include __DIR__."/MailChecker/platform/php/MailChecker.php";
	
	$e1 = 'myemail@yopmail.com';
	$e2 = 'duncan@bigdreams.ca';

	echo "<pre>";
	var_dump($e1,MailChecker::isValid($e1));
	var_dump($e2,MailChecker::isValid($e2));
	echo "</pre>";
	
} else
if( isset($_GET['ATTACH'])) {
	require_once( "include/sts_attachment_class.php" );
	$att = sts_attachment::getInstance( $exspeedite_db, $sts_debug );
	
	$possibles = $att->fetch_rows("DATE(CREATED_DATE) = '2021-10-14'");
	//echo "<pre>";
	//var_dump($possibles);
	//echo "</pre>";
	
	if( is_array($possibles) && count($possibles) > 0 ) {
		echo '<table>
		<tr>
			<th>ATTACHMENT_CODE</th>
			<th>SOURCE_CODE</th>
			<th>SOURCE_TYPE</th>
			<th>FILE_NAME</th>
			<th>STORED_WHERE</th>
			<th>STORED_AS</th>
		</tr>			
		';
		foreach( $possibles as $row ) {
			$check = $att->where($row["STORED_WHERE"])->file_exists($row['STORED_AS']);
			if( ! $check )
			echo '<tr>
			<td>'.$row["ATTACHMENT_CODE"].'</td>
				<td>'.$row["SOURCE_CODE"].'</td>
				<td>'.$row["SOURCE_TYPE"].'</td>
				<td>'.$row["FILE_NAME"].'</td>
				<td>'.$row["STORED_WHERE"].'</td>
				<td>'.$row["STORED_AS"].'</td>
				</tr>
				';
		}
		echo '</table>
		';
	}
	
	
} else
if( isset($_GET['OP'])) {
		//echo "<pre>";
		//var_dump(opcache_get_configuration());
		//echo "</pre>";
	
	if(function_exists('opcache_compile_file')) {
		$include_dir = './include';
		
		//$includes = scandir($include_dir);
		$includes = ['sts_session_class.php', 'sts_setting_class.php', 'sts_alert_class.php',
			'sts_email_class.php', 'sts_shipment_class.php', 'sts_load_class.php',
			'sts_status_class.php', 'sts_status_codes_class.php', 'sts_report_class.php',
			'header_inc.php', 'footer_inc.php', 'navbar_inc.php'];
		
		foreach( $includes as $include_file ) {
			// substr($include_file, 0, 4) == 'sts_' &&
			if( substr($include_file, -4) == '.php') {
				echo '<p>Compile '.$include_dir.'/'.$include_file.' - ';
				$result1 = opcache_compile_file($include_dir.'/'.$include_file);
				if( ! $result1 ) die;
				
				sleep(1);
				$result2 = opcache_is_script_cached($include_dir.'/'.$include_file);
				echo ( $result2 ? 'true' : 'false').'</p>';
				ob_flush(); flush();
				if( ! $result2 ) die;
			}
		}
	}
	

} else
if( isset($_GET['DP'])) {
	require_once( "include/sts_driver_pay_master_class.php" );
	$dp = sts_driver_pay_master::getInstance($exspeedite_db, $sts_debug);
	
	echo '<p>Driver: '.$dp->driver_menu().'</p>
	<p>Period: '.$dp->pay_period_menu().'</p>';
	
	//$dp->driver_pay('2019-12-30', 188);

} else
if( isset($_GET['DETAIL'])) {
	require_once( "include/sts_detail_class.php" );
	$detail = sts_detail::getInstance($exspeedite_db, $sts_debug);
	
	$return = $detail->update_hazmat( $_GET['DETAIL'] );
	echo "<pre>";
	var_dump($return);
	echo "</pre>";
	
} else
if( isset($_GET['YAML'])) {
	if (extension_loaded('yaml')) {
		echo "yaml loaded :)";

		$url = 'https://developers.pipedrive.com/docs/api/v1/openapi.yaml';
		$yaml = file_get_contents($url);
		$parsed = yaml_parse($yaml);
		echo "<pre>";
		var_dump($parsed);
		echo "</pre>";
	} else
		echo "something is wrong - yaml not loaded";
  
} else
if( isset($_GET['CERT2'])) {
    error_reporting( E_ALL );
    
	echo "<pre>phpinfo\n";
	var_dump(getenv('OPENSSL_CONF') );
	var_dump(getenv('OPENSSL_CONF2') );
	echo "</pre>";
    
$dn = array(
"countryName" => "US",
"stateOrProvinceName" => "Connecticut",
"localityName" => "Storrs",
"organizationName" => "ConsoliBYTE, LLC",
"organizationalUnitName" => "Technical",
"commonName" => "www.xyz.com:test.www.xyz.com"
);
$privkey = openssl_pkey_new();
$csr = openssl_csr_new($dn, $privkey);
$sscert = openssl_csr_sign($csr, null, $privkey, 365);
openssl_csr_export($csr, $csrout) and var_dump($csrout);
openssl_x509_export($sscert, $certout) and var_dump($certout);
openssl_pkey_export($privkey, $pkeyout, "mypassword") and var_dump($pkeyout);
while (($e = openssl_error_string()) !== false) {
echo $e . "\n";
}
} else	
if( isset($_GET['CERT'])) {
    error_reporting( E_ALL );
	require_once( "include/sts_certificate_class.php" );
	$cert = sts_certificate::getInstance($exspeedite_db, $sts_debug);
	echo "<p>created sts_certificate class</p>";
	$x = $cert->create('certificate', 'fuzzy');
	echo "<p>after create</p>";

	//$pub_key = openssl_pkey_get_public($x->pub);
	echo "<pre>cert, dump\n";
	var_dump($x->pub, openssl_x509_parse($x->pub) );
	echo "</pre>";
	
	$ok = '-----BEGIN CERTIFICATE-----
MIIC0DCCAjkCBEOO/bswDQYJKoZIhvcNAQEFBQAwga4xJjAkBgkqhkiG9w0BCQEWF3Jvc2V0dGFu
ZXRAbWVuZGVsc29uLmRlMQswCQYDVQQGEwJERTEPMA0GA1UECBMGQmVybGluMQ8wDQYDVQQHEwZC
ZXJsaW4xIjAgBgNVBAoTGW1lbmRlbHNvbi1lLWNvbW1lcmNlIEdtYkgxIjAgBgNVBAsTGW1lbmRl
bHNvbi1lLWNvbW1lcmNlIEdtYkgxDTALBgNVBAMTBG1lbmQwHhcNMDUxMjAxMTM0MjE5WhcNMTkw
ODEwMTM0MjE5WjCBrjEmMCQGCSqGSIb3DQEJARYXcm9zZXR0YW5ldEBtZW5kZWxzb24uZGUxCzAJ
BgNVBAYTAkRFMQ8wDQYDVQQIEwZCZXJsaW4xDzANBgNVBAcTBkJlcmxpbjEiMCAGA1UEChMZbWVu
ZGVsc29uLWUtY29tbWVyY2UgR21iSDEiMCAGA1UECxMZbWVuZGVsc29uLWUtY29tbWVyY2UgR21i
SDENMAsGA1UEAxMEbWVuZDCBnzANBgkqhkiG9w0BAQEFAAOBjQAwgYkCgYEAvl9YOib23cCSOpkD
DU+NRnMnB1G8AhViieKhw2h33895+IfrkCSaEL3PMi0wn55ddPRgdMi9mOWELU6ITkvSMMsjFgYY
e+1ibQjfK3Tnw9g1te/O+7XvjZaboEb4Onjh+p6fVZ90WTg1ccU8sifKSPFTJ59d2HsjDMO1VWhD
uYUCAwEAATANBgkqhkiG9w0BAQUFAAOBgQC8DiHP61jAADXRIfxoDvw0pFTMMTOVAa905GGy1P+Y
4NC8I92PviobpmEq8Z2HsEi6iviVwODrPTSfm93mUWZ52EPXinlGYHRP0D/VxNOMvFi+mRyweLA5
5rIFWk1PqdJRch9E3vTcjwRtCfPNdPQlynVwk0jeYKtEtQn2J9LLWg==
-----END CERTIFICATE-----
';
	
	$pub_key = openssl_pkey_get_public($ok);
	echo "<pre>pub, details, parse\n";
	var_dump($pub_key, openssl_pkey_get_details($pub_key), openssl_x509_parse($ok) );
	echo "</pre>";
	
	/*
	$plaintext = "Mary had a little lamb";
	
	$ivlen = openssl_cipher_iv_length($cipher="AES-128-CBC");
	$iv = openssl_random_pseudo_bytes($ivlen);
	$ciphertext_raw = openssl_encrypt($plaintext, $cipher, $x->pub, $options=OPENSSL_RAW_DATA, $iv);
	$hmac = hash_hmac('sha256', $ciphertext_raw, $x->pub, $as_binary=true);
	$ciphertext = base64_encode( $iv.$hmac.$ciphertext_raw );

	echo "<pre>plaintext, ciphertext_raw, ciphertext\n";
	var_dump($plaintext, $ciphertext_raw, $ciphertext);
	echo "</pre>";

	$c = base64_decode($ciphertext);
	$ivlen = openssl_cipher_iv_length($cipher="AES-128-CBC");
	$iv = substr($c, 0, $ivlen);
	$hmac = substr($c, $ivlen, $sha2len=32);
	$ciphertext_raw = substr($c, $ivlen+$sha2len);
	echo "<pre>ciphertext_raw 2\n";
	var_dump($ciphertext_raw);
	echo "</pre>";

	$original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $x->pub, $options=OPENSSL_RAW_DATA, $iv);

	echo "<pre>plaintext\n";
	var_dump($original_plaintext);
	echo "</pre>";
	*/

} else	
if( isset($_GET['SETTING'])) {
	require_once( "include/sts_setting_class.php" );
	$sc = sts_setting::getInstance($exspeedite_db, $sts_debug);

	echo "<pre>";
	var_dump($sc->get('option', 'SHIPMENT_SCREEN_REFRESH_RATE'));
	echo "</pre>";
die;
} else	
if( isset($_GET['STATE'])) {
	require_once( "include/sts_status_codes_class.php" );
	$sc = sts_status_codes::getInstance($exspeedite_db, $sts_debug);

	echo "<pre>";
	var_dump($sc->cache_following_states('load'));
	echo "</pre>";
die;

	require_once( "include/sts_cache_class.php" );
	$cache_table = sts_cache::getInstance($exspeedite_db, $sts_debug);

		echo "<pre>";
		var_dump($cache_table->get_state_table());
		echo "</pre>";
	
} else	
if( isset($_GET['SQL'])) {
	
function sql_format($query) {
  $keywords = array("select", "from", "where", "order by", "group by", "insert into", "update","SET", ",");
  foreach ($keywords as $keyword) {
      if (preg_match("/($keyword *)/i", ",", $matches)) {
        $query = str_replace($matches[1],strtoupper($matches[1]) . "<br/>&nbsp;&nbsp;  ", $query);  
      }
    else if(preg_match("/($keyword *)/i", $query, $matches)) {
      $query = str_replace($matches[1],"<br>".strtoupper($matches[1]) . "<br/>&nbsp;  ", $query);
    }
  }
  return $query;
}



function getFormattedSQL($sql_raw)
{
 if( empty($sql_raw) || !is_string($sql_raw) )
 {
  return false;
 }

 $sql_reserved_all = array (
     'ACCESSIBLE', 'ACTION', 'ADD', 'AFTER', 'AGAINST', 'AGGREGATE', 'ALGORITHM', 'ALL', 'ALTER', 'ANALYSE', 'ANALYZE', 'AND', 'AS', 'ASC',
     'AUTOCOMMIT', 'AUTO_INCREMENT', 'AVG_ROW_LENGTH', 'BACKUP', 'BEGIN', 'BETWEEN', 'BINLOG', 'BOTH', 'BY', 'CASCADE', 'CASE', 'CHANGE', 'CHANGED',
     'CHARSET', 'CHECK', 'CHECKSUM', 'COLLATE', 'COLLATION', 'COLUMN', 'COLUMNS', 'COMMENT', 'COMMIT', 'COMMITTED', 'COMPRESSED', 'CONCURRENT', 
     'CONSTRAINT', 'CONTAINS', 'CONVERT', 'CREATE', 'CROSS', 'CURRENT_TIMESTAMP', 'DATABASE', 'DATABASES', 'DAY', 'DAY_HOUR', 'DAY_MINUTE', 
     'DAY_SECOND', 'DEFINER', 'DELAYED', 'DELAY_KEY_WRITE', 'DELETE', 'DESC', 'DESCRIBE', 'DETERMINISTIC', 'DISTINCT', 'DISTINCTROW', 'DIV',
     'DO', 'DROP', 'DUMPFILE', 'DUPLICATE', 'DYNAMIC', 'ELSE', 'ENCLOSED', 'END', 'ENGINE', 'ENGINES', 'ESCAPE', 'ESCAPED', 'EVENTS', 'EXECUTE',
     'EXISTS', 'EXPLAIN', 'EXTENDED', 'FAST', 'FIELDS', 'FILE', 'FIRST', 'FIXED', 'FLUSH', 'FOR', 'FORCE', 'FOREIGN', 'FROM', 'FULL', 'FULLTEXT',
     'FUNCTION', 'GEMINI', 'GEMINI_SPIN_RETRIES', 'GLOBAL', 'GRANT', 'GRANTS', 'GROUP', 'HAVING', 'HEAP', 'HIGH_PRIORITY', 'HOSTS', 'HOUR', 'HOUR_MINUTE',
     'HOUR_SECOND', 'IDENTIFIED', 'IF', 'IGNORE', 'IN', 'INDEX', 'INDEXES', 'INFILE', 'INNER', 'INSERT', 'INSERT_ID', 'INSERT_METHOD', 'INTERVAL',
     'INTO', 'INVOKER', 'IS', 'ISOLATION', 'JOIN', 'KEY', 'KEYS', 'KILL', 'LAST_INSERT_ID', 'LEADING', 'LEFT', 'LEVEL', 'LIKE', 'LIMIT', 'LINEAR',               
     'LINES', 'LOAD', 'LOCAL', 'LOCK', 'LOCKS', 'LOGS', 'LOW_PRIORITY', 'MARIA', 'MASTER', 'MASTER_CONNECT_RETRY', 'MASTER_HOST', 'MASTER_LOG_FILE',
     'MASTER_LOG_POS', 'MASTER_PASSWORD', 'MASTER_PORT', 'MASTER_USER', 'MATCH', 'MAX_CONNECTIONS_PER_HOUR', 'MAX_QUERIES_PER_HOUR',
     'MAX_ROWS', 'MAX_UPDATES_PER_HOUR', 'MAX_USER_CONNECTIONS', 'MEDIUM', 'MERGE', 'MINUTE', 'MINUTE_SECOND', 'MIN_ROWS', 'MODE', 'MODIFY',
     'MONTH', 'MRG_MYISAM', 'MYISAM', 'NAMES', 'NATURAL', 'NOT', 'NULL', 'OFFSET', 'ON', 'OPEN', 'OPTIMIZE', 'OPTION', 'OPTIONALLY', 'OR',
     'ORDER', 'OUTER', 'OUTFILE', 'PACK_KEYS', 'PAGE', 'PARTIAL', 'PARTITION', 'PARTITIONS', 'PASSWORD', 'PRIMARY', 'PRIVILEGES', 'PROCEDURE',
     'PROCESS', 'PROCESSLIST', 'PURGE', 'QUICK', 'RAID0', 'RAID_CHUNKS', 'RAID_CHUNKSIZE', 'RAID_TYPE', 'RANGE', 'READ', 'READ_ONLY',            
     'READ_WRITE', 'REFERENCES', 'REGEXP', 'RELOAD', 'RENAME', 'REPAIR', 'REPEATABLE', 'REPLACE', 'REPLICATION', 'RESET', 'RESTORE', 'RESTRICT',
     'RETURN', 'RETURNS', 'REVOKE', 'RIGHT', 'RLIKE', 'ROLLBACK', 'ROW', 'ROWS', 'ROW_FORMAT', 'SECOND', 'SECURITY', 'SELECT', 'SEPARATOR',
     'SERIALIZABLE', 'SESSION', 'SET', 'SHARE', 'SHOW', 'SHUTDOWN', 'SLAVE', 'SONAME', 'SOUNDS', 'SQL', 'SQL_AUTO_IS_NULL', 'SQL_BIG_RESULT',
     'SQL_BIG_SELECTS', 'SQL_BIG_TABLES', 'SQL_BUFFER_RESULT', 'SQL_CACHE', 'SQL_CALC_FOUND_ROWS', 'SQL_LOG_BIN', 'SQL_LOG_OFF',
     'SQL_LOG_UPDATE', 'SQL_LOW_PRIORITY_UPDATES', 'SQL_MAX_JOIN_SIZE', 'SQL_NO_CACHE', 'SQL_QUOTE_SHOW_CREATE', 'SQL_SAFE_UPDATES',
     'SQL_SELECT_LIMIT', 'SQL_SLAVE_SKIP_COUNTER', 'SQL_SMALL_RESULT', 'SQL_WARNINGS', 'START', 'STARTING', 'STATUS', 'STOP', 'STORAGE',
     'STRAIGHT_JOIN', 'STRING', 'STRIPED', 'SUPER', 'TABLE', 'TABLES', 'TEMPORARY', 'TERMINATED', 'THEN', 'TO', 'TRAILING', 'TRANSACTIONAL',    
     'TRUNCATE', 'TYPE', 'TYPES', 'UNCOMMITTED', 'UNION', 'UNIQUE', 'UNLOCK', 'UPDATE', 'USAGE', 'USE', 'USING', 'VALUES', 'VARIABLES',
     'VIEW', 'WHEN', 'WHERE', 'WITH', 'WORK', 'WRITE', 'XOR', 'YEAR_MONTH'
 );

 $sql_skip_reserved_words = array('AS', 'ON', 'USING');
 $sql_special_reserved_words = array('(', ')');

 $sql_raw = str_replace("\n", " ", $sql_raw);

 $sql_formatted = "";

 $prev_word = "";
 $word = "";

 for( $i=0, $j = strlen($sql_raw); $i < $j; $i++ )
 {
  $word .= $sql_raw[$i];

  $word_trimmed = trim($word);

  if($sql_raw[$i] == " " || in_array($sql_raw[$i], $sql_special_reserved_words))
  {
   $word_trimmed = trim($word);

   $trimmed_special = false;

   if( in_array($sql_raw[$i], $sql_special_reserved_words) )
   {
    $word_trimmed = substr($word_trimmed, 0, -1);
    $trimmed_special = true;
   }

   $word_trimmed = strtoupper($word_trimmed);

   if( in_array($word_trimmed, $sql_reserved_all) && !in_array($word_trimmed, $sql_skip_reserved_words) )
   {
    if(in_array($prev_word, $sql_reserved_all))
    {
     $sql_formatted .= '<b>'.strtoupper(trim($word)).'</b>'.'&nbsp;';
    }
    else
    {
     $sql_formatted .= '<br/>&nbsp;';
     $sql_formatted .= '<b>'.strtoupper(trim($word)).'</b>'.'&nbsp;';
    }

    $prev_word = $word_trimmed;
    $word = "";
   }
   else
   {
    $sql_formatted .= trim($word).'&nbsp;';

    $prev_word = $word_trimmed;
    $word = "";
   }
  }
 }

 $sql_formatted .= trim($word);

 return $sql_formatted;
}


$q1 = "select LOAD_CODE, CURRENT_STATUS AS CURRENT_STATUS_KEY, (SELECT STATUS_STATE from EXP_STATUS_CODES X WHERE X.STATUS_CODES_CODE = EXP_LOAD.CURRENT_STATUS  LIMIT 0 , 1) AS CURRENT_STATUS, (SELECT count(*) from EXP_SHIPMENT X WHERE X.LOAD_CODE = EXP_LOAD.LOAD_CODE
					) AS SHIPMENTS, (SELECT count(*) from EXP_STOP X WHERE X.LOAD_CODE = EXP_LOAD.LOAD_CODE
					) AS STOPS, (SELECT coalesce(sum(PALLETS),0) from EXP_SHIPMENT X WHERE X.LOAD_CODE = EXP_LOAD.LOAD_CODE
					) AS PALLETS, (SELECT coalesce(sum(WEIGHT),0) from EXP_SHIPMENT X WHERE X.LOAD_CODE = EXP_LOAD.LOAD_CODE
					) AS WEIGHT, CHANGED_DATE, CHANGED_BY AS CHANGED_BY_KEY, (SELECT USERNAME from EXP_USER X WHERE X.USER_CODE = EXP_LOAD.CHANGED_BY  LIMIT 0 , 1) AS CHANGED_BY
		from EXP_LOAD 
		where OFFICE_CODE = 1
		order by changed_date desc
		limit 5
		";

$q2 = "select THE_VALUE
		from EXP_SETTING 
		where CATEGORY = 'main' AND SETTING = 'LAST_CHECKED'";

require_once( "include/SqlFormatter.php" );		
//echo SqlFormatter::format($q1);

// Using web API from https://sqlformat.org/api/

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL,"https://sqlformat.org/api/v1/format");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "reindent=1&keyword_case=upper&sql=".$q2);

// Receive server response ...
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$server_output = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close ($ch);

$info = json_decode($server_output);

//echo "<pre>".$httpCode."\n".$info->result."</pre>";
echo SqlFormatter::highlight($info->result);

} else	
if( isset($_GET['USERS'])) {
	echo $my_session->who('count');

} else	
if( isset($_GET['PRUNE'])) {
	require_once( "include/sts_attachment_class.php" );
	$att = sts_attachment::getInstance( $exspeedite_db, $sts_debug );
	$local = sts_files_local::getInstance( $exspeedite_db, $sts_debug );
	set_time_limit(0);
	ini_set('memory_limit', '1024M');
	
	// Turn off output buffering
	ini_set('output_buffering', 'off');
	// Implicitly flush the buffer(s)
	ini_set('implicit_flush', true);
	ob_implicit_flush(true);

	if (ob_get_level() == 0) ob_start();

	echo '<div class="container" role="main">
	<h2>Prune Directories</h2>
	<p>Start at '.$local->get_attachment_dir().'</p>
	';
	ob_flush(); flush();
	
function traverse($att, $dir) {

    $files = array_diff(scandir($dir), ['.', '..']);
	echo '<p>at '.$dir.' ('.count($files).' items)</p>';
    echo '<ul>';

	if( count($files) > 0 ) {
	    foreach($files as $key => $value) {
	        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
	        if(is_dir($path)) {
			    traverse($att, $path);
		    } else {
			    echo '<p>file '.$value.' - ';
			    $check = $att->fetch_rows("STORED_AS = '".str_replace('\\', '/', $path)."'");
			    if( is_array($check) && count($check) > 0 ) {
					echo '<strong>FOUND IN DB</strong></p>';
			    } else {
				    $fa_path = $att->pathto_filesanywhere($path);
				    $check2 = $att->fetch_rows("STORED_AS = '".$fa_path."'");
				    if( is_array($check2) && count($check2) > 0 ) {
						echo '<strong>FOUND IN DB for filesanywhere</strong></p>';
						if( $att->attachment_exists($check2[0]["ATTACHMENT_CODE"]) ) {
							echo '<p>FILE EXISTS in filesanywhere (can remove)</p>';
							unlink($path);
						} else {
							echo '<p>FILE MISSING in filesanywhere (manual intervention needed)</p>';
							echo "<pre>";
							var_dump($check2);
							echo "</pre>";
						}
				    } else {
					    echo '<strong>ORPHAN</strong></p>';
				    }
			    }
		    }
	    }
	}

    $files2 = array_diff(scandir($dir), ['.', '..']);

	if( count($files2) == 0 ) {
		echo '<p><strong>remove dir '.$dir.'</strong></p>';
		if( is_writable($dir) )
			rmdir($dir);
	}
	
    echo '</ul>';
	ob_flush(); flush();
}

traverse($att, $local->get_attachment_dir());
	

	
} else if( isset($_GET['FA'])) {
	require_once( "include/sts_attachment_class.php" );
	//$fa = sts_files_anywhere::getInstance( $exspeedite_db, true );
	$att = sts_attachment::getInstance( $exspeedite_db, $sts_debug );
	//$result = $att->moveto_local( $_GET['FA'] );
	set_time_limit(0);
	ini_set('memory_limit', '1024M');
	
	$attachments = $att->fetch_rows('', 'ATTACHMENT_CODE');

		// Turn off output buffering
		ini_set('output_buffering', 'off');
		// Implicitly flush the buffer(s)
		ini_set('implicit_flush', true);
		ob_implicit_flush(true);
	
		if (ob_get_level() == 0) ob_start();

		$missing = [];
		$found = [];
		echo "<p>";
		foreach($attachments as $row) {
			$code = $row["ATTACHMENT_CODE"];
			echo $code." ... ";
			ob_flush(); flush();
			
			if( $att->attachment_exists( $code )) {
				$found[] = $code;
				echo "FOUND ";
				ob_flush(); flush();
			} else {
				$missing[] = $code;
				echo "MISSING ";
				ob_flush(); flush();
			}
		}
		
		//echo "<h3>".count($found)." Found: ".implode(', ', $found)."</h3>";
		echo "<h3>".count($missing)." Missing: ".implode(', ', $missing)."</h3>";
		$missing_attachments = $att->fetch_rows("ATTACHMENT_CODE in (".implode(', ', $missing).")");

		echo "<pre>";
		var_dump($missing_attachments);
		echo "</pre>";

	
	//$result = $fa->ls('\STRONGTOWER\Exspeedite Build');
	//$result = $fa->file_exists('\STRONGTOWER\Exspeedite Build\exp_editdriver.php');
	//$result = $fa->file_exists('\STRONGTOWER\Exspeedite Build\exp_editdriver2.php');
	//$result = $fa->fetch('\STRONGTOWER\Exspeedite Build\test.txt');

	//$result = $fa->file_exists('\STRONGTOWER\Exspeedite Build\fuzzy2.jpg');
	//$result = $fa->unlink('\STRONGTOWER\Exspeedite Build\fuzzy2.jpg');
	//$result = $fa->file_exists('\STRONGTOWER\Exspeedite Build\fuzzy2.jpg');

	//$result = $fa->fetch('\STRONGTOWER\Exspeedite Build\fuzzy1.jpg');
	//$result2 = $fa->put('\STRONGTOWER\Exspeedite Build\test2\fuzzy2.jpg', $result);
	
	//$result = $fa->mkdir('\STRONGTOWER\Exspeedite Build\test1', 'test1a');

	//$result = $fa->dir_exists('\STRONGTOWER\Exspeedite Build\test1');
	//$result = $fa->dir_exists('\STRONGTOWER\Exspeedite Build\test2');
	//$result = $fa->dir_exists('\STRONGTOWER\Exspeedite Build\test.txt');
	
	//$result = $fa->logout();

} else	
if( isset($_GET['TR'])) {
	require_once( "include/sts_trailer_class.php" );
	$tr = sts_trailer::getInstance( $exspeedite_db, $sts_debug );
	$result = $tr->add_carrier_trailer( $_GET['TR'] );
	echo "<p>Result = $result</p>";
	
} else	
if( isset($_GET['NUM'])) {
	
	$test = '100..0';
	echo "<pre>";
	var_dump($test, is_numeric($test), is_float($test) );
	echo "</pre>";
	$test = '100';
	echo "<pre>";
	var_dump($test, is_numeric($test), is_float($test) );
	echo "</pre>";
	
} else	
if( isset($_GET['TAX'])) {
	require_once( "include/sts_company_tax_class.php" );
	$tax = sts_company_tax::getInstance( $exspeedite_db, $sts_debug );
	echo "<pre>";
	var_dump($tax->lumper_tax_info( 71738 ));
	var_dump($tax->get_issue());
	echo "</pre>";
	
	
} else if( isset($_GET['SESS2'])) {
	require_once( "include/sts_cache_class.php" );
	$cache_table = sts_cache::getInstance( $exspeedite_db, $sts_debug );
	//$cache_table->write_cache();

	//$st = $cache_table->get_state_table( 'client' );
	//$st = $cache_table->get_column_types( 'EXP_SHIPMENT' );
	$st = $cache_table->get_setting( 'company', 'LOGO' );
	$wc = $cache_table->get_whole_cache();
	echo "<pre>";
	var_dump($st, $wc);
	echo "</pre>";
	exit;

} else if( isset($_GET['SESS'])) {
	require_once( "include/sts_user_class.php" );
	require_once( "include/sts_report_class.php" );
	$user_table = sts_user::getInstance( $exspeedite_db, $sts_debug );
	$report_table = sts_report::getInstance( $exspeedite_db, $sts_debug );
	$report_table->user_reports( true );
	
	echo "<pre>";
	var_dump(session_id());
	var_dump($_SESSION);
	//var_dump($b, $d, strcmp($b, $d));
	echo "</pre>";
	
	die;
	
} else if( isset($_GET['PW'])) {
	require_once( "include/sts_user_class.php" );
	$user_table = sts_user::getInstance( $exspeedite_db, false );
	$pw = 'test';
	$db = '$2y$10$l7d/TlZBfoIx2NtxFX7Qz.CExbcViM5nQu.v7a9a5ajRgoQaAXOCi';
	$enc = $user_table->hash( $pw );
	$check = $user_table->verify( $pw, $enc );
	
	$encrypted = crypt( $pw, $db );
	
	echo "<pre>";
	var_dump($pw);
	var_dump($enc);
	var_dump($check);
	var_dump($encrypted);
	var_dump($encrypted == $enc);
	echo "</pre>";
	
	$username = 'duncan';
	$check = $user_table->fetch_rows("USERNAME = '".$username."' AND ISACTIVE = 'Active'",
	 "USER_CODE, USERNAME, USER_GROUPS, FULLNAME, EMAIL, USER_PASSWORD");
	 
	$result = $user_table->verify( $pw, $check[0]['USER_PASSWORD'] );
	
	$result2 = password_verify( $pw, $check[0]['USER_PASSWORD'] );
	
	echo "<pre>";
	var_dump($username);
	var_dump($pw);
	var_dump($result);
	var_dump($result2);
	echo "</pre>";
	
	$enc2 = sodium_crypto_pwhash_str( $pw, 
			    SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
			    SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE);
			    
	$result3 = sodium_crypto_pwhash_str_verify( $enc2, $pw );
	
	echo "<pre>";
	var_dump($enc2);
	var_dump($result3);
	echo "</pre>";
	
	$msg = 'This is a test.';
	$enc = $user_table->encryptData( $msg );
	
	$enc2 = '3T4wigBCdh8xD+DPYiKECfRoPjnBBbWyyxgTqrdww0gKATapOk8ZU4lX0j53KP3IxtpldQhzOw==';
	
	$dec = $user_table->decryptData( $enc );
	$dec2 = $user_table->decryptData( $enc2 );
	
	echo "<pre>encryptData\n";
	var_dump($msg);
	var_dump($enc);
	var_dump($dec);
	var_dump($enc2);
	var_dump($dec2);
	echo "</pre>";
	

	
	
} else
if( isset($_GET['CHRIS'])) {
	require_once( "include/sts_report_class.php" );
	$report_table = sts_report::getInstance( $exspeedite_db, false );
	$_SESSION['EXT_USERNAME'] = 'CHRIS KITCHEN';
	$_SESSION['EXT_USER_CODE'] = 47;
	$_SESSION['EXT_GROUPS'] = 'user,shipments,dispatch,billing,profiles,fleet';
	$report_table->user_reports( true );

	$rmr =  is_array($report_table->reports) &&
		count($report_table->reports) > 0 &&
		array_search('R&M Report', array_column($report_table->reports, 'REPORT_NAME')) !== false; 

	echo "<pre>";
	var_dump($_SESSION['EXT_USERNAME']);
	var_dump($_SESSION['EXT_USER_CODE']);
	var_dump($_SESSION['EXT_GROUPS']);
	var_dump($report_table->reports);
	var_dump(array_column($report_table->reports, 'REPORT_NAME'));
	var_dump(array_search('R&M Report', array_column($report_table->reports, 'REPORT_NAME')));
	var_dump($rmr);
	echo "</pre>";
	
} else
if( isset($_GET['CODE'])) {
	require_once( "include/sts_shipment_class.php" );
	$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
	echo "<pre>";
	var_dump($_GET['CODE']);
	var_dump(is_numeric($_GET['CODE']));
	echo "</pre>";

	if( isset($_GET['CODE']) && ! is_numeric($_GET['CODE'])) {
		$chk = $shipment_table->fetch_rows("SS_NUMBER = '".$_GET['CODE']."'", "SHIPMENT_CODE");
		echo "<pre>";
		var_dump($chk);
		echo "</pre>";
		if( is_array($chk) && count($chk) == 1 &&
			 isset($chk[0]["SHIPMENT_CODE"]) && $chk[0]["SHIPMENT_CODE"] > 0 &&
			 $chk[0]["SHIPMENT_CODE"] <> $_GET['CODE'] ) {
			echo "<p>reload</p>";
		}
	}
	
	
} else if( isset($_GET['VAL'])) {
	require_once( "include/sts_zip_class.php" );
	require_once( "PCMILER/exp_get_miles.php" );
	$sts_debug = true;
	//$zip_table = new sts_zip($exspeedite_db, $sts_debug);
	
	$pcm = sts_pcmiler_api::getInstance( $exspeedite_db, $sts_debug );

	$address = array( 'ZIP_CODE' => $_GET['VAL'] );
	$check = $pcm->validate_address( $address );
	
	if( $check == 'valid' && isset($pcm->pcm_result) ) {
		$country = (string) $pcm->pcm_result->Address->CountryAbbreviation;
		$state = (string) $pcm->pcm_result->Address->State;
		$city = (string) $pcm->pcm_result->Address->City;
		$zip = (string) $pcm->pcm_result->Address->Zip;
	}

	
	echo "<pre>Result for ".$_GET['VAL']."\n";
	var_dump($check, $country, $state, $city, $zip, $pcm->lat, $pcm->lon);
	echo "</pre>";
	die;
	
} else
if( isset($_GET['IR'])) {
	require_once( "include/sts_insp_report_class.php" );
	$ir = sts_insp_report_grid::getInstance($exspeedite_db, $sts_debug);
	$result = $ir->fetch_rows();
	echo "<pre>IR:\n";
	var_dump($result);
	echo "</pre>";
	
	die;
} else
if( isset($_GET['RES'])) {
	echo '<div class="container theme-showcase" role="main">

	<form role="form" class="form-horizontal" action="test11.php" 
				method="post" enctype="multipart/form-data" 
				name="res" id="res">
		<textarea class="form-control" name="SQL" id="SQL"  
				placeholder="SQL" maxlength="16777215" required rows="20">
		</textarea>
		<button class="btn btn-sm btn-success" name="save" type="submit" >Check for reserved words</button> 
		</form>
		</div>
		';
} else
if( isset($_POST['SQL'])) {
	echo '<pre>'.$exspeedite_db->highlight_reserved( $_POST['SQL'] ).'</pre>';
	

}


require_once( "include/footer_inc.php" );
?>