<?php 

// $Id: exp_find_carrier.php 5166 2023-07-23 01:27:41Z dev $
// List loads - client view.

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

// We want to use TableTools with DataTables
define( '_STS_TABLETOOLS', 1 );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug']) || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_SHIPMENTS );	// Make sure we should be here

require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance( $exspeedite_db, false );
$sts_home_currency = $setting_table->get( 'option', 'HOME_CURRENCY' );
$sts_years = $setting_table->get( 'option', 'FIND_CARRIER_YEARS' );
$sts_find_carrier_bc = $setting_table->get( 'option', 'FIND_CARRIER_BC' );
$sts_cutoff = $setting_table->get( 'option', 'SHIPMENT_CUTOFF' );

if( is_array($_GET) && isset($_GET["pw"]) && $_GET["pw"] == 'Portable' &&
	isset($_GET["sh"]) && isset($_GET["bc"]) ) {
	// update business code for shipment
	$sh_upd = $setting_table->database->get_one_row("
		UPDATE EXP_SHIPMENT SET BUSINESS_CODE = ".$_GET["bc"]."
		WHERE SHIPMENT_CODE = ".$_GET["sh"]);

	if( empty($sts_find_carrier_bc) ) {
		$find_carrier = true;
	} else {			
		$fc_bc = $setting_table->database->get_one_row("
			select BC_NAME
			FROM EXP_BUSINESS_CODE 
			where APPLIES_TO = 'shipment'
			AND BUSINESS_CODE = ".$_GET["bc"]);
		$find_carrier = is_array($fc_bc) && count($fc_bc) == 1 &&
			isset($fc_bc["BC_NAME"]) &&
			in_array($fc_bc["BC_NAME"], explode(',', $sts_find_carrier_bc));
	}	

	if( $sts_debug ) {
		echo "<p>find_carrier = </p>
		<pre>";
		var_dump($find_carrier);
		echo "</pre>";
	} else {
		echo json_encode( $find_carrier );
	}
} else {

$sts_subtitle = "Find Carrier For Shipment";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

function nm0( $x ) {
	return isset($x) && is_numeric($x) && $x != 0 ? number_format($x,0):'';
}

function isok( $x ) {
	return isset($x) && $x ? '<span class="glyphicon glyphicon-ok text-success"></span>':'<span class="glyphicon glyphicon-remove text-danger"></span>';
	
}

function get_carriers( $shipment_table, $code, $currency, $years, $debug = false ) {
	$how_far_back = $years * 12;
	$check = $shipment_table->database->multi_query("
		SET @HC = HOME_CURRENCY(), @AO = GET_ASOF($code);

		SELECT COALESCE(MAX(C.GENERAL_LIAB_INS) *
				CONVERT_RATE( GET_ASOF(S.SHIPMENT_CODE), C.INSURANCE_CURRENCY, @HC),0) AS GENERAL_LIAB_INS,
			COALESCE(MAX(C.AUTO_LIAB_INS) *
				CONVERT_RATE( GET_ASOF(S.SHIPMENT_CODE), C.INSURANCE_CURRENCY, @HC),0) AS AUTO_LIAB_INS,
			COALESCE(MAX(C.CARGO_LIAB_INS) *
				CONVERT_RATE( GET_ASOF(S.SHIPMENT_CODE), C.INSURANCE_CURRENCY, @HC),0) AS CARGO_LIAB_INS,
			COALESCE(C.CTPAT_REQUIRED,0) AS CTPAT_REQUIRED
		INTO @GEN, @AUTO, @CARGO, @CTPAT		
		FROM EXP_SHIPMENT S, EXP_CLIENT C
		WHERE S.SHIPMENT_CODE = $code
		AND S.BILLTO_CLIENT_CODE = C.CLIENT_CODE;

		SELECT CARRIER_CODE, CARRIER_NAME, SHIPPER, CONS,
			INS_SUFF, CTPAT_CERTIFIED, CTPAT_SUFF, NUM,
			ROUND(COST/NUM) AS AVG_COST,
		    ROUND(REVENUE/NUM) AS AVG_REVENUE,
		    ROUND((REVENUE-COST)/NUM) AS AVG_MARGIN
		FROM (
			SELECT C.CARRIER_CODE, C.CARRIER_NAME,
				CONCAT(S1.SHIPPER_CITY, ', ', S1.SHIPPER_STATE) AS SHIPPER,
			    CONCAT(S1.CONS_CITY, ', ', S1.CONS_STATE) AS CONS, S1.BILLTO_NAME, 
                (CHECK_CARRIER_INS3(CARRIER_CODE, @AO, @GEN, @AUTO, @CARGO) AND
                	CARRIER_EXPIRED(CARRIER_CODE) <> 'red') AS INS_SUFF,
            	COALESCE(C.CTPAT_CERTIFIED,0) AS CTPAT_CERTIFIED,
                COALESCE(C.CTPAT_CERTIFIED,0) >= @CTPAT AS CTPAT_SUFF,			    
			    COUNT(*) NUM,
			    ROUND(CONVERT_LOAD_TO_HOME(L.LOAD_CODE,
			    	SUM(COALESCE(L.CARRIER_BASE,0) +
					COALESCE(L.CARRIER_HANDLING,0) +
					COALESCE(L.CARRIER_FSC,0)))) COST,
				ROUND(CONVERT_TO_HOME(S1.SHIPMENT_CODE, COALESCE(SUM(S1.TOTAL_CHARGES),0)), 2) REVENUE
			FROM EXP_CARRIER C, EXP_LOAD L, EXP_SHIPMENT S1, EXP_SHIPMENT S2
			WHERE C.ISDELETED = FALSE
			AND L.CARRIER = C.CARRIER_CODE
			AND L.CARRIER > 0
			AND L.CURRENT_STATUS IN (33, 34)
			AND L.COMPLETED_DATE > DATE_SUB(NOW(), INTERVAL ".$how_far_back." MONTH)
			AND L.LOAD_CODE = S1.LOAD_CODE
			AND S1.SHIPPER_CITY = S2.SHIPPER_CITY
			AND S1.SHIPPER_STATE = S2.SHIPPER_STATE
			AND S1.CONS_CITY = S2.CONS_CITY
			AND S1.CONS_STATE = S2.CONS_STATE
			AND S2.SHIPMENT_CODE = $code
			GROUP BY C.CARRIER_CODE, C.CARRIER_NAME, S1.SHIPPER_STATE, S1.CONS_STATE
			ORDER BY 8 DESC
			LIMIT 20) X
		");

	if( ! is_array($check) || count($check) < 20 ) {
		$check2 = $shipment_table->database->multi_query("
		SET @HC = HOME_CURRENCY(), @AO = GET_ASOF($code);

		SELECT COALESCE(MAX(C.GENERAL_LIAB_INS) *
				CONVERT_RATE( GET_ASOF(S.SHIPMENT_CODE), C.INSURANCE_CURRENCY, @HC),0) AS GENERAL_LIAB_INS,
			COALESCE(MAX(C.AUTO_LIAB_INS) *
				CONVERT_RATE( GET_ASOF(S.SHIPMENT_CODE), C.INSURANCE_CURRENCY, @HC),0) AS AUTO_LIAB_INS,
			COALESCE(MAX(C.CARGO_LIAB_INS) *
				CONVERT_RATE( GET_ASOF(S.SHIPMENT_CODE), C.INSURANCE_CURRENCY, @HC),0) AS CARGO_LIAB_INS,
			COALESCE(C.CTPAT_REQUIRED,0) AS CTPAT_REQUIRED
		INTO @GEN, @AUTO, @CARGO, @CTPAT		
		FROM EXP_SHIPMENT S, EXP_CLIENT C
		WHERE S.SHIPMENT_CODE = $code
		AND S.BILLTO_CLIENT_CODE = C.CLIENT_CODE;

		SELECT CARRIER_CODE, CARRIER_NAME, SHIPPER, CONS,
			INS_SUFF, CTPAT_CERTIFIED, CTPAT_SUFF, NUM,
			ROUND(COST/NUM) AS AVG_COST,
		    ROUND(REVENUE/NUM) AS AVG_REVENUE,
		    ROUND((REVENUE-COST)/NUM) AS AVG_MARGIN
		FROM (
			SELECT C.CARRIER_CODE, C.CARRIER_NAME, S1.SHIPPER_STATE AS SHIPPER, S1.CONS_STATE AS CONS,
				S1.BILLTO_NAME, 
                (CHECK_CARRIER_INS3(CARRIER_CODE, @AO, @GEN, @AUTO, @CARGO) AND
                	CARRIER_EXPIRED(CARRIER_CODE) <> 'red') AS INS_SUFF,
                COALESCE(C.CTPAT_CERTIFIED,0) AS CTPAT_CERTIFIED,
                COALESCE(C.CTPAT_CERTIFIED,0) >= @CTPAT AS CTPAT_SUFF,				
				COUNT(*) NUM,
			    ROUND(CONVERT_LOAD_TO_HOME(L.LOAD_CODE,
			    	SUM(COALESCE(L.CARRIER_BASE,0) +
					COALESCE(L.CARRIER_HANDLING,0) +
					COALESCE(L.CARRIER_FSC,0)))) COST,
				ROUND(CONVERT_TO_HOME(S1.SHIPMENT_CODE, COALESCE(SUM(S1.TOTAL_CHARGES),0)), 2) REVENUE
			FROM EXP_CARRIER C, EXP_LOAD L, EXP_SHIPMENT S1, EXP_SHIPMENT S2
			WHERE C.ISDELETED = FALSE
			AND L.CARRIER = C.CARRIER_CODE
			AND L.CARRIER > 0
			AND L.CURRENT_STATUS IN (33, 34)
			AND L.COMPLETED_DATE > DATE_SUB(NOW(), INTERVAL ".$how_far_back." MONTH)
			AND L.LOAD_CODE = S1.LOAD_CODE
			AND S1.SHIPPER_STATE = S2.SHIPPER_STATE
			AND S1.CONS_STATE = S2.CONS_STATE
			AND S2.SHIPMENT_CODE = $code
			GROUP BY C.CARRIER_CODE, C.CARRIER_NAME, S1.SHIPPER_STATE, S1.CONS_STATE
			ORDER BY 8 DESC
			LIMIT 20) X
		");
		
		if( is_array($check2) && count($check2) > 0 ) {
			if( ! is_array($check) )
				$check = array();
			foreach( $check2 as $row ) {
				$already_exists = false;
				if( count($check) > 0 ) {
					foreach($check as $row2) {
						if( $row2['CARRIER_CODE'] == $row['CARRIER_CODE'] )
						$already_exists = true;
					}
				}
				
				if( ! $already_exists )
					$check[] = $row;
			}
		}
	}

	if( ! is_array($check) || count($check) < 0 ) {
		$check3 = $shipment_table->database->multi_query("
		SET @HC = HOME_CURRENCY(), @AO = GET_ASOF($code);

		SELECT COALESCE(MAX(C.GENERAL_LIAB_INS) *
				CONVERT_RATE( GET_ASOF(S.SHIPMENT_CODE), C.INSURANCE_CURRENCY, @HC),0) AS GENERAL_LIAB_INS,
			COALESCE(MAX(C.AUTO_LIAB_INS) *
				CONVERT_RATE( GET_ASOF(S.SHIPMENT_CODE), C.INSURANCE_CURRENCY, @HC),0) AS AUTO_LIAB_INS,
			COALESCE(MAX(C.CARGO_LIAB_INS) *
				CONVERT_RATE( GET_ASOF(S.SHIPMENT_CODE), C.INSURANCE_CURRENCY, @HC),0) AS CARGO_LIAB_INS,
			COALESCE(C.CTPAT_REQUIRED,0) AS CTPAT_REQUIRED
		INTO @GEN, @AUTO, @CARGO, @CTPAT		
		FROM EXP_SHIPMENT S, EXP_CLIENT C
		WHERE S.SHIPMENT_CODE = $code
		AND S.BILLTO_CLIENT_CODE = C.CLIENT_CODE;

		SELECT CARRIER_CODE, CARRIER_NAME, SHIPPER, CONS,
			INS_SUFF, CTPAT_CERTIFIED, CTPAT_SUFF, NUM,
			ROUND(COST/NUM) AS AVG_COST,
		    ROUND(REVENUE/NUM) AS AVG_REVENUE,
		    ROUND((REVENUE-COST)/NUM) AS AVG_MARGIN
		FROM (
			SELECT C.CARRIER_CODE, C.CARRIER_NAME, S1.SHIPPER_STATE AS SHIPPER, S1.CONS_STATE AS CONS,
				S1.BILLTO_NAME, 
                (CHECK_CARRIER_INS3(CARRIER_CODE, @AO, @GEN, @AUTO, @CARGO) AND
                	CARRIER_EXPIRED(CARRIER_CODE) <> 'red') AS INS_SUFF,
                COALESCE(C.CTPAT_CERTIFIED,0) AS CTPAT_CERTIFIED,
                COALESCE(C.CTPAT_CERTIFIED,0) >= @CTPAT AS CTPAT_SUFF,				
				COUNT(*) NUM,
			    ROUND(CONVERT_LOAD_TO_HOME(L.LOAD_CODE,
			    	SUM(COALESCE(L.CARRIER_BASE,0) +
					COALESCE(L.CARRIER_HANDLING,0) +
					COALESCE(L.CARRIER_FSC,0)))) COST,
				ROUND(CONVERT_TO_HOME(S1.SHIPMENT_CODE, COALESCE(SUM(S1.TOTAL_CHARGES),0)), 2) REVENUE
			FROM EXP_CARRIER C, EXP_LOAD L, EXP_SHIPMENT S1, EXP_SHIPMENT S2
			WHERE C.ISDELETED = FALSE
			AND L.CARRIER = C.CARRIER_CODE
			AND L.CARRIER > 0
			AND L.CURRENT_STATUS IN (33, 34)
			AND L.COMPLETED_DATE > DATE_SUB(NOW(), INTERVAL ".$how_far_back." MONTH)
			AND L.LOAD_CODE = S1.LOAD_CODE
			AND S1.SHIPPER_STATE = (SELECT abbrev FROM EXP_STATES
				WHERE ADJACENT LIKE CONCAT('%', S2.SHIPPER_STATE, '%')
				LIMIT 1)
			AND S1.CONS_STATE = (SELECT abbrev FROM EXP_STATES
				WHERE ADJACENT LIKE CONCAT('%', S2.CONS_STATE, '%')
				LIMIT 1)
			AND S2.SHIPMENT_CODE = $code
			GROUP BY C.CARRIER_CODE, C.CARRIER_NAME, S1.SHIPPER_STATE, S1.CONS_STATE
			ORDER BY 8 DESC
			LIMIT 20) X
		");
		
		if( is_array($check3) && count($check3) > 0 ) {
			if( ! is_array($check) )
				$check = array();
			foreach( $check3 as $row ) {
				$already_exists = false;
				if( count($check) > 0 ) {
					foreach($check as $row2) {
						if( $row2['CARRIER_CODE'] == $row['CARRIER_CODE'] )
						$already_exists = true;
					}
				}
				
				if( ! $already_exists )
					$check[] = $row;
			}
		}
	}

	if( is_array($check) && count($check) > 0 ) {
		echo '<form class="form-inline" role="form" action="exp_find_carrier.php" 
		method="post" enctype="multipart/form-data" name="CARRIER_FORM" id="CARRIER_FORM">
		<input name="SHIPMENT_CODE" type="hidden" value="'.$code.'">
		'.( $debug ? '<input name="debug" type="hidden" value="on">' : '').'
		<div class="well well-sm">
		<h2>Potential Carriers (From the last '.$years.' years, Cost/Revenue in '.$currency.')</h2>
		
		<table class="display table table-striped table-condensed smallmargin" id="CARRIERS">
		<thead>
			<tr class="exspeedite-bg">
				<th>SELECT</th>
				<th>CARRIER</th>
				<th>SHIPPER</th>
				<th>CONSIGNEE</th>
				<th>INS</th>
				<th>CTPAT</th>
				<th class="text-right">#ROUTES</th>
				<th class="text-right">AVG_COST</th>
				<th class="text-right">AVG_REVENUE</th>
				<th class="text-right">AVG_MARGIN</th>
			</tr>
		</thead>
		<tbody>
		';
		foreach( $check as $row ) {
			echo '<tr>
				<td>'.($row["INS_SUFF"] && $row["CTPAT_SUFF"] ?
					'<button class="btn btn-sm btn-success" name="CARRIER_CODE" value="'.$row['CARRIER_CODE'].'"><span class="glyphicon glyphicon-ok"></span></button>' :'<a class="btn btn-sm btn-danger disabled tip" title="Check insurance or CTPAT requirements" name="disabled"><span class="glyphicon glyphicon-remove"></span></a>').'</td>
				<td><a href="exp_editcarrier.php?CODE='.$row['CARRIER_CODE'].'" target="_blank">'.$row["CARRIER_NAME"].'</a></td>
				<td>'.$row["SHIPPER"].'</td>
				<td>'.$row["CONS"].'</td>
				<td>'.isok($row["INS_SUFF"]).'</td>
				<td>'.isok($row["CTPAT_CERTIFIED"]).'</td>
				<td class="text-right">'.nm0($row["NUM"]).'</td>
				<td class="text-right">'.nm0($row["AVG_COST"]).'</td>
				<td class="text-right">'.nm0($row["AVG_REVENUE"]).'</td>
				<td class="text-right">'.nm0($row["AVG_MARGIN"]).'</td>
			</tr>
			';		
		}
		echo '</tbody>
		</table>
		</div>
		</form>
		';
	} else {
		echo '<div class="well well-sm">
		<h2>No matching carriers found within the last '.$years.' years</h2>
		<p>Suggest you create a load, add the shipment and manually select a carrier</p>
		</div>
		';
	}
}

if( isset($_POST['SHIPMENT_CODE']) ) { //! Confirm action
	require_once( "include/sts_carrier_class.php" );
	require_once( "include/sts_shipment_class.php" );
	
	$carrier_table = sts_carrier::getInstance($exspeedite_db, $sts_debug);
	$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);

	echo '<div class="container" role="main">
	';
		
	//echo "<pre>";
	//var_dump($_POST);
	//echo "</pre>";
	
	$check1 = $shipment_table->fetch_rows($shipment_table->primary_key." = ".$_POST['SHIPMENT_CODE'],
		"SS_NUMBER, OFFICE_CODE, (SELECT OFFICE_NAME FROM EXP_OFFICE WHERE EXP_OFFICE.OFFICE_CODE = EXP_SHIPMENT.OFFICE_CODE) AS OFFICE_NAME");

	$check2 = $carrier_table->fetch_rows($carrier_table->primary_key." = ".$_POST['CARRIER_CODE'],
		"CARRIER_NAME");
		
	if( is_array($check1) && count($check1) == 1 &&
		is_array($check2) && count($check2) == 1 ) {
		
		$shipment_link = '<a href="exp_addshipment.php?CODE='.$_POST['SHIPMENT_CODE'].'">'.$_POST['SHIPMENT_CODE'].(empty($check1[0]["SS_NUMBER"]) ? '' : '/'.$check1[0]["SS_NUMBER"]).'</a>';
		
		$office_link = '<a href="exp_editoffice.php?CODE='.$check1[0]["OFFICE_CODE"].'">'.$check1[0]["OFFICE_NAME"].'</a>';
		
		$carrier_link = '<a href="exp_editcarrier.php?CODE='.$_POST['CARRIER_CODE'].'">'.$check2[0]["CARRIER_NAME"].'</a>';
	
		echo '<div class="well well=sm">
		<h1>Send Shipment '.$shipment_link.' via '.$carrier_link.'</h2>
		<h3>This next step will:</h3>
		<ul>
		<h3>Create a new load</h3>
		<h3>Add the shipment '.$shipment_link.' to the new load</h3>
		<h3>Add stops for the shipment '.$shipment_link.' to the new load</h3>
		<h3>Assign the carrier '.$carrier_link.' to the new load</h3>
		<h3>The office for the load will be  '.$office_link.', which is the same as the shipment.</h3>
		</ul>
		<h3>Your load will be ready to send the carrier manifest</h3>
		
		<h2><a class="btn btn-lg btn-default" href="exp_find_carrier.php?CODE='.$_POST['SHIPMENT_CODE'].'"><span class="glyphicon glyphicon-arrow-left"></span> Go Back</a> OR <a class="btn btn-lg btn-success" href="exp_find_carrier.php?SHIPMENT_CODE='.$_POST['SHIPMENT_CODE'].'&CARRIER_CODE='.$_POST['CARRIER_CODE'].'">Proceed <span class="glyphicon glyphicon-arrow-right"></span></a></h2>
		';
	} else {
		echo '<h2>Error: can\'t find shipment or carrier!</h2>
		';
	}
	
	echo '</div></div>
	';
	
} else if( isset($_GET['SHIPMENT_CODE']) && isset($_GET['CARRIER_CODE']) ) { //! Perform action
	require_once( "include/sts_load_class.php" );
	require_once( "include/sts_stop_class.php" );
	require_once( "include/sts_shipment_class.php" );
	
	$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
	$stop_table = sts_stop::getInstance($exspeedite_db, $sts_debug);
	$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
	
	$shipment_code = $_GET['SHIPMENT_CODE'];
	
	$load_code = $load_table->create_empty();
	$response = $shipment_table->update($_GET['SHIPMENT_CODE'], ['LOAD_CODE' => $load_code], false);

	$stop_table->add([ "LOAD_CODE" => $load_code, 
					"SEQUENCE_NO" => 1,
					"STOP_TYPE" => 'pick', 
					"SHIPMENT" => $shipment_code, 
					"CURRENT_STATUS" => $stop_table->behavior_state['entry'] ]);
	$stop_table->add([ "LOAD_CODE" => $load_code, 
					"SEQUENCE_NO" => 2,
					"STOP_TYPE" => 'drop', 
					"SHIPMENT" => $shipment_code, 
					"CURRENT_STATUS" => $stop_table->behavior_state['entry'] ]);
	
	$load_table->add_load_status( $load_code, "Add shipment# $shipment_code to load# $load_code ".($response ? "OK" : "FAIL") );
	$shipment_table->add_shipment_status( $shipment_code, "Add shipment# $shipment_code to load# $load_code ".($response ? "OK" : "FAIL"), false, false, $load_code );

	$changes = ['CARRIER' => $_GET['CARRIER_CODE']];
	
	// Propagate the OFFICE_CODE from the shipment to the load
	$check = $shipment_table->fetch_rows("SHIPMENT_CODE = ".$_GET['SHIPMENT_CODE'],	'OFFICE_CODE');
	if( is_array($check) && count($check) == 1 && isset($check[0]['OFFICE_CODE']) )
		$changes['OFFICE_CODE'] = $check[0]['OFFICE_CODE'];
	
	$load_table->update($load_code, $changes);
	
	reload_page ( "exp_viewload.php?CODE=".$load_code );
	
} else if( isset($_GET['CODE']) ) { //! Find Carriers
	require_once( "include/sts_shipment_class.php" );
	$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
	ob_implicit_flush(true);
	
	if (ob_get_level() == 0) ob_start();

	if( ! empty($sts_cutoff) &&
	//! SCR# 587 - Less strict date checking
	checkIsAValidDate($sts_cutoff) ) {
		$expired = "	DATE(GET_ASOF(EXP_SHIPMENT.SHIPMENT_CODE)) < DATE('".date("Y-m-d", strtotime($sts_cutoff))."')";
	} else {
		$expired = "FALSE ";
	}
	
	
	$check = $shipment_table->database->get_multiple_rows("
		SELECT SS_NUMBER, EXP_SHIPMENT.CURRENT_STATUS, SHIPPER_CITY, SHIPPER_STATE, CONS_CITY,
			CONS_STATE, BILLTO_NAME, LOAD_CODE,
			COALESCE(CTPAT_REQUIRED,0) AS CTPAT_REQUIRED,
			COALESCE(GENERAL_LIAB_INS,0) AS GENERAL_LIAB_INS,
			COALESCE(AUTO_LIAB_INS,0) AS AUTO_LIAB_INS,
			COALESCE(CARGO_LIAB_INS,0) AS CARGO_LIAB_INS,
			".$expired." AS EXPIRED,
			INSURANCE_CURRENCY
		FROM EXP_SHIPMENT
		LEFT JOIN EXP_CLIENT
		ON BILLTO_CLIENT_CODE = CLIENT_CODE
		WHERE SHIPMENT_CODE = ".$_GET['CODE']);

	echo '<div class="container" role="main">
	';
		
	if( is_array($check) && count($check) == 1 ) {		
		
		echo '<h1>Find Carrier For Shipment <a href="exp_addshipment.php?CODE='.$_GET['CODE'].'">'.$_GET['CODE'].(empty($check[0]["SS_NUMBER"]) ? '' : '/'.$check[0]["SS_NUMBER"]).'</a>
		<a class="btn btn-lg btn-success" href="exp_find_carrier.php?CODE='.$_GET['CODE'].'"><span class="glyphicon glyphicon-refresh"></span></a>
		<a class="btn btn-lg btn-default" href="exp_listshipment.php"><span class="glyphicon glyphicon-arrow-left"></span> Shipments</a>
		<a class="btn btn-lg btn-danger" disabled>Send To All</a>
		</h2>
		';
		
		if( empty($check[0]["SHIPPER_CITY"]) || empty($check[0]["SHIPPER_STATE"]) ||
			empty($check[0]["CONS_CITY"]) || empty($check[0]["CONS_STATE"]) ||
			empty($check[0]["CURRENT_STATUS"]) ||
			$check[0]["CURRENT_STATUS"] <> $shipment_table->behavior_state['assign'] ) {
			
			echo '<div class="alert alert-danger">
			<h2>Shipment not Completed and Ready</h2>
			<h3>You need a completed shipment (Shipper, Consignee) in the Ready state to proceed.</h3>
			</div>
			';
		} else if( ! $shipment_table->business_code_ok( $_GET['CODE'] ) ) {
			echo '<div class="alert alert-danger">
			<h2>Shipment Business Code Incompatible</h2>
			<h3>The busines code has to be one of ['.implode(', ', explode(',', $sts_find_carrier_bc)).'].</h3>
			</div>
			';
		} else if( ! empty($check[0]["EXPIRED"]) && $check[0]["EXPIRED"] ) {
			echo '<div class="alert alert-danger">
			<h2>Shipment Is Expired</h2>
			<h3>The shipment is older than '.$sts_cutoff.'</h3>
			</div>
			';
		} else if( isset($check[0]["LOAD_CODE"]) && $check[0]["LOAD_CODE"] > 0 ) {
			echo '<div class="alert alert-danger">
			<h2>Shipment Already On Load '.$check[0]["LOAD_CODE"].'</h2>
			<h3>You can\'t use this for a shipment once it\'s already added to a load.</h3>
			';
		} else {
			echo '<h3>From: '.$check[0]["SHIPPER_CITY"].', '.$check[0]["SHIPPER_STATE"].
				'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;To: '.$check[0]["CONS_CITY"].', '.$check[0]["CONS_STATE"].
				'<br>Client: '.$check[0]["BILLTO_NAME"].' '.
				($check[0]["CTPAT_REQUIRED"] ? ' <img src="images/logo-ctpat.jpg" alt="logo-ctpat" width="80px" />' : '').
				($check[0]["GENERAL_LIAB_INS"] + $check[0]["AUTO_LIAB_INS"] +
					$check[0]["CARGO_LIAB_INS"] > 0 ? '(General $'.nm0($check[0]["GENERAL_LIAB_INS"]).
					', Auto $'.nm0($check[0]["AUTO_LIAB_INS"]).
					', Cargo $'.nm0($check[0]["CARGO_LIAB_INS"]).' '.$check[0]["INSURANCE_CURRENCY"].')' : '').'</h3>

				<div id="loading1"><h3 class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /><br>Searching for suitable carriers...<br>(Busiest routes may take a little longer)</h3></div>';
			ob_flush(); flush();
			
			get_carriers( $shipment_table, $_GET['CODE'], $sts_home_currency, $sts_years, $sts_debug );
			update_message( 'loading1', '' );			
		}
		
		
	} else {
		echo '<h1>No Shipment '.$_GET['CODE'].' Found</h1>
		';
	}
} else {
		echo '<h1>No Shipment specified</h1>
		';
}

?>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");
			<?php if( ! $sts_debug ) { ?>
			document.documentElement.style.overflow = 'hidden';  // firefox, chrome
			document.body.scroll = "no"; // ie only
			<?php } ?>

			$('#CARRIERS').dataTable({
		        //"bLengthChange": false,
		        "bFilter": false,
		        "bSort": true,
		        "bInfo": true,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - 420) + "px",
				//"sScrollXInner": "120%",
				"order": [[ 6, "desc" ]],
		        //"lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				"bPaginate": false,
				"bScrollCollapse": false,
				"bSortClasses": false		
			});
			
			if( window.HANDLE_RESIZE_EVENTS ) {
				$(window).bind('resize', function(e) {
					console.log('resize event triggered');
					if (window.RT) clearTimeout(window.RT);
					window.RT = setTimeout(function() {
						this.location.reload(false); /* false to get page from cache */
					}, 100);
				});
			}
			
		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
}
?>
