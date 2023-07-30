<?php

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

if( ! defined('EXP_RELATIVE_PATH') )
	define('EXP_RELATIVE_PATH',
		dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR );

if ( ! defined('_STS_SESSION_AJAX') ) {
	$formatted = urlencode(date('Y-m-d H:i:s', $_SESSION['LAST_ACTIVITY']));
	
	if( ! defined('_STS_SESSION_READONLY') &&
		session_status() == PHP_SESSION_ACTIVE &&
		false === session_write_close()) {
		//! Very BAD! - the session file could not be written
		$my_session->log_event( "Very BAD! session_write_close failed!\n".
		print_r(error_get_last(), true)."\n".
		print_r(session_status(), true)."\n".
		print_r($_SESSION, true), EXT_ERROR_ERROR );
	}
}
?>
	<div class="modal fade fuzzy bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="session_warning">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header" id="session_warning_header">
					<h4 class="modal-title" id="myModalLabel"><span class="text-success"><strong><span class="glyphicon glyphicon-hourglass"></span> Session Inactivity</strong></span></h4>
				</div>
				<div class="modal-body" id="session_warning_body">
					<h4 class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125"> Your session will expire soon due to inactivity.</h4>
				</div>
				<div class="modal-footer">
					<a class="btn btn-success" id="session_refresh" href="<?php echo $_SERVER["REQUEST_URI"]; ?>"><span class="glyphicon glyphicon-refresh"></span> Reload This Page</a>
					<a class="btn btn-danger" id="session_logout" href="<?php echo EXP_RELATIVE_PATH; ?>exp_logout.php"><span class="glyphicon glyphicon-arrow-right"></span> Log Out</a>
				</div>
			</div>
		</div>
	</div>

	<script language="JavaScript" type="text/javascript"><!--
		$(document).ready( function () {
			
			// These are for numeric types
			// none = integer
			// allowneg = allow negative
			// negfloat = allow negative or float
					
			$('input[type=number]:not([allowneg],[negfloat])').numericInput({ allowFloat: true });
			$('input[type=number][allowneg]').numericInput({ allowFloat: true, allowNegative: true });
			$('input[type=number][negfloat]').numericInput({ allowFloat: true, allowNegative: true, allowFloat: true });
			
			<?php if ( ! defined('_STS_SESSION_AJAX') ) { ?>
			if (/PhantomJS/.test(window.navigator.userAgent)) {
				console.log("footer_inc.php: PhantomJS environment detected.");
			} else {
				
				// 5/21/2020 - attempt to avoid session timeout call every 10 min
				var refreshTime = 600000; // every 10 minutes in milliseconds
				window.setInterval( function() {
				    $.ajax({
				        cache: false,
				        type: "GET",
				        url: "exp_refresh_session.php",
				        success: function(data) {
				        }
				    });
				}, refreshTime );
								
				setTimeout( function() {
					$('#session_warning').modal({
						container: 'body'
					});
				}, <?php echo $sts_activity_timeout / 2 * 1000; ?> );
				
				setTimeout(function() {
					if (document.visibilityState == "visible") {
						window.location = "exp_logout.php?EXPIRED=<?php echo $formatted; ?>";
					} else {
						$('#session_warning_body').html('<h2 class="text-center text-danger"><span class="glyphicon glyphicon-warning-sign"></span> Your session has EXPIRED because of inactivity. But since your window was not visible, Exspeedite did not log you out. This is to protect you if you had multiple tabs/windows open.</h2>');

					}
				}, <?php echo ($sts_activity_timeout + 1) * 1000; ?>);
				
			}
			<?php } ?>

			$('#incfont').click(function(){  
				console.log('incfont'); 
				curSize= parseInt($('body').css('font-size')) + 2;
				if(curSize<=20)
					$('body').css('font-size', curSize);
			}); 
			$('#decfont').click(function(){   
				console.log('decfont'); 
				curSize= parseInt($('body').css('font-size')) - 2;
				if(curSize>=8)
					$('body').css('font-size', curSize);
			}); 

			$('.tip').tooltip({container: 'body'});
			
			$('.tip-bottom').tooltip({
				title: 'Exspeedite Help',
				html: 'true',
				placement: 'bottom',
				container: 'body',
				delay: { show: 500, hide: 500 },

			});
			
			$('.my-switch').bootstrapToggle({
				on: 'on',
				off: 'off',
				onstyle: 'success'
			});
			
			var iOS = false,
		    p = navigator.platform.substr(0, 4);
			if( p === 'iPad' || p === 'iPho' || p === 'iPod' ||
				(p === 'MacI' && is_touch_device()) ){
			    iOS = true;
			}
			
			var chrome = /chrom(e|ium)/.test(navigator.userAgent.toLowerCase());
			//$('#PLATFORM').html(navigator.platform);
			
			if( ! iOS ) {
				// Chrome breaks this, clearing out the field
				//$('input[type="date"]').addClass('date').attr('type','text');
				$('.date:not([readonly])').datetimepicker({
			      //language: 'en',
			      format: 'MM/DD/YYYY',
			      //autoclose: true,
			      //pickTime: false
			    }).attr('type','text');
			    /*
				$('input[type=time]').datetimepicker({
			      //language: 'en',
			      format: 'HH:MM',
			      autoclose: true,
			      pickDate: false
			    });
			    */
				$('.timestamp:not([readonly])').datetimepicker({
			      //language: 'en',
			      format: 'MM/DD/YYYY HH:mm',
			      //autoclose: true,
			      //pick12HourFormat: false
			    }).attr('type','text');
			}

			$(".monthpicker").datetimepicker( {
				format: "MM/YYYY",
				viewMode: 'years'
			});
	
			$('.inform').popover({ 
				placement: 'top',
				html: 'true',
				container: 'body',
				trigger: 'hover',
				delay: { show: 50, hide: 1000 },
				title: '<strong>Information</strong> <button type="button" class="close" data-hide="confirm" data-delay="0" aria-hidden="true">&times;</button>' 
			});
			
			$('.informr').popover({ 
				placement: 'right',
				html: 'true',
				container: 'body',
				trigger: 'hover',
				delay: { show: 50, hide: 1000 },
				title: '<strong>Information</strong> <button type="button" class="close" data-hide="confirm" data-delay="0" aria-hidden="true">&times;</button>' 
			});
			
			$('.confirm').popover({ 
				placement: 'top',
				html: 'true',
				container: 'body',
				trigger: 'hover',
				delay: { show: 50, hide: 3000 },
				title: '<strong>Confirm Action</strong> <button type="button" class="close" data-hide="confirm" data-delay="0" aria-hidden="true">&times;</button>' 
			});
			
			// Close other confirm or inform popups when you enter a new one.
			/*
			var last_popover = 'NONE';
			$('.confirm, .inform').on('mouseover', function (e) {
				if(last_popover <> 'NONE' && last_popover <> $(this).attr('id'))
			    $('.inform' + ' #' + last_popover).popover('hide');
			    last_popover = $(this).attr('id');
			});
			*/


			$('body').on('click', function (e) {
			    $('[data-toggle="popover"]').each(function () {
			        //the 'is' for buttons that trigger popups
			        //the 'has' for icons within a button that triggers a popup
			        if (!$(this).is(e.target) && $(this).has(e.target).length === 0 && $('.confirm, .inform').has(e.target).length === 0) {
			            $(this).popover('hide');
			        }
			    });
			}); 

			//$('.confirm').popover().click(function () {
			//    setTimeout(function () {
			//        $('.confirm').popover('hide');
			//    }, 5000);
			//});	

			$('#loading-btn').button();
			
			$('#loading-btn').click(function () {
				$(this).button('loading');
	        });
	
			// Every time a modal is shown, if it has an autofocus element, focus on it.
			$('.modal').on('shown.bs.modal', function() {
			  $(this).find('[autofocus]').focus();
			});	

 
		});
		
// This function is used to change lower to upper case for the Input text
function cUpper(cObj)
{
cObj.value=cObj.value.toUpperCase();
}

// This displays a confirmation before going to another page
// New version using bootbox
function confirmation(message, url, inplace) {
	if( inplace === undefined ) {
		inplace = false;
	}
	message = message.replace("\n", "<br>");
	bootbox.dialog({
		title: '<h3><img src="images/EXSPEEDITEsmr.png" height="32" alt="<?php echo $sts_title ?>"> Confirm Action</h3>',
		message: message,
		closeButton: false,
		buttons: {
			confirm: {
				label: '<span class="glyphicon glyphicon-ok"></span> Confirm',
				className: 'btn-danger',
				callback: function(result) {
					if( inplace === false ) {
						window.location = url;
					} else {
						$.get(url);
					}
				}
			},
			cancel: {
				label: '<span class="glyphicon glyphicon-remove"></span> Cancel',
				className: 'btn-default'
			}
		}
	});
}

function sadness( message, inplace ) {
	if( inplace === undefined ) {
		inplace = false;
	}
	message = message.replace("\n", "<br>");
	bootbox.dialog({
		title: '<h3><img src="images/EXSPEEDITEsmr.png" height="32" alt="<?php echo $sts_title ?>"> Move Not Possible</h3>',
		message: message,
		closeButton: false,
		buttons: {
			cancel: {
				label: '<span class="glyphicon glyphicon-remove"></span> Understood',
				className: 'btn-default'
			}
		}
	});
}

	//--></script>
  </body>
</html>
