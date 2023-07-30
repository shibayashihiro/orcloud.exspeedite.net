<?php

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );

class sts_detail extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {
		global $sts_result_detail_layout, $sts_result_detail_edit;

		$this->debug = $debug;
		$this->primary_key = "DETAIL_CODE";
		$this->layout_fields = $sts_result_detail_layout;
		$this->edit_fields = $sts_result_detail_edit;
		if( $this->debug ) echo "<p>Create sts_detail</p>";
		parent::__construct( $database, DETAIL_TABLE, $debug);
	}
	
	// Allow re-use of objects - singleton function
	public static function getInstance( $database, $debug = false ) {
		static $instance = null;
		$myclass = get_class ();
		if( $debug ) echo "<p>Get instance of $myclass</p>";
		if (null === $instance) {
			$instance = new $myclass( $database, $debug );
		}
		return $instance;
    }

	public function lookup_commodity( $commodity ) {
		global $exspeedite_db;
		
		$result = $exspeedite_db->get_one_row("select PIECES_UNITS, 
			(SELECT UN_NUMBER FROM EXP_UN_NUMBER
			WHERE EXP_COMMODITY.UN_NUMBER = EXP_UN_NUMBER.UN_NUMBER_CODE) UN_NUMBER,
			TEMP_CONTROLLED, TEMPERATURE, TEMPERATURE_UNITS, COMMODITY_DESCRIPTION, DANGEROUS
			FROM EXP_COMMODITY
			WHERE COMMODITY_CODE = $commodity");
	
		if( $this->debug ) {
			echo "<p>result = </p>
			<pre>";
			var_dump($result);
			echo "</pre>";
		}
		return is_array($result) ? $result : 'null';
	}

	public function get_osd_info( $shipment ) {
		return $this->fetch_rows( "SHIPMENT_CODE = ".$shipment, "DETAIL_CODE, (SELECT COMMODITY_NAME FROM EXP_COMMODITY WHERE COMMODITY = COMMODITY_CODE) AS COMMODITY_NAME,
		PALLETS, PIECES, WEIGHT" );
	}

	public function duplicate( $shipment, $new_shipment ) {
		if( $this->debug ) echo "<p>detail/duplicate shipment = $shipment, new_shipment = $new_shipment</p>";
		// Get current record
		$matching_records = $this->fetch_rows("SHIPMENT_CODE = ".$shipment );
		
		foreach( $matching_records as $current_record ) {
			// Reset some fields
			$new_record['SHIPMENT_CODE'] = strval($new_shipment);

			$new_record["COMMODITY"] = $current_record['COMMODITY'];
			$new_record["PALLETS"] = $current_record['PALLETS'];
			$new_record["PIECES"] = $current_record['PIECES'];
			$new_record["PIECES_UNITS"] = $current_record['PIECES_UNITS'];
			$new_record["LENGTH"] = $current_record['LENGTH'];
			$new_record["WEIGHT"] = $current_record['WEIGHT'];
			$new_record["AREA"] = $current_record['AREA'];
			$new_record["VOLUME"] = $current_record['VOLUME'];
			$new_record["AMOUNT"] = $current_record['AMOUNT'];
			$new_record["EXTRA_CHARGE"] = $current_record['EXTRA_CHARGE'];
			$new_record["HIGH_VALUE"] = $current_record['HIGH_VALUE'];
			$new_record["DANGEROUS_GOODS"] = $current_record['DANGEROUS_GOODS'];
			$new_record["UN_NUMBER"] = $current_record['UN_NUMBER'];
			$new_record["TEMP_CONTROLLED"] = $current_record['TEMP_CONTROLLED'];
			$new_record["BILLABLE"] = $current_record['BILLABLE'];
			$new_record["PO_NUMBER"] = $current_record['PO_NUMBER'];
			$new_record["TEMPERATURE"] = $current_record['TEMPERATURE'];
			$new_record["TEMPERATURE_UNITS"] = $current_record['TEMPERATURE_UNITS'];
			
			$result = $this->add( $new_record );
			if( $this->debug ) echo "<p>detail/duplicate add result = $result</p>";
		}
	}
	
	//! SCR# 761 - update the shipment based on hazmat and UN numbers, return results
	// Assumes $shipment exists 
	public function update_hazmat( $shipment ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry shipment = $shipment</p>";
		$hasmat = false;
		$uns = '';
		
		$check = $this->fetch_rows("SHIPMENT_CODE = ".$shipment, "DANGEROUS_GOODS, UN_NUMBER,
			(SELECT UN_CLASS FROM EXP_UN_NUMBER WHERE EXP_UN_NUMBER.UN_NUMBER = EXP_DETAIL.UN_NUMBER
			LIMIT 1) AS UN_CLASS" );
		if( is_array($check) && count($check) > 0 ) {
			$un = [];
			foreach( $check as $row ) {
				if( $row["DANGEROUS_GOODS"] )
					$hasmat = true;
				if( ! empty($row["UN_NUMBER"]) )
					$un[] = (empty($row["UN_CLASS"]) ? '': 'CLASS '.$row["UN_CLASS"].' ').
						'UN '.$row["UN_NUMBER"];
			}
			$uns = implode(', ', $un);
		}
		
		$this->database->get_one_row("UPDATE EXP_SHIPMENT SET DANGEROUS_GOODS = ".($hasmat ? 1 : 0).", UN_NUMBERS = '".$uns."' WHERE SHIPMENT_CODE = $shipment");
		
		if( $this->debug ) echo "<p>".__METHOD__.": exit hazmat = ".($hasmat ? "true" : "false")." uns = $uns</p>";
		return ['DANGEROUS_GOODS' => $hasmat, 'UN_NUMBERS' => $uns];
	}
}

class sts_detail_invoice extends sts_detail {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		if( $debug ) echo "<p>Create sts_stop_left_join</p>";
		parent::__construct( $database, $debug);
	}

	// Allow re-use of objects - singleton function
	public static function getInstance( $database, $debug = false ) {
		static $instance = null;
		$myclass = get_class ();
		if( $debug ) echo "<p>Get instance of $myclass</p>";
		if (null === $instance) {
			$instance = new $myclass( $database, $debug );
		}
		return $instance;
    }	

	// Fetch one or more rows
	public function fetch_rows( $match = "", $fields = "*", $order = "", $limit = "", $groupby = "", $match2 = "" ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $match</p>";
		
		$result = $this->database->get_multiple_rows("SELECT $fields FROM
			(SELECT 
				(SELECT COMMODITY_NAME FROM EXP_COMMODITY
					WHERE COMMODITY_CODE = COMMODITY) AS CNAME,
				(SELECT COMMODITY_DESCRIPTION FROM EXP_COMMODITY
					WHERE COMMODITY_CODE = COMMODITY) AS CDESCRIPTION,
				WEIGHT, PALLETS, PIECES,
				(SELECT UNIT_NAME FROM EXP_UNIT
					WHERE UNIT_CODE = PIECES_UNITS) AS UNAME

			FROM EXP_DETAIL
			WHERE $match ) x
			 ".($order <> "" ? "ORDER BY $order" : "") );

		if( $this->debug ) {
			echo "<p>result for $this->table_name = </p>
			<pre>";
			var_dump($result);
			echo "</pre>";
		}
		return $result;
	}

	public function html_template() {
		return array(	//! $sts_email_detail
	'header' => '
	<table class="noborder">
		<thead>
			<tr>
				<th class="w25">
					COMMODITY
				</th>
				<th class="w25">
					DESCRIPTION
				</th>
				<th class="w15 text-right">
					PALLETS
				</th>
				<th class="w15 text-right">
					PIECES
				</th>
				<th class="w15 text-right">
					WEIGHT
				</th>
			</tr>
		</thead>
		<tbody>',
	'layout' => '
			<tr>
				<td class="w25">
					%CNAME%
				</td>
				<td class="w25">
					%CDESCRIPTION%
				</td>
				<td class="w15 text-right">
					%PALLETS%
				</td>
				<td class="w15 text-right">
					%PIECES% %UNAME%
				</td>
				<td class="w15 text-right">
					%WEIGHT%
				</td>
			</tr>
	',
	'footer' => '</tbody>
	</table>
		' );
	}
}

//! Form Specifications - For use with sts_form
$sts_form_add_detail = array( //! $sts_form_add_detail
	'title' => 'Add Commodity',
	'action' => 'exp_adddetail.php',
	'cancel' => 'exp_editorder_stop.php?CODE=%SHIPMENT_CODE%',
	'popup' => true,	// issue with the toggle switches
	'name' => 'add_detail',
	'okbutton' => 'Save Changes',
	//'saveadd' => 'Save & Add Another',
	//'cancelbutton' => 'Cancel',
	'layout' => '
	%SHIPMENT_CODE%
	%TEMP_CONTROLLED%
	<div class="form-group tighter">
		<div class="col-sm-10">
			<div class="form-group">
				<label for="COMMODITY" class="col-sm-2 control-label">#COMMODITY#</label>
				<div class="col-sm-10">
					%COMMODITY%
				</div>
			</div>
			<div class="col-sm-10 col-sm-offset-2 tighter">
				<p class="text-info"><span class="glyphicon glyphicon-warning-sign"></span> When you change the above commodity, it updates the details below.<br>Make sure the information is correct before saving.</p>
			</div>
		</div>
	</div>
	<div class="form-group tighter well well-md">
		<div class="form-group">
			<label for="NOTES" class="col-sm-2 control-label" style="margin-left: -10px;">#NOTES#</label>
			<div class="col-sm-10">
				%NOTES%
			</div>
		</div>
		<div class="col-sm-5">
			<div class="form-group">
				<label for="PALLETS" class="col-sm-4 control-label">#PALLETS#</label>
				<div class="col-sm-8">
					%PALLETS%
				</div>
			</div>
			<div class="form-group">
				<label for="PIECES" class="col-sm-4 control-label">#PIECES#</label>
				<div class="col-sm-4">
					%PIECES%
				</div>
				<div class="col-sm-4">
					%PIECES_UNITS%
				</div>
			</div>
			<div class="form-group">
				<label for="WEIGHT" class="col-sm-4 control-label">#WEIGHT#</label>
				<div class="col-sm-8">
					%WEIGHT%
				</div>
			</div>
			<div class="form-group temp" hidden>
				<div class="col-sm-8 col-sm-offset-4">
					<label class="text-primary"><span class="glyphicon glyphicon-asterisk"></span> Temperature Controlled</label>
					</div>
			</div>
		</div>
		<div class="col-sm-5">
			<div class="form-group">
				<label for="DANGEROUS_GOODS" class="col-sm-4 control-label">#DANGEROUS_GOODS#</label>
				<div class="col-sm-8">
					%DANGEROUS_GOODS%
				</div>
			</div>
			<div class="form-group">
				<label for="BILLABLE" class="col-sm-4 control-label">#BILLABLE#</label>
				<div class="col-sm-8">
					%BILLABLE%
				</div>
			</div>
			<div class="form-group">
				<label for="UN_NUMBER" class="col-sm-4 control-label">#UN_NUMBER#</label>
				<div class="col-sm-8">
					%UN_NUMBER%
				</div>
			</div>
			<div class="form-group temp">
				<label for="TEMPERATURE" class="col-sm-4 control-label">#TEMPERATURE#</label>
				<div class="col-sm-3">
					%TEMPERATURE%
				</div>
				<div class="col-sm-5">
					%TEMPERATURE_UNITS%
				</div>
			</div>
		</div>
	</div>
'
);

$sts_form_edit_detail = array( //! $sts_form_edit_detail
	'title' => 'Edit Commodity',
	'action' => 'exp_editdetail.php',
	'cancel' => 'exp_editorder_stop.php?CODE=%SHIPMENT_CODE%',
	'popup' => true,	// issue with the toggle switches
	'name' => 'edit_detail',
	'okbutton' => 'Save Changes',
	//'saveadd' => 'Save & Add Another',
	'cancelbutton' => 'Cancel',
	'layout' => '
	%DETAIL_CODE%
	%SHIPMENT_CODE%
	%TEMP_CONTROLLED%
	<div class="form-group tighter">
		<div class="col-sm-10">
			<div class="form-group">
				<label for="COMMODITY" class="col-sm-2 control-label">#COMMODITY#</label>
				<div class="col-sm-10">
					%COMMODITY%
				</div>
			</div>
			<div class="col-sm-10 col-sm-offset-2 tighter">
				<p class="text-info"><span class="glyphicon glyphicon-warning-sign"></span> When you change the above commodity, it updates the details below.<br>Make sure the information is correct before saving.</p>
			</div>
		</div>
	</div>
	<div class="form-group tighter well well-md">
		<div class="form-group">
			<label for="NOTES" class="col-sm-2 control-label" style="margin-left: -10px;">#NOTES#</label>
			<div class="col-sm-10">
				%NOTES%
			</div>
		</div>
		<div class="col-sm-5">
			<div class="form-group">
				<label for="PALLETS" class="col-sm-4 control-label">#PALLETS#</label>
				<div class="col-sm-8">
					%PALLETS%
				</div>
			</div>
			<div class="form-group">
				<label for="PIECES" class="col-sm-4 control-label">#PIECES#</label>
				<div class="col-sm-4">
					%PIECES%
				</div>
				<div class="col-sm-4">
					%PIECES_UNITS%
				</div>
			</div>
			<div class="form-group">
				<label for="WEIGHT" class="col-sm-4 control-label">#WEIGHT#</label>
				<div class="col-sm-8">
					%WEIGHT%
				</div>
			</div>
			<div class="form-group temp" hidden>
				<div class="col-sm-8 col-sm-offset-4">
					<label class="text-primary"><span class="glyphicon glyphicon-asterisk"></span> Temperature Controlled</label>
					</div>
			</div>
		</div>
		<div class="col-sm-5">
			<div class="form-group">
				<label for="DANGEROUS_GOODS" class="col-sm-4 control-label">#DANGEROUS_GOODS#</label>
				<div class="col-sm-8">
					%DANGEROUS_GOODS%
				</div>
			</div>
			<div class="form-group">
				<label for="BILLABLE" class="col-sm-4 control-label">#BILLABLE#</label>
				<div class="col-sm-8">
					%BILLABLE%
				</div>
			</div>
			<div class="form-group">
				<label for="UN_NUMBER" class="col-sm-4 control-label">#UN_NUMBER#</label>
				<div class="col-sm-8">
					%UN_NUMBER%
				</div>
			</div>
			<div class="form-group temp" hidden>
				<label for="TEMPERATURE" class="col-sm-4 control-label">#TEMPERATURE#</label>
				<div class="col-sm-3">
					%TEMPERATURE%
				</div>
				<div class="col-sm-5">
					%TEMPERATURE_UNITS%
				</div>
			</div>
		</div>
	</div>
'
);

//! Field Specifications - For use with sts_form
$sts_form_add_detail_fields = array( //! $sts_form_add_detail_fields
	'SHIPMENT_CODE' => array( 'format' => 'hidden' ),
	'COMMODITY' => array( 'label' => 'Commodity', 'format' => 'table',
		'table' => COMMODITY_TABLE, 'key' => 'COMMODITY_CODE', 'fields' => 'COMMODITY_NAME,COMMODITY_DESCRIPTION',
		'separator' => ' - ', 'nolink' => true,
		'order' => "COMMODITY_NAME asc" ),
	'PALLETS' => array( 'label' => 'Pallets', 'format' => 'number', 'align' => 'right' ),
	'PIECES' => array( 'label' => 'Items', 'format' => 'number', 'align' => 'right' ),
	'PIECES_UNITS' => array( 'label' => 'Units', 'format' => 'table',
		'table' => UNIT_TABLE, 'key' => 'UNIT_CODE', 'fields' => 'UNIT_NAME',
		'condition' => 'UNIT_TYPE = \'item\'', 'nolink' => true, 'extras' => 'readonly' ),
	//'LENGTH' => array( 'label' => 'Length', 'format' => 'number', 'align' => 'right' ),
	'WEIGHT' => array( 'label' => 'Weight', 'format' => 'number', 'align' => 'right' ),
	//'AREA' => array( 'label' => 'Area', 'format' => 'number', 'align' => 'right' ),
	//'VOLUME' => array( 'label' => 'Volume', 'format' => 'number', 'align' => 'right' ),
	//'AMOUNT' => array( 'label' => 'Amt', 'format' => 'number', 'align' => 'right' ),
	//'EXTRA_CHARGE' => array( 'label' => 'Xtra', 'format' => 'number', 'align' => 'right' ),
	//'HIGH_VALUE' => array( 'label' => 'High', 'format' => 'bool2' ),
	'DANGEROUS_GOODS' => array( 'label' => 'Hazmat', 'format' => 'bool' ),
	'BILLABLE' => array( 'label' => 'Billable', 'format' => 'bool', 'value' => 'true' ),
	'UN_NUMBER' => array( 'label' => 'UN#', 'format' => 'text' ),
	'NOTES' => array( 'label' => 'Notes', 'format' => 'text' ),

	'TEMP_CONTROLLED' => array( 'format' => 'hidden' ),
	'TEMPERATURE' => array( 'label' => 'Temp', 'format' => 'number', 'align' => 'right',
		'extras' => 'allowneg' ),
	'TEMPERATURE_UNITS' => array( 'label' => 'Units', 'format' => 'table',
		'table' => UNIT_TABLE, 'key' => 'UNIT_CODE', 'fields' => 'UNIT_NAME',
		'condition' => 'UNIT_TYPE = \'temperature\'', 'nolink' => true, 'extras' => 'readonly' ),
);

$sts_form_edit_detail_fields = array( //! $sts_form_edit_detail_fields
	'DETAIL_CODE' => array( 'format' => 'hidden' ),
	'SHIPMENT_CODE' => array( 'format' => 'hidden' ),
	'COMMODITY' => array( 'label' => 'Commodity', 'format' => 'table',
		'table' => COMMODITY_TABLE, 'key' => 'COMMODITY_CODE', 'fields' => 'COMMODITY_NAME,COMMODITY_DESCRIPTION',
		'separator' => ' - ', 'nolink' => true,
		'order' => "COMMODITY_NAME != 'PALLETS', COMMODITY_NAME asc" ),
	'PALLETS' => array( 'label' => 'Pallets', 'format' => 'number', 'align' => 'right' ),
	'PIECES' => array( 'label' => 'Items', 'format' => 'number', 'align' => 'right' ),
	'PIECES_UNITS' => array( 'label' => 'Units', 'format' => 'table',
		'table' => UNIT_TABLE, 'key' => 'UNIT_CODE', 'fields' => 'UNIT_NAME',
		'condition' => 'UNIT_TYPE = \'item\'', 'nolink' => true ),
	//'LENGTH' => array( 'label' => 'Length', 'format' => 'number' ),
	'WEIGHT' => array( 'label' => 'Weight', 'format' => 'number', 'align' => 'right' ),
	//'AREA' => array( 'label' => 'Area', 'format' => 'number' ),
	//'VOLUME' => array( 'label' => 'Volume', 'format' => 'number' ),
	//'AMOUNT' => array( 'label' => 'Amt', 'format' => 'number', 'align' => 'right' ),
	//'EXTRA_CHARGE' => array( 'label' => 'Xtra', 'format' => 'number', 'align' => 'right' ),
	//'HIGH_VALUE' => array( 'label' => 'High', 'format' => 'bool2' ),
	'DANGEROUS_GOODS' => array( 'label' => 'Hazmat', 'format' => 'bool' ),
	'BILLABLE' => array( 'label' => 'Billable', 'format' => 'bool' ),
	'UN_NUMBER' => array( 'label' => 'UN#', 'format' => 'text' ),
	'NOTES' => array( 'label' => 'Notes', 'format' => 'text' ),

	'TEMP_CONTROLLED' => array( 'format' => 'hidden' ),
	'TEMPERATURE' => array( 'label' => 'Temp', 'format' => 'number', 'align' => 'right',
		'extras' => 'allowneg' ),
	'TEMPERATURE_UNITS' => array( 'label' => 'Units', 'format' => 'table',
		'table' => UNIT_TABLE, 'key' => 'UNIT_CODE', 'fields' => 'UNIT_NAME',
		'condition' => 'UNIT_TYPE = \'temperature\'', 'nolink' => true, 'extras' => 'readonly' ),
);

//! Layout Specifications - For use with sts_result
$sts_result_detail_layout = array( //! $sts_result_detail_layout
	'DETAIL_CODE' => array( 'format' => 'hidden' ),
	'SHIPMENT_CODE' => array( 'format' => 'hidden' ),
	'COMMODITY' => array( 'label' => 'Commodity', 'format' => 'table',
		'table' => COMMODITY_TABLE, 'key' => 'COMMODITY_CODE', 'fields' => 'COMMODITY_NAME' ),
	'COMMODITY_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'table',
		'table' => COMMODITY_TABLE, 'key' => 'COMMODITY_CODE', 'pk' => 'COMMODITY', 'fields' => 'COMMODITY_DESCRIPTION' ),
	'NOTES' => array( 'label' => 'Notes', 'format' => 'text' ),
	'204_REFERENCE' => array( 'label' => '204&nbsp;Ref#', 'format' => 'text' ),
	'PO_NUMBER' => array( 'label' => 'PO&nbsp;#', 'format' => 'text' ),
	'PALLETS' => array( 'label' => 'Pallets', 'format' => 'num0', 'align' => 'right' ),
	'PIECES' => array( 'label' => 'Items', 'format' => 'num0', 'align' => 'right' ),
	'PIECES_UNITS' => array( 'label' => 'Units', 'format' => 'table',
		'table' => UNIT_TABLE, 'key' => 'UNIT_CODE', 'fields' => 'UNIT_NAME',
		'condition' => 'UNIT_TYPE = \'item\'', 'nolink' => true ),
	//'LENGTH' => array( 'label' => 'Length', 'format' => 'num2', 'align' => 'right' ),
	'WEIGHT' => array( 'label' => 'Weight', 'format' => 'num2', 'align' => 'right' ),
	//'TEMPERATURE' => array( 'label' => 'Temp', 'format' => 'text', 'align' => 'right' ),
	//'TEMPERATURE_UNITS' => array( 'label' => 'Units', 'format' => 'number', 'align' => 'right' ),
	'TEMP2' => array( 'label' => 'Temp', 'format' => 'text', 'align' => 'right',
		'snippet' => "CONCAT(TEMPERATURE,'&deg;',(SELECT SUBSTR(UNIT_NAME,1,1) FROM EXP_UNIT WHERE UNIT_CODE = TEMPERATURE_UNITS))" ),
	//'AREA' => array( 'label' => 'Area', 'format' => 'num2, 'align' => 'right'' ),
	//'VOLUME' => array( 'label' => 'Volume', 'format' => 'num2', 'align' => 'right' ),
	//'AMOUNT' => array( 'label' => 'Amt', 'format' => 'num2', 'align' => 'right' ),
	//'EXTRA_CHARGE' => array( 'label' => 'Xtra', 'format' => 'num2', 'align' => 'right' ),
	//'HIGH_VALUE' => array( 'label' => 'High', 'format' => 'bool' ),
	//'SYNERGY_IMPORT' => array( 'label' => 'Synergy', 'align' => 'center', 'format' => 'bool' ),
	'DANGEROUS_GOODS' => array( 'label' => 'Hazmat', 'format' => 'bool', 'align' => 'center' ),
	'UN_NUMBER' => array( 'label' => 'UN#', 'format' => 'text' ),
	'TEMP_CONTROLLED' => array( 'label' => 'Temp', 'format' => 'bool', 'align' => 'center' ),
	//'BILLABLE' => array( 'label' => 'Billable', 'format' => 'bool', 'align' => 'center' ),
);

//! Edit/Delete Button Specifications - For use with sts_result
$sts_result_detail_edit = array(
	'title' => 'Commodities',
	'add' => 'exp_adddetail.php',
	//'popup' => true,
	'addbutton' => 'Add Commodity',
	'rowbuttons' => array(
		array( 'url' => 'exp_editdetail.php?CODE=', 'key' => 'DETAIL_CODE', 'label' => 'COMMODITY', 'tip' => 'Edit commodity ', 'icon' => 'glyphicon glyphicon-edit', 'class' => 'popup' ),
		array( 'url' => 'exp_deletedetail.php?CODE=', 'key' => 'DETAIL_CODE', 'label' => 'COMMODITY', 'tip' => 'Delete commodity ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes', 'inplace' => true )
	)
);
	


?>
