<?php 

// $Id: exp_filtershipment.php 5030 2023-04-12 20:31:34Z duncan $
//! Part of the select shipments for a load page
// Called via ajax to load the left column with suitable shipments

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Set flag that this is session readonly
define( '_STS_SESSION_READONLY', 1 );

// Set flag that this is an ajax call
define( '_STS_SESSION_AJAX', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[SHIPMENT_TABLE] );	// Make sure we should be here

require_once( "include/sts_shipment_class.php" );
require_once( "include/sts_result_class.php" );
require_once( "include/sts_office_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_multi_currency = $setting_table->get( 'option', 'MULTI_CURRENCY' ) == 'true';
$sts_cutoff = $setting_table->get( 'option', 'SHIPMENT_CUTOFF' );
$sts_sort_shipcode = $setting_table->get( 'option', 'SELSHIPMENT_DEFSHIPCODE' ) == 'true';

$office_table = sts_office::getInstance($exspeedite_db, $sts_debug);

$multi_company = $office_table->multi_company();

$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET['load']) && isset($_GET['pw']) && $_GET['pw'] == 'Cheerios' ) {
	$assign_state = isset($shipment_table->behavior_state['assign']) ?
		$shipment_table->behavior_state['assign'] : 0;
	assert( $assign_state > 0, "Unable to find assign status code" );
	$docked_state = isset($shipment_table->behavior_state['docked']) ?
		$shipment_table->behavior_state['docked'] : 0;
	assert( $docked_state > 0, "Unable to find docked status code" );

	$condition = "LOAD_CODE = 0 AND CURRENT_STATUS IN(".$assign_state.",  ".$docked_state.")";
	if( isset($_GET['shipper_zone']) && $_GET['shipper_zone'] <> '' )
		$condition .= " AND MATCH_ZONE(SHIPPER_ZIP, '".$_GET['shipper_zone']."')";
	
	if( isset($_GET['cons_zone']) && $_GET['cons_zone'] <> '' )
		$condition .= " AND MATCH_ZONE(CONS_ZIP, '".$_GET['cons_zone']."')";
		
	//! SCR# 405 - Optionally restrict which shipments can be added to load by date
	// NOTE (4/7/2022) - this doesn't work if the shipment has no dates,
	// because GET_ASOF() will return the current date.
	if( ! $my_session->in_group(EXT_GROUP_ADMIN) &&
		! empty($sts_cutoff) &&
		//! SCR# 587 - Less strict date checking
		checkIsAValidDate($sts_cutoff) ) {
		//! SCR# 419 - use GET_ASOF rather than created date
		$condition .= " AND DATE(GET_ASOF(SHIPMENT_CODE)) >= DATE('".date("Y-m-d", strtotime($sts_cutoff))."')";
	}
	
	//! SCR# 639 - identify if non C-TPAT carrier already assigned.
	$check = $exspeedite_db->get_one_row("
		SELECT COALESCE(
		(SELECT CTPAT_CERTIFIED FROM EXP_CARRIER WHERE CARRIER_CODE = CARRIER), FALSE) AS CTPAT_CERTIFIED
		FROM EXP_LOAD
		WHERE LOAD_CODE = ".$_GET['load']."
		AND CARRIER > 0");
	$no_ctpat = is_array($check) && isset($check["CTPAT_CERTIFIED"]) && $check["CTPAT_CERTIFIED"] == 0;

	$condition = $office_table->office_code_match_load( $_GET['load'], $condition );
	
	$sort_order = $sts_sort_shipcode ? "SHIPMENT_CODE ASC" : "PICKUP_DATE ASC, DELIVER_DATE ASC, CONS_NAME ASC";

	$available = $shipment_table->database->get_multiple_rows("
		select SHIPMENT_CODE, 
		(CASE WHEN DOCKED_AT > 0 THEN (SELECT LOAD_CODE FROM EXP_STOP
			WHERE STOP_CODE = DOCKED_AT) ELSE NULL END ) AS PREV_LOAD,
		(CASE WHEN CURRENT_STATUS = ".$docked_state." THEN 
			s.STOP_NAME
		ELSE SHIPPER_NAME END ) AS SHIPPER_NAME,
		(CASE WHEN CURRENT_STATUS = ".$docked_state." THEN 
			s.STOP_CITY 
		ELSE SHIPPER_CITY END ) AS SHIPPER_CITY,
		(CASE WHEN CURRENT_STATUS = ".$docked_state." THEN 
			s.STOP_STATE 
		ELSE SHIPPER_STATE END ) AS SHIPPER_STATE,
		(CASE WHEN CURRENT_STATUS = ".$docked_state." THEN 
			s.STOP_ZIP
		ELSE SHIPPER_ZIP END ) AS SHIPPER_ZIP,
		SHIPMENT_CTPAT(SHIPMENT_CODE) AS CTPAT_REQUIRED,
		(SELECT TOTAL FROM EXP_CLIENT_BILLING
		WHERE SHIPMENT_ID = SHIPMENT_CODE) AS TOTAL,
		(SELECT CURRENCY FROM EXP_CLIENT_BILLING
		WHERE SHIPMENT_ID = SHIPMENT_CODE) AS CURRENCY,
		CONS_NAME, CONS_CITY, CONS_STATE, CONS_ZIP, CONS_TERMINAL, PALLETS, WEIGHT,
		PICKUP_DATE, PICKUP_TIME_OPTION, PICKUP_TIME1, PICKUP_TIME2,
		DELIVER_DATE, DELIVER_TIME_OPTION, DELIVER_TIME1, DELIVER_TIME2,
		CURRENT_STATUS, SS_NUMBER, PO_NUMBER, PO_NUMBER2, PO_NUMBER3, PO_NUMBER4, PO_NUMBER5, LOAD_CODE, ST_NUMBER,
		BILLTO_NAME, s.STOP_NAME
		from EXP_SHIPMENT
        LEFT JOIN (SELECT STOP_CODE, STOP_NAME, STOP_CITY, STOP_STATE, STOP_ZIP FROM EXP_STOP
			WHERE stop_type = 'stop') as s
        on s.STOP_CODE = DOCKED_AT
		where $condition
		".($no_ctpat ? " AND NOT SHIPMENT_CTPAT(SHIPMENT_CODE)" : "")."
		order by $sort_order
	");
	
	if( false && $sts_debug ) {
		echo "<p>available = </p>
		<pre>";
		var_dump($available);
		echo "</pre>";
	} else {
		//! SCR# 211 - test if anything available
		if( is_array($available) && count($available) > 0 ) {
			foreach( $available as $row) {
				echo '<li data-shipment="'.$row['SHIPMENT_CODE'].'"
					data-shipper="'.$row['SHIPPER_NAME'].'" 
					data-consignee="'.$row['CONS_NAME'].'"
					data-billto="'.$row['BILLTO_NAME'].'"
					data-pallets="'.$row['PALLETS'].'"
					data-weight="'.$row['WEIGHT'].'"
					data-pickup="'.$row['PICKUP_DATE'].'" 
					data-deliver="'.$row['DELIVER_DATE'].'" 
					data-shipper-city="'.$row['SHIPPER_CITY'].'"
					data-shipper-state="'.$row['SHIPPER_STATE'].'" 
					data-cons-city="'.$row['CONS_CITY'].'" 
					data-cons-state="'.$row['CONS_STATE'].'"><strong><a href="exp_addshipment.php?CODE='.$row['SHIPMENT_CODE'].'" target="_blank" title="edit shipment '.$row['SHIPMENT_CODE'].' in new window">#'.$row['SHIPMENT_CODE'].'</a>'.
					($multi_company && isset($row['SS_NUMBER']) && $row['SS_NUMBER'] <> '' ? ' ('.$row['SS_NUMBER'].')' : '')
					.'</strong> '.$row['SHIPPER_NAME'].($row['CURRENT_STATUS'] == $docked_state ? ' <span class="badge" title="Docked from load #'.$row['PREV_LOAD'].'">D</span>' : '').' -> '.$row['CONS_NAME'].'<br>';
	
				$po_numbers = array('PO_NUMBER', 'PO_NUMBER2', 'PO_NUMBER3', 'PO_NUMBER4', 'PO_NUMBER5');
				$active_pos = array();
				foreach( $po_numbers as $po ) {
					if( isset($row[$po]) && $row[$po] <> '' )
						$active_pos[] = $row[$po];
				}
				if( count($active_pos) > 0 )
					echo 'PO#s: '.implode(', ', $active_pos).'<br>';
	
				echo $row['SHIPPER_CITY'].', '.$row['SHIPPER_STATE'].', '.$row['SHIPPER_ZIP'].' -> '.$row['CONS_CITY'].', '.$row['CONS_STATE'].', '.$row['CONS_ZIP'].'<br>'.
					sts_result::duedate( $row['PICKUP_DATE'], $row['PICKUP_TIME_OPTION'], $row['PICKUP_TIME1'], $row['PICKUP_TIME2'], ' ' )
					.' -> '.
					sts_result::duedate( $row['DELIVER_DATE'], $row['DELIVER_TIME_OPTION'], $row['DELIVER_TIME1'], $row['DELIVER_TIME2'], ' ' ).
					'<br>'.
					(empty($row['ST_NUMBER']) ? '' : '<strong>Container: '.$row['ST_NUMBER'].'</strong><br>').
					'<strong>'.$row['PALLETS'].' pallets</strong>, <strong>'.$row['WEIGHT'].' lb'.
			(empty($row['TOTAL']) ? '' : ', $'.number_format($row['TOTAL'],2).($sts_multi_currency ? ' '.$row['CURRENCY'] : ''))
			.($row['CTPAT_REQUIRED'] ? ' <span class="badge" title="Customs-Trade Partnership Against Terrorism">C-TPAT</span>' : '')
			.'</strong></li>';
			}
		} else {
			echo '<p class="text-center text-muted fixed"><strong><span class="glyphicon glyphicon-warning-sign"></span> No matching shipments.</strong><br>Make sure shipments are ready for dispatch,<br>or adjust filter above.</p>';
		}
	}
}

?>