<?php 

// $Id: exp_create_td.php 3884 2020-01-30 00:21:42Z duncan $
// Create test data
// Example:
// exp_create_td.php?PW=Geocaching&TYPE=driver&ADDR=B


// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

//$sts_subtitle = "Test Data";
//require_once( "include/header_inc.php" );

function test_address( $choice = 'A' ) {
	switch( $choice ) {
		case 'A':
		default:
			$result = array(
				'ADDRESS' => '7358 Birch Bay Drive',
				'CITY' => 'Blaine',
				'STATE' => 'WA',
				'ZIP_CODE' => '98230',
				'COUNTRY' => 'USA',
				'PHONE_CELL' => '360-555-1212',
				'EMAIL' => 'user@domain.com' );
			break;
		case 'B':
			$result = array(
				'ADDRESS' => '20486 64 Ave',
				'CITY' => 'Langley City',
				'STATE' => 'BC',
				'ZIP_CODE' => 'V2Y 2V5',
				'COUNTRY' => 'Canada',
				'PHONE_CELL' => '360-555-1212',
				'EMAIL' => 'tmw@isuck.com' );
			break;
		case 'C':
			$result = array(
				'ADDRESS' => 'One Apple Park Way',
				'CITY' => 'Cupertino',
				'STATE' => 'CA',
				'ZIP_CODE' => '95014',
				'COUNTRY' => 'USA',
				'PHONE_CELL' => '(408) 996–1010',
				'EMAIL' => 'apple@waycool.com' );
			break;
	}
	return $result;
}

if( isset($_GET['TYPE']) &&
	isset($_GET['PW']) && $_GET['PW'] == 'Geocaching' ) {

	switch( $_GET['TYPE'] ) {
		case 'driver':
			require_once( "include/sts_driver_class.php" );
			require_once( "include/sts_setting_class.php" );
			
			$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
			$driver_table = sts_driver::getInstance($exspeedite_db, $sts_debug);
			
			if( isset($_GET['DEL']) ) {
				$result = $driver_table->delete($_GET['DEL'],'permdel');
				
				echo '<p id="RESULT">'.($result ? "true" : "false").'</p>';
			} else {			
				$multi_company = ($setting_table->get("option", "MULTI_COMPANY") == 'true');
				$rand = substr(md5(microtime()),rand(0,26),5);
				
				$fields = array(
					'FIRST_NAME' => 'TestData_First'.$rand,
					'LAST_NAME' => 'TestData_Last'.$rand,
					'EMPLOYEE_NUMBER' => $rand,
					'DRIVER_NUMBER' => $rand,
					'DRIVER_NOTES' => 'Test Data',
					'BIRTHDATE' => '1982-01-01',
					'START_DATE' => '2018-01-01',
					'PHYSICAL_DUE' => '2030-01-01',
					'WORK_TYPE' => 'OTR',				
				);
				if( $multi_company ) {
					$check = $exspeedite_db->get_one_row("
						SELECT MIN(C.COMPANY_CODE) AS CNO, MIN(O.OFFICE_CODE) AS ONO
						FROM EXP_COMPANY C, EXP_OFFICE O
						WHERE C.COMPANY_CODE = O.COMPANY_CODE");
					if( is_array($check) && count($check) == 2 ) {
						$fields['COMPANY_CODE'] = $check['CNO'];
						$fields['OFFICE_CODE'] = $check['ONO'];
					}
				}
				$result = $driver_table->add( $fields );
				
				if( $result ) {
					require_once( "include/sts_contact_info_class.php" );
					$contact_info_table = new sts_contact_info($exspeedite_db, $sts_debug);
					$ci = array(
						'CONTACT_CODE' => $result,
						'CONTACT_SOURCE' => 'driver',
						'CONTACT_TYPE' => 'individual',
						'CONTACT_NAME' => 'TestData_Name'.$rand
					);
					$ci = array_merge($ci, test_address( isset($_GET['ADDR']) ? $_GET['ADDR'] : 'A') );
					$contact_info_table->add( $ci );
	
					require_once( "include/sts_license_class.php" );
					$license_table = new sts_license($exspeedite_db, $sts_debug);
					$license_table->add( array(
						'CONTACT_CODE' => $result,
						'CONTACT_SOURCE' => 'driver',
						'LICENSE_NUMBER' => 'TestData_Number'.$rand,
						'LICENSE_STATE' => $ci['STATE'],
						'LICENSE_EXPIRY_DATE' => '2030-01-01',
						'LICENSE_NOTES' => 'Test Data'
					) );
	
				}
				
				echo '<p id="RESULT">'.$result.'</p>';
			}

			break;
		default:
			break;
	}


}
?>