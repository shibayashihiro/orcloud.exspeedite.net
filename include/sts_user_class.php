<?php

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );

class sts_user extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		if( $this->debug ) echo "<p>Create sts_user</p>";
		parent::__construct( $database, USER_TABLE, $debug);
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

	// Authentication
	// Returns false if fails, or returns the user's information if successful.
	public function authenticate( $username, $password ) {

		if( $this->debug ) echo "<p>".__METHOD__.": $username, $password</p>";
		
		$check = $this->fetch_rows("USERNAME = '".$username."' AND ISACTIVE = 'Active'",
			 "USER_CODE, USERNAME, USER_GROUPS, FULLNAME, EMAIL, USER_PASSWORD");

		$result = false;
		if( is_array($check) && count($check) == 1 && 
			$this->verify( $password, $check[0]['USER_PASSWORD'] ) ) {
			$result = $check;
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.":return ".($result ? 'true' : 'false')."</p>";
		return $result;
	}

	// Delete user
	public function delete( $code, $type = '' ) {

		if( $this->debug ) echo "<p>delete $code</p>";
		
		$result = $this->delete_row( "USER_CODE = '".$code."'" );
		
		if( $this->debug ) echo "<p>delete result = ".($result ? 'true' : 'false '.$this->error())."</p>";
		return $result;
	}

	public function special_user( $name ) {
		$result = $this->fetch_rows( "USERNAME = '".$name."'", $this->primary_key );
		if( isset($result) && is_array($result) && count($result) > 0 ) {
			return $result[0][$this->primary_key];
		} else {
			return $this->add( array("USERNAME" => $name, "USER_PASSWORD" => "hjgiuoibin99",
				"USER_GROUPS" => "user", "FULLNAME" => "Special User",
				"EMAIL" => "duncan@strongtco.com") );
		}
	}
	
	public function direct_reports( $form, $pk ) {
		$directs_str = '';
		$directs = $this->fetch_rows("MANAGER = ".$pk, "USER_CODE, FULLNAME", "FULLNAME ASC");
		if( is_array($directs) && count($directs) > 0 ) {
			$directs_str = '<div id="DIRECTS" class="panel panel-default">
				  <div class="panel-heading">
				    <h3 class="panel-title">Direct Reports</h3>
				  </div>
				  <div class="panel-body">
				  <ol>
				';
			foreach( $directs as $row ) {
				$directs_str .= '<li><a href="exp_edituser.php?CODE='.$row["USER_CODE"].'">'.$row["FULLNAME"].'</a></li>';
			}

			$directs_str .= '</ol>
					</div>
				</div>
				';
			$form = str_replace('<!-- DIRECTS -->', $directs_str, $form);
		}
		return $form;
	}

    //! create a menu of users
    public function sales_menu( $selected = false, $id = 'SALES_PERSON', $match = '', $onchange = false, $any = false ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
		$select = false;

		$choices = $this->fetch_rows( 'USER_GROUPS like \'%'.EXT_GROUP_SALES.'%\'', "USER_CODE, USERNAME",
			"USERNAME ASC" );

		if( is_array($choices) && count($choices) > 0) {
			
			$select = '<select class="form-control input-sm" name="'.$id.'" id="'.$id.'"'.($onchange ? ' onchange="form.submit();"' : '').'>
			';
			if( $any ) {
				$select .= '<option value="0"';
				if( $selected && $selected == 0 )
					$select .= ' selected';
				$select .= '>All Users</option>
				';
			}
			foreach( $choices as $row ) {
				$select .= '<option value="'.$row["USER_CODE"].'"';
				if( $selected && $selected == $row["USER_CODE"] )
					$select .= ' selected';
				$select .= '>'.$row["USERNAME"].'</option>
				';
			}
			$select .= '</select>';
		}
			
		return $select;
	}
	
	// SCR# 715 - lookup PIPEDRIVE_API_TOKEN for a user
	public function pipeline_token( $code ) {
		$result = false;
		$check = $this->fetch_rows( "USER_CODE = ".$code, "PIPEDRIVE_API_TOKEN");
		
		if( is_array($check) && count($check) == 1 &&
			isset($check[0]["PIPEDRIVE_API_TOKEN"]) &&
			! empty($check[0]["PIPEDRIVE_API_TOKEN"]) )
			$result = $check[0]["PIPEDRIVE_API_TOKEN"];
		
		return $result;
	}

}

//! --- Layout Specifications - For use with sts_result
$sts_result_users_layout = array( //! $sts_result_users_layout
	'USER_CODE' => array( 'format' => 'hidden' ),
	'USERNAME' => array( 'label' => 'Username', 'format' => 'text' ),
	'FULLNAME' => array( 'label' => 'Full Name', 'format' => 'text' ),
	'ISACTIVE' => array( 'label' => 'Active', 'format' => 'text' ),
	'EMAIL' => array( 'label' => 'Email', 'format' => 'email' ),
	'USER_GROUPS' => array( 'label' => 'Groups', 'format' => 'groups', 'length' => 200 ),
	'MANAGER' => array( 'label' => 'Manager', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'FULLNAME',
		'condition' => 'USER_GROUPS like \'%'.EXT_GROUP_MANAGER.'%\'' ),
	'DRIVER' => array( 'label' => 'Driver', 'format' => 'table',
		'table' => DRIVER_TABLE, 'key' => 'DRIVER_CODE', 'fields' => 'concat_ws( \' \', FIRST_NAME , LAST_NAME )',
		'order' => 'LAST_NAME,FIRST_NAME' ),
	'CREATED_DATE' => array( 'label' => 'Created', 'format' => 'timestamp-s', 'length' => 90 ),
	'CREATED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' ),
	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'timestamp-s', 'length' => 90 ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )
);

//! --- Edit/Delete Button Specifications - For use with sts_result
$sts_result_users_edit = array( //! $sts_result_users_edit
	'title' => '<span class="glyphicon glyphicon-user"></span> Users',
	'sort' => 'USERNAME asc',
	'cancel' => 'index.php',
	'add' => 'exp_adduser.php',
	'addbutton' => 'Add User',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
	array( 'url' => 'exp_edituser.php?CODE=', 'key' => 'USER_CODE', 'label' => 'USERNAME', 'tip' => 'Edit user ', 'icon' => 'glyphicon glyphicon-edit' ),
	array( 'url' => 'exp_deleteuser.php?CODE=', 'key' => 'USER_CODE', 'label' => 'USERNAME', 'tip' => 'Delete user ', 'icon' => 'glyphicon glyphicon-remove', 'confirm' => 'yes', 'restrict' => EXT_GROUP_SUPERADMIN )
	)
);
	
//! --- Form Specifications - For use with sts_form
$sts_form_adduser_form = array( //! $sts_form_adduser_form
	'action' => 'exp_adduser.php',
	'cancel' => 'exp_listuser.php',
	'name' => 'adduser',
	'layout' => '
	<div class="row">
		<div class="col-md-6">%USERID%</div>
		<div class="col-md-6">%PASSWORD%</div>
	</div>
	<div class="row">
		<div class="col-md-6">%FULLNAME%</div>
		<div class="col-md-6">%EMAIL%</div>
	</div>
	<div class="row">
		<div class="col-md-12">%GROUPS%</div>
	</div>'
);

$sts_form_adduser_form2 = array( //! $sts_form_adduser_form2
	'title' => '<span class="glyphicon glyphicon-user"></span> Add User',
	'action' => 'exp_adduser.php',
	'cancel' => 'exp_listuser.php',
	'name' => 'adduser',
	'okbutton' => 'Add User',
	'cancelbutton' => 'Cancel',
	'layout' => '
	<div class="form-group">
		<div class="col-sm-7">
			<div class="form-group tighter" id="FG_USERNAME">
				<label for="USERNAME" class="col-sm-4 control-label">#USERNAME#</label>
				<div class="col-sm-8">
					%USERNAME%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="USER_PASSWORD" class="col-sm-4 control-label">#USER_PASSWORD#</label>
				<div class="col-sm-8">
					%USER_PASSWORD%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="FULLNAME" class="col-sm-4 control-label">#FULLNAME#</label>
				<div class="col-sm-8">
					%FULLNAME%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="EMAIL" class="col-sm-4 control-label">#EMAIL#</label>
				<div class="col-sm-8">
					%EMAIL%
				</div>
			</div>
			<div class="form-group tighter">
				<div class="text-right col-sm-4">
					<label for="USER_GROUPS" class="control-label">#USER_GROUPS#</label><br>
					<button class="text-right btn btn-sm btn-primary" type="button"
						data-toggle="collapse" data-target="#groups_help" aria-expanded="false"
						aria-controls="groups_help"><span class="glyphicon glyphicon-info-sign"></span> Help</button>
				</div>
				<div class="col-sm-8">
					%USER_GROUPS%
				</div>
			</div>
			<div class="collapse" id="groups_help">
				<div class="col-sm-8 col-sm-offset-4 alert alert-info">
					<p class="text-center lead"><strong><span class="glyphicon glyphicon-info-sign"></span> User Groups</strong></p>
					<p><strong>admin</strong> - system admin tasks if checked will have rights to all offices and companies</p>
					<p><strong>user</strong> - basic user rights</p>
					<p><strong>sales</strong> - access clients. If CRM is activated will have rights to the CRM</p>
					<p><strong>shipments</strong> - access shipments</p>
					<p><strong>driver</strong> - driver check-in when checked must link driver from drop-down menu</p>
					<p><strong>dispatch</strong> - access loads</p>
					<p><strong>billing</strong> - access shipment billing</p>
					<p><strong>finance</strong> - approve/unapprove/export to accounting</p>
					<p><strong>Sage50</strong> - export to Sage 50</p>
					<p><strong>HR</strong> - allow edit driver physical due</p>
					<p><strong>random</strong> - access to driver random events. You need HR + random to edit random events.</p>
					<p><strong>profiles</strong> - access clients & carriers</p>
					<p><strong>fleet</strong> - access drivers & trucks, view inspection reports</p>
					<p><strong>manager</strong> - Is a manager, has directs. Also can back up a load.</p>
					<p><strong>mechanic</strong> - Is a mechanic, for inspection reports module.</p>
					<p><strong>inspection</strong> - Can update inspection reports.</p>
					<p><strong>debug</strong> - for support/diagnostics only for Exspeedite support personnel</p>
				</div>
			</div>
			<div class="form-group tighter" id="MARGIN" hidden>
				<label for="SALES_MARGIN" class="col-sm-4 control-label">#SALES_MARGIN#</label>
				<div class="col-sm-8">
					%SALES_MARGIN%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="MANAGER" class="col-sm-4 control-label">#MANAGER#</label>
				<div class="col-sm-8">
					%MANAGER%
				</div>
			</div>
			<div class="form-group tighter" id="LINKED_DRIVER" hidden>
				<label for="DRIVER" class="col-sm-4 control-label">#DRIVER#</label>
				<div class="col-sm-8">
					%DRIVER%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="ISACTIVE" class="col-sm-4 control-label">#ISACTIVE#</label>
				<div class="col-sm-8">
					%ISACTIVE%
				</div>
			</div>
			<!-- PIPEDRIVE1 -->
			<div class="form-group tighter">
				<label for="PIPEDRIVE_API_TOKEN" class="col-sm-4 control-label">#PIPEDRIVE_API_TOKEN#</label>
				<div class="col-sm-8">
					%PIPEDRIVE_API_TOKEN%
				</div>
			</div>
			<!-- PIPEDRIVE2 -->
		</div>
		<div class="col-sm-4">
			<!-- OFFICES -->
			<!-- REPORTS -->
		</div>
	</div>
	'
);

$sts_form_edituser_form = array( //! $sts_form_edituser_form
	'title' => '<span class="glyphicon glyphicon-user"></span> Edit User',
	'action' => 'exp_edituser.php',
	'cancel' => 'exp_listuser.php',
	'name' => 'edituser',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Cancel / Back To Users',
	'noautocomplete' => true,
	'layout' => '
	%USER_CODE%
	<div class="form-group">
		<div class="col-sm-8">
			<div class="form-group tighter" id="FG_USERNAME">
				<label for="USERNAME" class="col-sm-4 control-label">#USERNAME#</label>
				<div class="col-sm-8">
					%USERNAME%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="USER_PASSWORD" class="col-sm-4 control-label">#USER_PASSWORD#</label>
				<div class="col-sm-8">
					%USER_PASSWORD%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="FULLNAME" class="col-sm-4 control-label">#FULLNAME#</label>
				<div class="col-sm-8">
					%FULLNAME%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="EMAIL" class="col-sm-4 control-label">#EMAIL#</label>
				<div class="col-sm-8">
					%EMAIL%
				</div>
			</div>
			<div class="form-group tighter">
				<div class="text-right col-sm-4">
					<label for="USER_GROUPS" class="control-label">#USER_GROUPS#</label><br>
					<button class="text-right btn btn-sm btn-primary" type="button"
						data-toggle="collapse" data-target="#groups_help" aria-expanded="false"
						aria-controls="groups_help"><span class="glyphicon glyphicon-info-sign"></span> Help</button>
				</div>
				<div class="col-sm-8">
					%USER_GROUPS%
				</div>
			</div>
			<div class="collapse" id="groups_help">
				<div class="col-sm-8 col-sm-offset-4 alert alert-info">
					<p class="text-center lead"><strong><span class="glyphicon glyphicon-info-sign"></span> User Groups</strong></p>
					<p><strong>admin</strong> - system admin tasks if checked will have rights to all offices and companies</p>
					<p><strong>user</strong> - basic user rights</p>
					<p><strong>sales</strong> - access clients. If CRM is activated will have rights to the CRM</p>
					<p><strong>shipments</strong> - access shipments</p>
					<p><strong>driver</strong> - driver check-in when checked must link driver from drop-down menu</p>
					<p><strong>dispatch</strong> - access loads</p>
					<p><strong>billing</strong> - access shipment billing</p>
					<p><strong>finance</strong> - approve/unapprove/export to accounting</p>
					<p><strong>Sage50</strong> - export to Sage 50</p>
					<p><strong>HR</strong> - allow edit driver physical due</p>
					<p><strong>random</strong> - access to driver random events. You need HR + random to edit random events.</p>
					<p><strong>profiles</strong> - access clients & carriers</p>
					<p><strong>fleet</strong> - access drivers & trucks, view inspection reports</p>
					<p><strong>manager</strong> - Is a manager, has directs. Also can back up a load.</p>
					<p><strong>mechanic</strong> - Is a mechanic, for inspection reports module.</p>
					<p><strong>inspection</strong> - Can update inspection reports.</p>
					<p><strong>debug</strong> - for support/diagnostics only for Exspeedite support personnel</p>
				</div>
			</div>

			<div class="form-group tighter" id="MARGIN" hidden>
				<label for="SALES_MARGIN" class="col-sm-4 control-label">#SALES_MARGIN#</label>
				<div class="col-sm-8">
					%SALES_MARGIN%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="MANAGER" class="col-sm-4 control-label">#MANAGER#</label>
				<div class="col-sm-8">
					%MANAGER%
				</div>
			</div>
			<div class="form-group tighter" id="LINKED_DRIVER" hidden>
				<label for="DRIVER" class="col-sm-4 control-label">#DRIVER#</label>
				<div class="col-sm-8">
					%DRIVER%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="ISACTIVE" class="col-sm-4 control-label">#ISACTIVE#</label>
				<div class="col-sm-8">
					%ISACTIVE%
				</div>
			</div>
			<!-- PIPEDRIVE1 -->
			<div class="form-group tighter">
				<label for="PIPEDRIVE_API_TOKEN" class="col-sm-4 control-label">#PIPEDRIVE_API_TOKEN#</label>
				<div class="col-sm-8">
					%PIPEDRIVE_API_TOKEN%
				</div>
			</div>
			<!-- PIPEDRIVE2 -->
		</div>
		<div class="col-sm-4">
			<!-- OFFICES -->
			<!-- REPORTS -->
			<!-- DIRECTS -->
		</div>
	</div>
	'
);

//! --- Field Specifications - For use with sts_form
$sts_form_add_user_fields = array( //! $sts_form_add_user_fields
	'USERNAME' => array( 'label' => 'Username', 'format' => 'username',
		'extras' => 'required autofocus' ),
	'USER_PASSWORD' => array( 'label' => 'Password', 'format' => 'password', 'length' => '12',
		'extras' => 'required autocomplete="new-password"' ),
	'FULLNAME' => array( 'label' => 'Full Name', 'format' => 'text',
		'extras' => 'required' ),
	'EMAIL' => array( 'label' => 'Email', 'format' => 'email',
		'extras' => 'required' ),
	'SALES_MARGIN' => array( 'label' => 'Sales Margin', 'format' => 'number', 'decimal' => '2', 'align' => 'right' ),
	'DRIVER' => array( 'label' => 'Linked Driver', 'format' => 'table',
		'table' => DRIVER_TABLE, 'key' => 'DRIVER_CODE', 'fields' => 'FIRST_NAME,LAST_NAME',
		'back' => 'USERNAME',
		'order' => 'LAST_NAME,FIRST_NAME' ),
	'MANAGER' => array( 'label' => 'Reports To', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'FULLNAME',
		'condition' => 'USER_GROUPS like \'%'.EXT_GROUP_MANAGER.'%\'' ),
	'USER_GROUPS' => array( 'label' => 'Groups', 'format' => 'groups', 
		'extras' => 'required' ),
	'ISACTIVE' => array( 'label' => 'Active', 'format' => 'enum', 'value' => 'Active' ),
	'PIPEDRIVE_API_TOKEN' => array( 'label' => 'Pipedrive Token', 'format' => 'text' ),
);

$sts_form_edit_user_fields = array( //! $sts_form_edit_user_fields
	'USER_CODE' => array( 'format' => 'hidden' ),	// primary key must be first
	'USERNAME' => array( 'label' => 'Username', 'format' => 'username', 
		'extras' => 'required autofocus' ),
	'USER_PASSWORD' => array( 'label' => 'Password', 'format' => 'password', 'length' => '12',
		'extras' => 'autocomplete="new-password"', 'placeholder' => 'Password (keep blank to retain original)' ),
	'FULLNAME' => array( 'label' => 'Full Name', 'format' => 'text',
		'extras' => 'required' ),
	'EMAIL' => array( 'label' => 'Email', 'format' => 'email',
		'extras' => 'required' ),
	'SALES_MARGIN' => array( 'label' => 'Sales Margin', 'format' => 'number', 'decimal' => '2', 'align' => 'right' ),
	'DRIVER' => array( 'label' => 'Linked Driver', 'format' => 'table',
		'table' => DRIVER_TABLE, 'key' => 'DRIVER_CODE', 'fields' => 'FIRST_NAME,LAST_NAME',
		'back' => 'USERNAME',
		'order' => 'LAST_NAME,FIRST_NAME' ),
	'MANAGER' => array( 'label' => 'Reports To', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'FULLNAME',
		'condition' => 'USER_GROUPS like \'%'.EXT_GROUP_MANAGER.'%\'' ),
	'USER_GROUPS' => array( 'label' => 'Groups', 'format' => 'groups', 
		'extras' => 'required' ),
	'ISACTIVE' => array( 'label' => 'Active', 'format' => 'enum' ),
	'PIPEDRIVE_API_TOKEN' => array( 'label' => 'Pipedrive Token', 'format' => 'text' ),
);

if( isset($sts_debug) && $sts_debug ) echo "<p>sts_user_class</p>";


?>
