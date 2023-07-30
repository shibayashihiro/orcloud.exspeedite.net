<?php
## -> A complete page BY MONA.

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');
require_once( "sts_table_class.php" );

class sts_driverpay_mng extends sts_table {
	// Constructor does not need the table name
	public function __construct( $database, $debug = false )
	{
		$this->debug =  $debug;
		$this->primary_key = "CLIENT_RATE_ID";
		parent::__construct( $database, CLIENT_RATES, $debug);		
		if( $this->debug ) echo "<p>Create sts_driverrate $this->table_name pk = $this->primary_key</p>";
	}
	
	// Add user Mk II
	public function add( $values ) {

		if( $this->debug ) echo "<p>add</p>";
		$column_list = array();
		$values_list = array();
		$values2 = array();
		foreach( $values as $field => $value ) {
			$column_list[] = $field;
			/* if( $field == 'SOCIAL_NUMBER' )
				$values2[$field] = $values_list[] = $this->enquote_string( $field, $this->encryptData( $value ) );
			else*/
			
				$values2[$field] = $values_list[] = $this->enquote_string( $field, $this->real_escape_string( $value ) );
				
		}

		if($values_list[3]=="'1'"){ $values_list[3]="'Yes'";}else{$values_list[3]="'No'";}

		$result = $this->add_row(implode(', ', $column_list), implode(', ', $values_list));
		
		if( $result ) {
			$code = $this->fetch_rows("RATE_CODE = ".$values2['RATE_CODE']." AND RATE_DESC = ".$values2['RATE_DESC']." AND RATE_PER_MILES = ".$values2['RATE_PER_MILES'].
				" AND TAXABLE = ".$values2['TAXABLE']);
			if( count($code) == 1 && isset($code[0]['RATE_ID']) )
				$result = $code[0]['RATE_ID'];
		}
		
		if( $this->debug ) echo "<p>add result = ".($result ? $result : 'false '.$this->error())."</p>";
		return $result;
	}
	
}
?>