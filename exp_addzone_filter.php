<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[ZONE_FILTER_TABLE] );	// Make sure we should be here

$sts_subtitle = (isset($_GET['CODE']) ? "Edit" : "Add")." Zone Filter";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );
require_once( "include/sts_zone_filter_class.php" );

	function load_states() {
		global $exspeedite_db;
		
		$states_table = new sts_table($exspeedite_db, STATES_TABLE, false ); //$this->debug
		
		$states = array();
		foreach( $states_table->fetch_rows() as $row ) {
			$states[$row['abbrev']] = $row['STATE_NAME'];
		}
		
		return $states;
	}

?>

<div class="container" role="main">

<!--
<div class="alert alert-warning alert-dismissable">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
  <strong>Warning!</strong> Work in progress.
</div>
-->

<?php
echo '<h2><img src="images/zone_icon.png" alt="zone_icon" height="24"> 
	'.(isset($_GET['CODE']) ? "Edit" : "Add").' Zone Filter
	<div class="btn-group">
	<a class="btn btn-md btn-success" id="SAVE_BUTTON" href="#" ><span class="glyphicon glyphicon-floppy-save" deleted></span> Save Changes</a>
	<a class="btn btn-md btn-default" id="BACK_BUTTON" href="exp_listzone_filter.php"><span class="glyphicon glyphicon-arrow-right"></span> Back</a>
	<div class="btn-group">
	</h2>
	<br>';

$got_filters = false;
if( isset($_GET['CODE']) ) {

	$zone_filter_table = new sts_zone_filter($exspeedite_db, $sts_debug);
	
	$filters = $zone_filter_table->fetch_rows("ZF_NAME = '".$_GET['CODE']."'");
	
	if( $filters && count($filters) > 0 ) {
		$got_filters = true;
		$filter_name = $filters[0]['ZF_NAME'];
	}

}

?>
	<div class="row">
		<div class="col-sm-6">
			<form role="form" class="form-horizontal" action="#" 
				method="post" enctype="multipart/form-data" 
				name="addzone_filter" id="addzone_filter">
				<div class="form-group">
					<label for="NAME" class="col-sm-4 control-label">Name</label>
					<div class="col-sm-6">
						<input class="form-control" name="ZF_NAME" id="ZF_NAME" type="text"  
						placeholder="Name" maxlength="20" <?php if( $got_filters ) echo 'value="'.$filter_name.'"'; ?> required autofocus>
					</div>
				</div>
				<div class="form-group">
					<label for="STATE" class="col-sm-4 control-label">State</label>
					<div class="col-sm-6">
					<?php
					$states = load_states();
					if( is_array($states) ) {
						$output = '<div class="input-group">
				<select class="form-control" name="STATE" id="STATE" >
					<option value="" selected>Choose State</option>';
						foreach( $states as $abbrev => $state_name ) {
							$output .= '
					<option value="'.$abbrev.'" >'.$abbrev.' - '.$state_name.'</option>';
						}
						$output .= '
						</select>
						<span class="input-group-btn">
						<a class="btn btn-md btn-default" id="ADDSTATE" href="#" disabled><span class="glyphicon glyphicon-arrow-right"></span> Add</a>
						</span>
						</div>';
						echo $output;
					}
					?>
					</div>
				</div>
				<div class="form-group">
					<label for="PREFIX" class="col-sm-4 control-label">Prefix</label>
					<div class="col-sm-6">
						<div class="input-group">
						<input class="form-control input-md" name="PREFIX" id="PREFIX" type="text"  
						placeholder="Prefix" maxlength="3" >
						<span class="input-group-btn">
						<a class="btn btn-md btn-default" id="ADDPREFIX" href="#" style="margin-bottom: 6px;" disabled><span class="glyphicon glyphicon-arrow-right"></span> Add</a>
						</span>
						</div>

					</div>
				</div>
				<div class="form-group">
					<label for="ZIP" class="col-sm-4 control-label">Zip</label>
					<div class="col-sm-6">
						<div class="input-group">
						<input class="form-control" name="ZIP" id="ZIP" type="text"  
						placeholder="Zip" maxlength="11" >
						<span class="input-group-btn">
						<a class="btn btn-md btn-default" id="ADDZIP" href="#" style="margin-bottom: 6px;" disabled><span class="glyphicon glyphicon-arrow-right"></span> Add</a>
						</span>
						</div>

					</div>
				</div>
			</form>
		
		</div>
		<div class="col-sm-6">
			<h4>Matching Rules</h4>
			<ul class="target">
			<?php
				if( $got_filters ) {
					foreach( $filters as $filter ) {
						echo '<li id="'.$filter['ZF_VALUE'].'" data-ztype="'.$filter['ZF_TYPE'].'" data-zvalue="'.$filter['ZF_VALUE'].'"><a class="btn btn-md btn-default" id="DEL" href="#"><span class="glyphicon glyphicon-remove"></span></a> &nbsp; <strong>'.$filter['ZF_TYPE'].':</strong> '.$filter['ZF_VALUE'].'</li>';
					}
				}
			?>
			</ul>
		</div>
	</div>

	<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="zone_filter_modal">
	  <div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
			<h4 class="modal-title" id="myModalLabel"><span class="text-success"><strong>Saving...</strong></span></h4>
		</div>
		<div class="modal-body">
			<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>
		</div>
		</div>
		</div>
	</div>

	<script language="JavaScript" type="text/javascript"><!--
		$(document).ready( function () {
		
			window.HAS_CHANGED = false;
		
			function save_button( changed ) {
				if( changed ) window.HAS_CHANGED = true;
				if( $('.target').children().length > 0 && $('#ZF_NAME').val() != '' )
					$('#SAVE_BUTTON').removeAttr('disabled');
				else
					$('#SAVE_BUTTON').attr('disabled','disabled');
			};
			
			var PREFIX_zips = new Bloodhound({
			  name: 'PREFIX',
			  remote: {
				  url: 'exp_suggest_prefix.php?code=Telephone&query=%QUERY',
				  wildcard: '%QUERY'
				  },
			  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('Prefix'),
			  queryTokenizer: Bloodhound.tokenizers.whitespace
			});
			
			PREFIX_zips.initialize();

			$('#PREFIX').typeahead(null, {
			  name: 'PREFIX',
			  minLength: 2,
			  limit: 10,
			  highlight: true,
			  display: 'Prefix',
			  source: PREFIX_zips,
			    templates: {
			    suggestion: Handlebars.compile(
			      '<p><strong>{{Prefix}}</strong> – {{CityMixedCase}}, {{State}}</p>'
			    )
			  }
			});

			var ZIP_zips = new Bloodhound({
			  name: 'ZIP',
			  remote: {
				  url: 'exp_suggest_zip.php?code=Balsamic&query=%QUERY',
				  wildcard: '%QUERY'
				  },
			  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('ZipCode'),
			  queryTokenizer: Bloodhound.tokenizers.whitespace
			});
			
			ZIP_zips.initialize();

			$('#ZIP').typeahead(null, {
			  name: 'ZIP',
			  minLength: 2,
			  limit: 10,
			  highlight: true,
			  display: 'ZipCode',
			  source: ZIP_zips,
			    templates: {
			    suggestion: Handlebars.compile(
			      '<p><strong>{{ZipCode}}</strong> – {{CityMixedCase}}, {{State}}</p>'
			    )
			  }
			});
			
			$('#STATE').on('change', function() {
				$('#ADDSTATE').removeAttr('disabled');
			});
			
			$('#ADDSTATE').click(function(event) {
				event.preventDefault();
				$(".target").append('<li id="' + $('#ZIP').val() + '" data-ztype="State" data-zvalue="' + $('#STATE').val() + '"><a class="btn btn-md btn-default" id="DEL" href="#"><span class="glyphicon glyphicon-remove"></span></a> &nbsp; <strong>State:</strong> ' + $('#STATE').val() + '</li>');
				$('#STATE').val('');
				$('#ADDSTATE').attr('disabled','disabled');
				save_button();
			});

			$('#PREFIX').bind('typeahead:selected', function(obj, datum, name) {
				$('#ADDPREFIX').removeAttr('disabled');
			});
			
			$('#ADDPREFIX').click(function(event) {
				event.preventDefault();
				$(".target").append('<li id="' + $('#ZIP').val() + '" data-ztype="Prefix" data-zvalue="' + $('#PREFIX').val() + '"><a class="btn btn-md btn-default" id="DEL" href="#"><span class="glyphicon glyphicon-remove"></span></a> &nbsp; <strong>Prefix:</strong> ' + $('#PREFIX').val() + '</li>');
				$('#PREFIX').val('');
				$('#ADDPREFIX').attr('disabled','disabled');
				save_button( true );
			});

			$('#ZIP').bind('typeahead:selected', function(obj, datum, name) {
				$('#ADDZIP').removeAttr('disabled');
			});
			
			$('#ADDZIP').click(function(event) {
				event.preventDefault();
				$(".target").append('<li id="' + $('#ZIP').val() + '" data-ztype="ZIP Code" data-zvalue="' + $('#ZIP').val() + '"><a class="btn btn-md btn-default" id="DEL" href="#"><span class="glyphicon glyphicon-remove"></span></a> &nbsp; <strong>ZIP:</strong> ' + $('#ZIP').val() + '</li>');
				$('#ZIP').val('');
				$('#ADDZIP').attr('disabled','disabled');
				save_button( true );
			});
			
			$(document).on("click", "#DEL", function(event) {
			    $(this).parent().remove();
			    save_button( true );
			});
			
			$('#ZF_NAME').on('keypress', function() {
				if( $('#ZF_NAME').val() != '' )
				    save_button( true );
			});
			
			save_button( false );
			
			$('#SAVE_BUTTON').click(function(event) {
				event.preventDefault();
				
				// Popup
				$('#zone_filter_modal').modal({
					container: 'body'
				});
				
				// Save changes
				$.ajax({
					async: false,
					url: 'exp_deletezone_filter.php',
					data: {
						code: 'Windoze8Sux',
						zname: $('#ZF_NAME').val()
					}
				});

				$("ul.target").children().each(function() {
				
					$.ajax({
						async: false,
						url: 'exp_addzfrow.php',
						data: {
							code: 'VistaSux',
							zname: $('#ZF_NAME').val(),
							ztype: $(this).data("ztype"),
							zvalue: $(this).data("zvalue")
						}
					});
				});

				// Clear popup
				$('#zone_filter_modal').modal('hide');
				
				// Move on
				window.location = "<?php echo $sts_crm_root; ?>/exp_listzone_filter.php";
			});

			$('#BACK_BUTTON').on('click', function() {
				if( window.HAS_CHANGED ) {
					var answer = confirm('You have unsaved changes that will be lost. Continue?');
					console.log(answer);
					if (answer){
						return true;
					} else {
						return false;
					}
				}
			});

		});
	//--></script>


<?php

require_once( "include/footer_inc.php" );
?>
