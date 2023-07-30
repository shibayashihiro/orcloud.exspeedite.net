<?php

// $Id: sts_card_advance_class.php 3884 2020-01-30 00:21:42Z duncan $
// Fuel card advances not yet applied

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );

class sts_card_advance extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "ADVANCE_CODE";
		if( $this->debug ) echo "<p>Create sts_card_advance</p>";
		parent::__construct( $database, CARD_ADVANCE_TABLE, $debug);
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

}

//! Layout Specifications - For use with sts_result

$sts_result_card_advance_layout = array(
	'ADVANCE_CODE' => array( 'format' => 'hidden' ),
	'CARD_SOURCE' => array( 'label' => 'Source', 'format' => 'text' ),
	'TRANS_DATE' => array( 'label' => 'Date', 'format' => 'date' ),
	'TRANS_NUM' => array( 'label' => 'Trans#', 'format' => 'text' ),
	'CARD_STOP' => array( 'label' => 'Stop', 'format' => 'text',
		'snippet' => "CONCAT(CARD_STOP, '<br>', CITY, ', ', STATE)" ),
	//'CITY' => array( 'label' => 'City', 'format' => 'text' ),
	//'STATE' => array( 'label' => 'State', 'format' => 'text' ),
	'CASH_ADV' => array( 'label' => 'Advance', 'format' => 'number', 'align' => 'right' ),
	'DRIVER_NAME' => array( 'label' => 'Driver Name', 'format' => 'text' ),
	'DRIVER_CODE' => array( 'label' => '<span class="tip-bottom" title="Exspeedite driver assigned to this card#. If this column is blank, a driver is not yet assigned. You can assign it in Fuel Card Import."><span class="glyphicon glyphicon-info-sign"></span> Driver</span>', 'format' => 'table',
		'table' => DRIVER_TABLE, 'key' => 'DRIVER_CODE',
		'fields' => 'concat_ws(\' \',FIRST_NAME,LAST_NAME) name',
		'link' => 'exp_editdriver.php?CODE=' ),
	'TRIP_NUM' => array( 'label' => 'Trip#', 'format' => 'text' ),
	'CURRENCY_CODE' => array( 'label' => 'Currency', 'format' => 'text' ),
	'FUEL_CARD_ID' => array( 'label' => 'Card ID', 'format' => 'text' ),
	'CARD_NUM' => array( 'label' => 'Card#', 'format' => 'text' ),
);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_card_advance_edit = array(
	'title' => '<img src="images/card_ftp_icon.png" alt="card_ftp_icon" height="40"> Fuel Card Advances Not Applied to Driver Pay',
	'sort' => 'TRANS_DATE asc',
	'cancel' => 'index.php',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_deleteca.php?CODE=', 'key' => 'ADVANCE_CODE', 'label' => 'TRANS_NUM', 'tip' => 'Delete advance ',
		'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )
	)
);


?>
