<?php 

// $Id: exp_editcarrier_amt.php 5030 2023-04-12 20:31:34Z duncan $
// Edit carrier amount or driver note.

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_DISPATCH, EXT_GROUP_DRIVER );	// Make sure we should be here

$sts_subtitle = "Edit Carrier Amounts";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_result_class.php" );
require_once( "include/sts_form_class.php" );
require_once( "include/sts_load_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$multi_currency = $setting_table->get( 'option', 'MULTI_CURRENCY' ) == 'true';
$multi_company = $setting_table->get("option", "MULTI_COMPANY") == 'true';

//! If not using multi-currency, hide Currency
if( ! $multi_currency ) {
	$match = preg_quote('<!-- CURRENCY1 -->').'(.*)'.preg_quote('<!-- CURRENCY2 -->');
	$sts_form_edit_carrier_amt_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_edit_carrier_amt_form['layout'], 1);	

}

$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
$load_code = isset($_GET['CODE']) ? $_GET['CODE'] : $_POST['LOAD_CODE'];

if( $multi_company ) {
	$office = $load_table->database->get_one_row("
		SELECT MIN( SHIPMENT_CODE ) S, SS_NUMBER
		FROM EXP_SHIPMENT
		WHERE LOAD_CODE = $load_code
		AND COALESCE(SS_NUMBER, '') <> ''");
	if( is_array($office) && ! empty($office["SS_NUMBER"]) ) {
		$sts_form_edit_carrier_amt_form['title'] .= ' / '.$office["SS_NUMBER"];
		$sts_form_edit_driver_note_form['title'] .= ' / '.$office["SS_NUMBER"];
	}
}

$check = $load_table->fetch_rows( "LOAD_CODE = ".$load_code, "CARRIER");
if( is_array($check) && count($check) == 1 && isset($check[0]) && 
	isset($check[0]["CARRIER"]) && $check[0]["CARRIER"] > 0) {
	
	//! SCR# 717 - disable terms menu if not manager or admin
	if( ! $my_session->in_group( EXT_GROUP_MANAGER ) &&
		! $my_session->in_group( EXT_GROUP_ADMIN ) ) {
		$sts_form_edit_carrier_amt_fields['TERMS']['static'] = true;
	}	
	
	$load_form = new sts_form($sts_form_edit_carrier_amt_form,
		$sts_form_edit_carrier_amt_fields, $load_table, $sts_debug);
	$is_carrier = true;
} else {
	$load_form = new sts_form($sts_form_edit_driver_note_form,
		$sts_form_edit_driver_note_fields, $load_table, $sts_debug);
	$is_carrier = false;
}

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	
	//! Log finance user event if there are changes to the carrier pay
	if( isset($_POST["CARRIER_BASE"]) ) {
		$check = $load_table->fetch_rows("LOAD_CODE = ".$_POST["LOAD_CODE"],
			"CARRIER, CURRENCY, LUMPER_TOTAL, LUMPER_CURRENCY, TERMS,
			(SELECT ITEM FROM EXP_ITEM_LIST
			WHERE ITEM_CODE = ".$_POST["TERMS"]." AND ITEM_TYPE = 'Vendor Terms') AS TERMS_NAME,
			CARRIER_BASE, CARRIER_FSC, CARRIER_HANDLING, LOAD_OFFICE_NUM(LOAD_CODE) AS OFFICE_NUM");
		
		$changes = array();
		if( is_array($check) && count($check) == 1 ) {
			if( isset($_POST["CURRENCY"]) &&
				(! isset($check[0]["CURRENCY"]) ||
				$_POST["CURRENCY"] <> $check[0]["CURRENCY"]) )
				$changes[] = "currency to ".(isset($_POST["CURRENCY"]) ? $_POST["CURRENCY"] : 0);
				
			if( isset($_POST["TERMS"]) &&
				(! isset($check[0]["TERMS"]) ||
				$_POST["TERMS"] <> $check[0]["TERMS"]) )
				$changes[] = "terms to ".(isset($_POST["TERMS_NAME"]) ? $_POST["CURRENCY"] : 0);
			
			if( isset($_POST["CARRIER_BASE"]) &&
				(! isset($check[0]["CARRIER_BASE"]) ||
				$_POST["CARRIER_BASE"] <> $check[0]["CARRIER_BASE"]) )
				$changes[] = "base to $".
				number_format((isset($_POST["CARRIER_BASE"]) && $_POST["CARRIER_BASE"] > 0 ? $_POST["CARRIER_BASE"] : 0),2).' '.
				(isset($_POST["CURRENCY"]) ? $_POST["CURRENCY"] : $check[0]["CURRENCY"]);
			
			if( isset($_POST["CARRIER_FSC"]) &&
				(! isset($check[0]["CARRIER_FSC"]) ||
				$_POST["CARRIER_FSC"] <> $check[0]["CARRIER_FSC"]) )
				$changes[] = "FSC to $".
				number_format((isset($_POST["CARRIER_FSC"]) && $_POST["CARRIER_FSC"] > 0 ? $_POST["CARRIER_FSC"] : 0),2).' '.
				(isset($_POST["CURRENCY"]) ? $_POST["CURRENCY"] : $check[0]["CURRENCY"]);
			
			if( isset($_POST["CARRIER_HANDLING"]) &&
				(! isset($check[0]["CARRIER_HANDLING"]) ||
				$_POST["CARRIER_HANDLING"] <> $check[0]["CARRIER_HANDLING"]) )
				$changes[] = "handling/lumper to $".
				number_format((isset($_POST["CARRIER_HANDLING"]) && $_POST["CARRIER_HANDLING"] > 0 ? $_POST["CARRIER_HANDLING"] : 0),2).' '.
				(isset($_POST["LUMPER"]) && $_POST["LUMPER"] > 0 ?
					(isset($_POST["LUMPER_CURRENCY"]) ? $_POST["LUMPER_CURRENCY"] : $check[0]["LUMPER_CURRENCY"]) :
					(isset($_POST["CURRENCY"]) ? $_POST["CURRENCY"] : $check[0]["CURRENCY"]) );
			
			if( isset($_POST["LUMPER_CURRENCY"]) &&
				(! isset($check[0]["LUMPER_CURRENCY"]) ||
				$_POST["LUMPER_CURRENCY"] <> $check[0]["LUMPER_CURRENCY"]) )
				$changes[] = "lumper currency to ".(isset($_POST["LUMPER_CURRENCY"]) && $_POST["LUMPER_CURRENCY"] > 0 ? $_POST["LUMPER_CURRENCY"] : '');
			
			if( count($changes) > 0 ) {
				require_once( "sts_user_log_class.php" );
				$user_log = sts_user_log::getInstance($exspeedite_db, $sts_debug);
				
				//! Add to load history as well as user event log
				$change_str = 'Updated carrier pay ('.implode(', ', $changes).') for load '.$_POST["LOAD_CODE"].(empty($check[0]["OFFICE_NUM"]) ? '' : ' / '.$check[0]["OFFICE_NUM"]);
				$user_log->log_event('finance', $change_str);
				$load_table->add_load_status( $_POST["LOAD_CODE"], $change_str);
			}
			
		}
		
	}
	
	$result = $load_form->process_edit_form();

	if( $result ) {
		if( $sts_debug ) die; // So we can see the results
		reload_page ( "exp_viewload.php?CODE=".$_POST['LOAD_CODE'] );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$load_table->error()."</p>";
	echo $load_form->render( $value );
} else if( isset($_GET['CODE']) ) {
	$result = $load_table->fetch_rows($load_table->primary_key." = ".$_GET['CODE']);
	echo $load_form->render( $result[0] );
}

?>
</div>
</div>
	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			var load_code = <?php echo (isset($_GET['CODE']) ? $_GET['CODE'] : 0); ?>;
			var is_carrier = <?php echo $is_carrier ? 'true' : 'false'; ?>;

			function IsNumeric(input) {
			    return (input - 0) == input && (''+input).replace(/^\s+|\s+$/g, "").length > 0;
			}
			
			function update_total() {
				console.log( 'update_total: entry, base= ', $('#CARRIER_BASE').val(), ' fsc= ', $('#CARRIER_FSC').val(), ' lumper_total= ', $('#LUMPER_TOTAL').text() );
				var total_amt = 0;
				if( IsNumeric($('#CARRIER_BASE').val()) )
					total_amt += parseFloat($('#CARRIER_BASE').val());
				if( IsNumeric($('#CARRIER_FSC').val()) )
					total_amt += parseFloat($('#CARRIER_FSC').val());
					
				if( $('#LUMPER').text() == 'No choices' ||
					! (IsNumeric($('select#LUMPER').val()) && $('select#LUMPER').val() > 0) ) {
					if( IsNumeric($('#LUMPER_TOTAL').text()) )
						total_amt += parseFloat($('#LUMPER_TOTAL').text());
				}
		
				$('#CARRIER_TOTAL_STATIC').text( total_amt );
				$('#CURRENCYT').text($('#CURRENCY').val());
			}
			
			if( is_carrier ) {

				function update_lumper_total() {
					console.log( 'update_lumper_total: entry, handling= ', $('#CARRIER_HANDLING').val(), ' tax= ', $('#LUMPER_TAX').val() );
					var handling = 0;
					if( IsNumeric($('#CARRIER_HANDLING').val()) )
						handling = parseFloat($('#CARRIER_HANDLING').val());
					
					var lt = handling + parseFloat($('#LUMPER_TAX').val());
					$('#LUMPER_TOTAL').text(lt.toFixed(2));
					update_total();
				}
				
				function update_tax() {
					console.log( 'update_tax: entry lumper = ', $('#LUMPER').val() );
					
					//console.log( 'before exp_get_lumper_tax.php: exempt = ', $('#CDN_TAX_EXEMPT').prop("checked") );
					$.ajax({
						url: 'exp_get_lumper_tax.php',
						data: {
							pw: 'Warranty',
							code: load_code,
							lumper: $('select#LUMPER').val(),
							currency: $('#CURRENCY').val(),
							exempt: $('#CDN_TAX_EXEMPT').prop("checked")
						},
						dataType: "json",
						success: function(data) {
							//console.log( 'exp_get_lumper_tax.php success, return ', data);

							if( data.exempt != false && ! $('#CDN_TAX_EXEMPT').prop("checked") )
								$('#CDN_TAX_EXEMPT').prop("checked", true).change();
							if( $('#CDN_TAX_EXEMPT').prop("checked") ) {
								$('.cdn-tax').prop('hidden', 'hidden');
								$('#LUMPER_TAX').val(0);
							} else {
								$('.cdn-tax').prop('hidden', false);

								if( data.rates != false )
									$('#CDN_TAX').text(data.rates[0].province+' '+data.rates[0].tax+' ('+data.rates[0].rate+'%)');
								
							}
							
							//console.log(data.LUMPER_CURRENCY);
							if(typeof(data.LUMPER_CURRENCY) != "undefined" && data.LUMPER_CURRENCY !== null) {
								//console.log('set LUMPER_CURRENCY to ', data.LUMPER_CURRENCY );

								$('#LUMPER_CURRENCY').val(data.LUMPER_CURRENCY).change();
							}
								
							var lt;
							if( data.rates != false ) {
								var handling = 0;
								if( IsNumeric($('#CARRIER_HANDLING').val()) )
									handling = parseFloat($('#CARRIER_HANDLING').val());
								lt = Math.round(handling * data.rates[0].rate )/ 100.0;
							} else {
								lt = 0;
							}
							$('#LUMPER_TAX').val(lt.toFixed(2));
								
							if( data.issue.length > 0 )
								data.issue = '<div class="alert alert-danger" style="padding-top: 0px;" role="alert"><h4 class="text-danger"><span class="glyphicon glyphicon-warning-sign"></span> ' + data.issue + '</h4><h4>Fix this or tax will be missing on the bill to the lumper.</h4></div>';
							$('#LUMPER_ISSUE_STATIC').html(data.issue);
							
							update_lumper_total();

						}
					});
					
				}
				
				function update_lumper_amount() {
					console.log( 'update_lumper amount: entry lumper = ', $('#LUMPER').val() );
					if( IsNumeric($('select#LUMPER').val()) && $('select#LUMPER').val() > 0 ) {
						if( $('#CONS_COUNTRY').val() == 'Canada' ) {
							update_tax();
						}
					}
					update_lumper_total();
				}
				
				function update_lumper_currency() {
			    	$('#LUMPER_CURRENCY_STATIC').text($('#LUMPER_CURRENCY').val()).change();
				}
				
				function update_lumper() {
					console.log( 'update_lumper: entry lumper = ', $('#LUMPER').val() );
					if( $('#LUMPER').text() == 'No choices' ) {
						$('.nolumper').prop('hidden', 'hidden');
						$('#CDN_TAX_EXEMPT').prop("checked", true).change();
						$('#LUMPER_TAX').val(0).change();
					} else {
						$('.nolumper').prop('hidden', false);
					}
					if( IsNumeric($('select#LUMPER').val()) && $('select#LUMPER').val() > 0 ) {
						console.log( 'Got lumper ', $('select#LUMPER').val(), ' country= ', $('#CONS_COUNTRY').val(), ' previous_lumper= ', $( "body" ).data('previous_lumper') );
						$('.lumper').prop('hidden', false);
						if( $( "body" ).data('previous_lumper') != $('select#LUMPER').val() ) {
							$.ajax({
								url: 'exp_get_lumper_tax.php',
								data: {
									pw: 'Warranty',
									code: load_code,
									lumper: $('select#LUMPER').val(),
								},
								dataType: "json",
								success: function(data) {
									if(typeof(data.LUMPER_CURRENCY) != "undefined" && data.LUMPER_CURRENCY !== null) {
		
										$('#LUMPER_CURRENCY').val(data.LUMPER_CURRENCY).change();
									}
								}
							});
						}
						
						if( $('#CONS_COUNTRY').val() == 'Canada' ) {
							if( $( "body" ).data('previous_lumper') != $('select#LUMPER').val() ) {
								$('#CDN_TAX_EXEMPT').prop("checked", false).change();
							}
							$('.cdn-tax').prop('hidden', false);
							$('.cdn-tax2').prop('hidden', false);
							update_tax();
						}
					} else { // no lumper, assume tax exempt
						console.log('no lumper: set LUMPER_CURRENCY to ', $('#CURRENCY').val() );
						$('.lumper').prop('hidden', 'hidden');
						if(typeof($('#CURRENCY').val()) != "undefined" && $('#CURRENCY').val() !== null)
							$('#LUMPER_CURRENCY').val($('#CURRENCY').val()).change();
						else { // multi currency disabled
							$('#LUMPER_CURRENCY').val('USD').change();
							$('#LUMPER_CURRENCY_STATIC').prop('hidden', 'hidden');
						}
						$('.cdn-tax').prop('hidden', 'hidden');
						$('.cdn-tax2').prop('hidden', 'hidden');
						$('#CDN_TAX_EXEMPT').prop("checked", true);
						$('#LUMPER_TAX').val(0).change();
					}
					
					update_lumper_total();
					$( "body" ).data('previous_lumper', $('select#LUMPER').val());

				}
				
				if( $('#CONS_COUNTRY').val() == 'Canada' ) {
			
				    $('#CDN_TAX_EXEMPT').on('change', function() {
				    	update_tax();
				    });
			    } else {
				    // Not Canada
					$('.cdn-tax').prop('hidden', 'hidden');
					$('.cdn-tax2').prop('hidden', 'hidden');
					$('#CDN_TAX_EXEMPT').prop("checked", true);
			    }
			    
				$('#LUMPER_CURRENCY').on('change', function() {
			    	update_lumper_currency()
				});
				    				    
			    $('#CARRIER_HANDLING').keyup(function() {
			    	update_lumper_amount();
			    });
			    
			    $('select#LUMPER').on('change', function() {
			    	update_lumper();
			    });
				
				$( "body" ).data('previous_lumper', $('select#LUMPER').val());
				
				update_lumper();
				update_lumper_currency();
		    
			    $('#CARRIER_BASE, #CARRIER_FSC').keyup(function() {
			    	update_total();
			    });
			    
			    window.edit_carrier_amounts_HAS_CHANGED = false;
		    }

		});
		
	//--></script>
<?php

require_once( "include/footer_inc.php" );
?>

