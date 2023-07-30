<?php
## -> A complete page BY PADMAJA.

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');
require_once( "sts_table_class.php" );

class sts_clientrate_mng extends sts_table {
	// Constructor does not need the table name
	public function __construct( $database, $debug = TRUE )
	{
		$this->debug = $debug ;/*$debug*/
		$this->primary_key = "CLIENT_RATE_ID";
		parent::__construct( $database, CLIENT_CAT_RATE, $debug);		
		if( $this->debug ) echo "<p>Create sts_clientrate $this->table_name pk = $this->primary_key</p>";
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

$sts_result_client_layout_rate	= array(
	'CLIENT_RATE_ID' => array( 'format' => 'hidden' ),
	'ISDELETED' => array( 'format' => 'hidden' ),
	'RATE_CODE' => array( 'label' => 'Rate Code', 'format' => 'text' ),
	'RATE_NAME'=>array( 'label' => 'Rate Name', 'format' => 'text' ),
	'RATE_DESC' => array( 'label' => 'Rate Description', 'format' => 'text' ),
	'RATE_PER_MILES' => array( 'label' => 'Rate', 'format' => 'text' ),
	'TAXABLE' => array( 'label' => 'Taxable', 'format' => 'text' ),
	//'ZONES'=>array('label'=>'Zones','format'=>'text')	
);

$sts_result_client_rate = array(
	'title' => '<img src="images/driver_icon.png" alt="driver_icon" height="24"> Client\'s Rates List ',
	'sort' => '	CLIENT_RATE_ID asc , RATE_CODE asc',
	'cancel' => 'index.php',
	'add' => 'exp_addclientsrates.php',
	'addbutton' => 'Add Client Rate',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
array( 'url' => 'exp_editclientrate.php?CODE=', 'key' => 'CLIENT_RATE_ID', 'label' => 'RATE_CODE', 'tip' => 'Edit client rate ', 'icon' => 'glyphicon glyphicon-edit',  'showif' => 'notdeleted' ),
array( 'url' => 'exp_deleteclientrate.php?TYPE=del&CODE=', 'key' => 'CLIENT_RATE_ID', 'label' => 'RATE_CODE', 'tip' => 'Delete client rate ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes', 'showif' => 'notdeleted' ),
array( 'url' => 'exp_deleteclientrate.php?TYPE=undel&CODE=', 'key' => 'CLIENT_RATE_ID', 'label' => 'RATE_CODE', 'tip' => 'Undelete client rate ', 'icon' => 'glyphicon glyphicon-arrow-left', 'showif' => 'deleted' ),
array( 'url' => 'exp_deleteclientrate.php?TYPE=permdel&CODE=', 'key' => 'CLIENT_RATE_ID', 'label' => 'RATE_CODE', 'tip' => 'Permanently Delete client rate ', 'icon' => 'glyphicon glyphicon-remove', 'confirm' => 'yes', 'showif' => 'deleted' )
	)
);

$sts_form_addclientrate_form = array(	//! $sts_form_addclientrate_form
	'title' => '<img src="images/money_bag.png" alt="money_bag" height="24"> Add Client Rates',
	'action' => 'exp_addclientsrates.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listclientrates.php',
	'name' => 'addclientrate',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-4">
			<div class="form-group">
				<label for="RATE_CODE" class="col-sm-4 control-label">#RATE_CODE#</label>
				<div class="col-sm-8">
					%RATE_CODE%
				</div>
			</div>
			
			<div class="form-group">
				<label for="RATE_NAME" class="col-sm-4 control-label">#RATE_NAME#</label>
				<div class="col-sm-8">
					%RATE_NAME%
				</div>
			</div>
			
			<div class="form-group">
				<label for="CATEGORY" class="col-sm-4 control-label">#CATEGORY#</label>
				<div class="col-sm-8">
					%CATEGORY%
				</div>
			</div>
			
			<div class="form-group" id="SHIPPER_CLIENT_CODE">
				<label for="SHIPPER_CLIENT_CODE" class="col-sm-4 control-label">#SHIPPER_CLIENT_CODE#</label>
				<div class="col-sm-8">
					%SHIPPER_CLIENT_CODE%
				</div>
			</div>
			
			<div class="form-group" id="CONS_CLIENT_CODE">
				<label for="CONS_CLIENT_CODE" class="col-sm-4 control-label">#CONS_CLIENT_CODE#</label>
				<div class="col-sm-8">
					%CONS_CLIENT_CODE%
				</div>
			</div>
			
			
			<div class="form-group">
				<label for="RATE_PER_MILES" class="col-sm-4 control-label">#RATE_PER_MILES#</label>
				<div class="col-sm-8">
					%RATE_PER_MILES%
				</div>
			</div>
			<div class="form-group">
				<label for="TAXABLE" class="col-sm-4 control-label">#TAXABLE#</label>
				<div class="col-sm-8">
					%TAXABLE%
				</div>
			</div>
			<div class="form-group">
				<label for="RATE_DESC" class="col-sm-4 control-label">#RATE_DESC#</label>
				<div class="col-sm-8">
					%RATE_DESC%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
			<br>
			<div class="alert alert-info">
				<h4>Rate Categories</h4>
				<p><strong>Shipper</strong> - The <strong>Rate</strong> is applied to the billing if the Shipper matches. Be sure to select a Shipper below.</p>
				<p><strong>Consignee</strong> - The <strong>Rate</strong> is applied to the billing if the Consignee matches.  Be sure to select a Consignee below.</p>
				<p>All other categories, if you assign to a client, they will show up on the billing page.</p>
				<br>
				<p><strong>Taxable</strong> - If tax is to be applied (most likely in Canada)</p>
			</div>
		</div>
	</div>
	');
	
$sts_form_editclientrate_form = array(	//! $sts_form_editclientrate_form
	'title' => '<img src="images/money_bag.png" alt="money_bag" height="24"> Update Client Rates',
	'action' => 'exp_editclientrate.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listclientrates.php',
	'name' => 'editclientrate',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Cancel',
		'layout' => '
	%CLIENT_RATE_ID%
	<div class="form-group">
		<div class="col-sm-4">
			<div class="form-group">
				<label for="RATE_CODE" class="col-sm-4 control-label">#RATE_CODE#</label>
				<div class="col-sm-8">
					%RATE_CODE%
				</div>
			</div>
			
			<div class="form-group">
				<label for="RATE_NAME" class="col-sm-4 control-label">#RATE_NAME#</label>
				<div class="col-sm-8">
					%RATE_NAME%
				</div>
			</div>
			
			<div class="form-group">
				<label for="CATEGORY" class="col-sm-4 control-label">#CATEGORY#</label>
				<div class="col-sm-8">
					%CATEGORY%
				</div>
			</div>
			
			<div class="form-group">
				<label for="SHIPPER_CLIENT_CODE" class="col-sm-4 control-label">#SHIPPER_CLIENT_CODE#</label>
				<div class="col-sm-8">
					%SHIPPER_CLIENT_CODE%
				</div>
			</div>
			
			<div class="form-group">
				<label for="CONS_CLIENT_CODE" class="col-sm-4 control-label">#CONS_CLIENT_CODE#</label>
				<div class="col-sm-8">
					%CONS_CLIENT_CODE%
				</div>
			</div>
			
			
			<div class="form-group">
				<label for="RATE_PER_MILES" class="col-sm-4 control-label">#RATE_PER_MILES#</label>
				<div class="col-sm-8">
					%RATE_PER_MILES%
				</div>
			</div>
			<div class="form-group">
				<label for="TAXABLE" class="col-sm-4 control-label">#TAXABLE#</label>
				<div class="col-sm-8">
					%TAXABLE%
				</div>
			</div>
			<div class="form-group">
				<label for="RATE_DESC" class="col-sm-4 control-label">#RATE_DESC#</label>
				<div class="col-sm-8">
					%RATE_DESC%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
			<br>
			<div class="alert alert-info">
				<h4>Rate Categories</h4>
				<p><strong>Shipper</strong> - The <strong>Rate</strong> is applied to the billing if the Shipper matches. Be sure to select a Shipper below.</p>
				<p><strong>Consignee</strong> - The <strong>Rate</strong> is applied to the billing if the Consignee matches.  Be sure to select a Consignee below.</p>
				<p>All other categories, if you assign to a client, they will show up on the billing page.</p>
				<br>
				<p><strong>Taxable</strong> - If tax is to be applied (most likely in Canada)</p>
			</div>
		</div>
	</div>
	');
	
	$sql_obj	=	new sts_table($exspeedite_db , CLIENT_CAT_RATE , $sts_debug);
	$max_range_id			=  $sql_obj->database->get_multiple_rows(" SELECT 	MAX(RATE_CODE) FROM ".CLIENT_CAT_RATE." ");	
	if($max_range_id[0]['MAX(RATE_CODE)']==''){$res['RATE_CODE']='C101';}else{ $arrr=explode('C',$max_range_id[0]['MAX(RATE_CODE)']);$r=$arrr[1]+1;  $res['RATE_CODE']='C'.$r;}

$sts_form_add_clientrate_fields = array(	//! $sts_form_add_clientrate_fields
	'RATE_CODE' => array( 'label' => 'Rate Code', 'format' => 'text',  'extras' => 'required autofocus readonly' , 'value'=> $res['RATE_CODE']),
	'CATEGORY' => array( 'label' => 'Rate Category', 'format' => 'table', 'table' => CLIENT_CAT, 'key' => 'CLIENT_CAT', 'fields' => 'CATEGORY_NAME' ,'extras' => 'required' ),
	//'ZONES' => array( 'label' => 'Zones', 'format' => 'table', 'table' => ZONE_FILTER_TABLE, 'key' => 'ZF_NAME', 'fields' => 'ZF_NAME' ,'extras' => 'required' ),

	'SHIPPER_CLIENT_CODE' => array( 'label' => 'Shipper', 'format' => 'table', 'table' => CLIENT_TABLE,
		'key' => 'CLIENT_CODE', 'fields' => 'CLIENT_NAME',
		'condition' => 'SHIPPER', 'nolink' => true ),
	'CONS_CLIENT_CODE' => array( 'label' => 'Consignee', 'format' => 'table', 'table' => CLIENT_TABLE,
		'key' => 'CLIENT_CODE', 'fields' => 'CLIENT_NAME',
		'condition' => 'CONSIGNEE', 'nolink' => true ),

	'RATE_NAME' => array( 'label' => 'Rate Name', 'format' => 'text' , 'extras' => 'required' ),
	'RATE_PER_MILES' => array( 'label' => 'Rate', 'format' => 'text' , 'extras' => 'required' ),
	'TAXABLE' => array( 'label' => 'Taxable', 'format' => 'enum', 'extras' => 'required' , 'value'=>'No'),
	'RATE_DESC' => array( 'label' => 'Description','format' => 'textarea', 'extras' => 'required rows="5"')
);

$sts_form_edit_clientrate_fields = array(	//! $sts_form_edit_clientrate_fields
	'CLIENT_RATE_ID' => array( 'format' => 'hidden' ),
	'RATE_CODE' => array( 'label' => 'Rate Code', 'format' => 'text',  'extras' => 'required autofocus readonly' , 'value'=> $res['RATE_CODE']),
	'CATEGORY' => array( 'label' => 'Rate Category', 'format' => 'table', 'table' => CLIENT_CAT, 'key' => 'CLIENT_CAT', 'fields' => 'CATEGORY_NAME' ,'extras' => 'required' ),
	//'ZONES' => array( 'label' => 'Zones', 'format' => 'table', 'table' => ZONE_FILTER_TABLE, 'key' => 'ZF_NAME', 'fields' => 'ZF_NAME' ,'extras' => 'required' ),

	'SHIPPER_CLIENT_CODE' => array( 'label' => 'Shipper', 'format' => 'table', 'table' => CLIENT_TABLE,
		'key' => 'CLIENT_CODE', 'fields' => 'CLIENT_NAME',
		'condition' => 'SHIPPER', 'nolink' => true ),
	'CONS_CLIENT_CODE' => array( 'label' => 'Consignee', 'format' => 'table', 'table' => CLIENT_TABLE,
		'key' => 'CLIENT_CODE', 'fields' => 'CLIENT_NAME',
		'condition' => 'CONSIGNEE', 'nolink' => true ),

	'RATE_NAME' => array( 'label' => 'Rate Name', 'format' => 'text' , 'extras' => 'required' ),
	'RATE_PER_MILES' => array( 'label' => 'Rate', 'format' => 'text' , 'extras' => 'required' ),
	'TAXABLE' => array( 'label' => 'Taxable', 'format' => 'enum', 'extras' => 'required' , 'value'=>''),
	'RATE_DESC' => array( 'label' => 'Description','format' => 'textarea', 'extras' => 'required rows="5"')
);

?>