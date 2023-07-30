<?php
## -> A complete page BY MONA.

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');
require_once( "sts_table_class.php" );

class sts_driverrate_mng extends sts_table {
	// Constructor does not need the table name
	public function __construct( $database, $debug = false )
	{
		$this->debug =  $debug;
		$this->primary_key = "RATE_ID";
		parent::__construct( $database, DRIVER_RATES, $debug);		
		if( $this->debug ) echo "<p>Create sts_driverrate $this->table_name pk = $this->primary_key</p>";
	}
}

$sts_result_drivers_layout_rate	= array(
	'RATE_ID' => array( 'format' => 'hidden' ),
	'ISDELETED' => array( 'format' => 'hidden' ),
	'RATE_CODE' => array( 'label' => 'Rate Code', 'format' => 'text' ),
	'RATE_NAME' => array( 'label' => 'Rate Name', 'format' => 'text' ),
	'RATE_DESC' => array( 'label' => 'Rate Description', 'format' => 'text' ),
	'TEAM_DRIVER' => array( 'label' => 'Team', 'format' => 'bool', 'align' => 'center' ),
	'RATE_PER_MILES' => array( 'label' => 'Rate', 'format' => 'number', 'align' => 'right' ),
	'FREE_HOURS' => array( 'label' => 'Free Hours', 'format' => 'number', 'align' => 'right' ),
	'RATE_BONUS' => array( 'label' => 'Bonus', 'format' => 'bool', 'align' => 'center' ),
	'ZONES'=>array('label'=>'Zones','format'=>'text')	
);

$sts_result_drivers_rate = array(
	'title' => '<img src="images/driver_icon.png" alt="driver_icon" height="24"> Driver\'s Rates List ',
	'sort' => 'RATE_ID asc , RATE_CODE asc',
	'cancel' => 'index.php',
	'add' => 'exp_adddriverrate.php',
	'addbutton' => 'Add Driver Rate',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_editdriverrate.php?CODE=', 'key' => 'RATE_ID', 'label' => 'RATE_CODE', 'tip' => 'Edit driver rate ', 'icon' => 'glyphicon glyphicon-edit' ),
array( 'url' => 'exp_deletedriverrate.php?TYPE=del&CODE=', 'key' => 'RATE_ID', 'label' => 'RATE_CODE', 'tip' => 'Delete driver rate ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes', 'showif' => 'notdeleted' ),
array( 'url' => 'exp_deletedriverrate.php?TYPE=undel&CODE=', 'key' => 'RATE_ID', 'label' => 'RATE_CODE', 'tip' => 'Undelete driver rate ', 'icon' => 'glyphicon glyphicon-arrow-left', 'showif' => 'deleted' ),
array( 'url' => 'exp_deletedriverrate.php?TYPE=permdel&CODE=', 'key' => 'RATE_ID', 'label' => 'RATE_CODE', 'tip' => 'Permanently Delete driver rate ', 'icon' => 'glyphicon glyphicon-remove', 'confirm' => 'yes', 'showif' => 'deleted' )
	)
);

$sts_form_adddriverrate_form = array( //! $sts_form_adddriverrate_form
	'title' => '<img src="images/money_bag.png" alt="driver_icon" height="24"> Add Driver Rates',
	'action' => 'exp_adddriverrate.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listdriverrates.php',
	'name' => 'adddriverrate',
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
				<label for="RATE_CODE" class="col-sm-4 control-label">#RATE_NAME#</label>
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
				<label for="ZONES" class="col-sm-4 control-label">#ZONES#</label>
				<div class="col-sm-8">
					%ZONES%
				</div>
			</div>
			
			<div class="form-group">
				<label for="CLIENT_RATE" class="col-sm-4 control-label">#CLIENT_RATE#</label>
				<div class="col-sm-8">
					%CLIENT_RATE%
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
			
			<div class="form-group" id="percent" hidden>
				<label for="RATE_ONE" class="col-sm-4 control-label">#RATE_ONE#</label>
				<div class="col-sm-8">
					%RATE_ONE%
				</div>
			</div>
			<div class="form-group">
				<label for="RATE_PER_MILES" class="col-sm-4 control-label">#RATE_PER_MILES#</label>
				<div class="col-sm-8">
					%RATE_PER_MILES%
				</div>
			</div>
			<div class="form-group">
				<label for="FREE_HOURS" class="col-sm-4 control-label">#FREE_HOURS#</label>
				<div class="col-sm-8">
					%FREE_HOURS%
				</div>
			</div>
			<div class="form-group">
				<label for="TEAM_DRIVER" class="col-sm-4 control-label">#TEAM_DRIVER#</label>
				<div class="col-sm-8">
					%TEAM_DRIVER%
				</div>
			</div>
			<div class="form-group">
				<label for="RATE_BONUS" class="col-sm-4 control-label">#RATE_BONUS#</label>
				<div class="col-sm-8">
					%RATE_BONUS%
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
				<p><strong>Per Mile</strong> - The <strong>Rate</strong> is multiplied by the total distance.</p>
				<p><strong>Empty Miles</strong> - The <strong>Rate</strong> is multiplied by the distance travelled without any shipments (empty load, sum of empty legs).</p>
				<p><strong>Loaded Miles</strong> - The <strong>Rate</strong> is multiplied by the distance travelled with shipments.</p>
				<p><strong>Per Pick Up</strong> - The <strong>Rate</strong> is multiplied by the number of pick up stops.</p>
				<p><strong>Per Drop</strong> - The <strong>Rate</strong> is multiplied by the number of drop (deliver) stops.</p>
				<p><strong>Per Stop</strong> - The <strong>Rate</strong> is multiplied by the number of extra stops.</p>
				<p><strong>Line haul percentage</strong> - The <strong>Rate</strong> is multiplied by<br>SUM(Freight + Stop off + loading detention + unloading detention).</p>
				<p><strong>Per Mile + Rate</strong> - SUM(All except FSC, including mileage * Per Mile) * Rate.</p>
				<p><strong>Client Rate Percentage</strong> - selected rate * Rate.</p>
				<p><strong>Flat Rate</strong> - Flat Rate.<p>
				<ul>
				<li>IF you selected a client rate, this is conditional on the client rate > 0.</li>
				<li>IF you selected a shipper, this is conditional on the shipper being present.</li>
				<li>IF you selected a consignee, this is conditional on the consignee being present.</li>
				</ul>
				<br>
				<p><strong>Team Rate</strong> - This rate only applies to Team Drivers</p>
				<p><strong>Bonus</strong> - This rate is bonusable</p>
				<br>
				<p>Other categories require the value to be entered manually in the driver pay screen.</p>
				<p>Free Hours = number of hours free before rate applies</p>
			</div>
		</div>
	</div>
	');

$sts_form_editdriverrate_form = array( //! $sts_form_editdriverrate_form
	'title' => '<img src="images/money_bag.png" alt="driver_icon" height="24"> Edit Driver Rates',
	'action' => 'exp_editdriverrate.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listdriverrates.php',
	'name' => 'editdriverrate',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Cancel',
		'layout' => '
	%RATE_ID%
	<div class="form-group">
		<div class="col-sm-4">
			<div class="form-group">
				<label for="RATE_CODE" class="col-sm-4 control-label">#RATE_CODE#</label>
				<div class="col-sm-8">
					%RATE_CODE%
				</div>
			</div>
			
			<div class="form-group">
				<label for="RATE_CODE" class="col-sm-4 control-label">#RATE_NAME#</label>
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
				<label for="ZONES" class="col-sm-4 control-label">#ZONES#</label>
				<div class="col-sm-8">
					%ZONES%
				</div>
			</div>
			
			<div class="form-group">
				<label for="CLIENT_RATE" class="col-sm-4 control-label">#CLIENT_RATE#</label>
				<div class="col-sm-8">
					%CLIENT_RATE%
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
			
			<div class="form-group" id="percent" hidden>
				<label for="RATE_ONE" class="col-sm-4 control-label">#RATE_ONE#</label>
				<div class="col-sm-8">
					%RATE_ONE%
				</div>
			</div>
			<div class="form-group">
				<label for="RATE_PER_MILES" class="col-sm-4 control-label">#RATE_PER_MILES#</label>
				<div class="col-sm-8">
					%RATE_PER_MILES%
				</div>
			</div>
			<div class="form-group">
				<label for="FREE_HOURS" class="col-sm-4 control-label">#FREE_HOURS#</label>
				<div class="col-sm-8">
					%FREE_HOURS%
				</div>
			</div>
			<div class="form-group">
				<label for="TEAM_DRIVER" class="col-sm-4 control-label">#TEAM_DRIVER#</label>
				<div class="col-sm-8">
					%TEAM_DRIVER%
				</div>
			</div>
			<div class="form-group">
				<label for="RATE_BONUS" class="col-sm-4 control-label">#RATE_BONUS#</label>
				<div class="col-sm-8">
					%RATE_BONUS%
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
				<p><strong>Per Mile</strong> - The <strong>Rate</strong> is multiplied by the total distance.</p>
				<p><strong>Empty Miles</strong> - The <strong>Rate</strong> is multiplied by the distance travelled without any shipments (empty load, sum of empty legs).</p>
				<p><strong>Loaded Miles</strong> - The <strong>Rate</strong> is multiplied by the distance travelled with shipments.</p>
				<p><strong>Per Pick Up</strong> - The <strong>Rate</strong> is multiplied by the number of pick up stops.</p>
				<p><strong>Per Drop</strong> - The <strong>Rate</strong> is multiplied by the number of drop (deliver) stops.</p>
				<p><strong>Per Stop</strong> - The <strong>Rate</strong> is multiplied by the number of extra stops.</p>
				<p><strong>Line haul percentage</strong> - The <strong>Rate</strong> is multiplied by<br>SUM(Freight + Stop off + loading detention + unloading detention)</p>
				<p><strong>Per Mile + Rate</strong> - SUM(All except FSC, including mileage * Per Mile) * Rate.</p>
				<p><strong>Client Rate Percentage</strong> - selected rate * Rate.</p>
				<p><strong>Flat Rate</strong> - Flat Rate.<p>
				<ul>
				<li>IF you selected a client rate, this is conditional on the client rate > 0.</li>
				<li>IF you selected a shipper, this is conditional on the shipper being present.</li>
				<li>IF you selected a consignee, this is conditional on the consignee being present.</li>
				</ul>
				<br>
				<p><strong>Team Rate</strong> - This rate only applies to Team Drivers</p>
				<p><strong>Bonus</strong> - This rate is bonusable</p>
				<br>
				<p>Other categories require the value to be entered manually in the driver pay screen.</p>
				<p>Free Hours = number of hours free before rate applies</p>
			</div>
		</div>
	</div>
	');

	$sql_obj	=	new sts_table($exspeedite_db , DRIVER_RATES , $sts_debug);
	$max_range_id			=  $sql_obj->database->get_multiple_rows(" SELECT 	MAX(RATE_CODE) FROM ".DRIVER_RATES." ");	
	if($max_range_id[0]['MAX(RATE_CODE)']==''){$res['RATE_CODE']='C101';}else{ $arrr=explode('C',$max_range_id[0]['MAX(RATE_CODE)']);$r=$arrr[1]+1;  $res['RATE_CODE']='C'.$r;}

	$sts_form_add_driverrate_fields = array( //! $sts_form_add_driverrate_fields
		'RATE_CODE' => array( 'label' => 'Rate Code', 'format' => 'text',  'extras' => 'readonly' , 'value'=> $res['RATE_CODE']),
		'RATE_NAME' =>array( 'label' => 'Rate Name', 'format' => 'text', 'extras' => 'required' ),
		'CATEGORY' => array( 'label' => 'Rate Category', 'format' => 'table', 'table' => CATEGORY_TABLE,
			'key' => 'CATEGORY_CODE', 'fields' => 'CATEGORY_NAME' ,'extras' => 'required' ),
		'ZONES' => array( 'label' => 'Zones', 'format' => 'table', 'table' => ZONE_FILTER_TABLE,
			'key' => 'ZF_NAME', 'fields' => 'ZF_NAME' ),
		'CLIENT_RATE' => array( 'label' => 'Client Rate', 'format' => 'table', 'table' => CLIENT_CAT_RATE,
			'key' => 'CLIENT_RATE_ID', 'fields' => 'RATE_CODE,RATE_NAME' ),
		'SHIPPER_CLIENT_CODE' => array( 'label' => 'Shipper', 'format' => 'table', 'table' => CLIENT_TABLE,
			'key' => 'CLIENT_CODE', 'fields' => 'CLIENT_NAME',
			'condition' => 'SHIPPER', 'nolink' => true ),
		'CONS_CLIENT_CODE' => array( 'label' => 'Consignee', 'format' => 'table', 'table' => CLIENT_TABLE,
			'key' => 'CLIENT_CODE', 'fields' => 'CLIENT_NAME',
			'condition' => 'CONSIGNEE', 'nolink' => true ),
		'RATE_PER_MILES' => array( 'label' => 'Rate', 'format' => 'number' , 'align' => 'right', 'extras' => 'required negfloat', 'decimal' => '3' ),
		'FREE_HOURS' => array( 'label' => 'Free Hours', 'format' => 'number', 'align' => 'right' ),
		'TEAM_DRIVER' => array( 'label' => 'Team Rate', 'format' => 'bool' ),
		'RATE_ONE' => array( 'label' => 'Per Mile', 'format' => 'number' , 'align' => 'right' ),
		'RATE_BONUS' => array( 'label' => 'Bonus', 'format' => 'bool' ),
		'RATE_DESC' => array( 'label' => 'Description','format' => 'textarea', 'extras' => 'required rows="5"' , 'value'=>'')
	);

	$sts_form_edit_driverrate_fields = array( //! $sts_form_edit_driverrate_fields
		'RATE_ID' => array( 'format' => 'hidden' ),
		'RATE_CODE' => array( 'label' => 'Rate Code', 'format' => 'text',  'extras' => 'readonly' , 'value'=> $res['RATE_CODE']),
		'RATE_NAME' =>array( 'label' => 'Rate Name', 'format' => 'text', 'extras' => 'required' ),
		'CATEGORY' => array( 'label' => 'Rate Category', 'format' => 'table', 'table' => CATEGORY_TABLE,
			'key' => 'CATEGORY_CODE', 'fields' => 'CATEGORY_NAME' ,'extras' => 'required' ),
		'ZONES' => array( 'label' => 'Zones', 'format' => 'table', 'table' => ZONE_FILTER_TABLE,
			'key' => 'ZF_NAME', 'fields' => 'ZF_NAME' ),
		'CLIENT_RATE' => array( 'label' => 'Client Rate', 'format' => 'table', 'table' => CLIENT_CAT_RATE,
			'key' => 'CLIENT_RATE_ID', 'fields' => 'RATE_CODE,RATE_NAME' ),
		'SHIPPER_CLIENT_CODE' => array( 'label' => 'Shipper', 'format' => 'table', 'table' => CLIENT_TABLE,
			'key' => 'CLIENT_CODE', 'fields' => 'CLIENT_NAME',
			'condition' => 'SHIPPER', 'nolink' => true ),
		'CONS_CLIENT_CODE' => array( 'label' => 'Consignee', 'format' => 'table', 'table' => CLIENT_TABLE,
			'key' => 'CLIENT_CODE', 'fields' => 'CLIENT_NAME',
			'condition' => 'CONSIGNEE', 'nolink' => true ),
		'RATE_PER_MILES' => array( 'label' => 'Rate', 'format' => 'number' , 'align' => 'right', 'extras' => 'required negfloat', 'decimal' => '3' ),
		'FREE_HOURS' => array( 'label' => 'Free Hours', 'format' => 'number', 'align' => 'right' ),
		'TEAM_DRIVER' => array( 'label' => 'Team Rate', 'format' => 'bool' ),
		'RATE_ONE' => array( 'label' => 'Per Mile', 'format' => 'number' , 'align' => 'right' ),
		'RATE_BONUS' => array( 'label' => 'Bonus', 'format' => 'bool', 'extras' => 'required' ),
		'RATE_DESC' => array( 'label' => 'Description','format' => 'textarea', 'extras' => 'required rows="5"' , 'value'=>'')
	);

?>