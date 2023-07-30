<?php

// $Id: exp_check_call.php 4350 2021-03-02 19:14:52Z duncan $
//! SCR# 298 - For Check call, using modal dialog and JQuery Location Picker
//! SCR# 302 - include office number

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

$path = explode('/', $_SERVER["SCRIPT_NAME"]);
$return = end($path).(empty($_SERVER["QUERY_STRING"]) ? '' : '?'.$_SERVER["QUERY_STRING"]);

?>
    <style>
        .pac-container {
		    z-index: 1060;
		}​
    </style>

	<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="checkcall_modal">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
		            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title" id="myModalLabel"><span class="text-success"><strong>Check Call For Load <span id="ck-load-title"></span><span id="ck-office"></span></strong></span></h4>
				</div>
				<div class="modal-body">
                    <form role="form" class="form-horizontal" action="exp_add_check_call.php" 
						method="post" enctype="multipart/form-data" 
							name="ck-form" id="ck-form">
	                    <input type="hidden" name="ck-pw" id="ck-pw" value="QwertyuioP">
	                    <input type="hidden" name="ck-return" id="ck-return" value="<?php echo $return; ?>">
 		                <input type="hidden" class="form-control text-right" name="ck-load" id="ck-load">
                       <div class="form-group">
	                        <div class="col-sm-6">
		                        <div class="form-group">
		                            <div class="col-sm-12">
		                                <p class="form-control-static">Drag the pointer in the map to a location or type in a location below, and select from the drop down menu. Click the <img src="images/expand.png" alt="expand" width="25" height="25" /> icon in the map to get full screen.</p>
		                            </div>
		                        </div>
		                        <div class="form-group">
		                            <label class="col-sm-3 control-label">Location:</label>
		                            <div class="col-sm-9">
		                                <input type="text" class="form-control" name="ck-address" id="ck-address" >
		                            </div>
		                        </div>
		                        <div class="form-group">
		                            <label class="col-sm-3 control-label">Note:</label>
		                            <div class="col-sm-9">
		                                <input type="text" class="form-control" name="ck-note" id="ck-note" >
		                            </div>
		                        </div>
		                        <div class="form-group">
		                            <label class="col-sm-3 control-label">Coords:</label>
		                            <div class="col-sm-4">
		                                <input type="text" class="form-control text-right" name="ck-lat" id="ck-lat" readonly>
		                            </div>		
		                            <div class="col-sm-4">
		                                <input type="text" class="form-control text-right" name="ck-lon" id="ck-lon" readonly>
		                            </div>
		                        </div>
		                        <br>
		                        <hr>
			                    <div class="form-group">
			                        <div class="col-sm-offset-3 col-sm-3">
										<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			                        </div>
			                        <div class="col-sm-3">
						                <button type="submit" name="save" id="save" class="btn btn-primary">Save changes</button>
			                        </div>
			                    </div>
                            </div>
	                        <div class="col-sm-6">
								<div id="ck" style="width: 100%; height: 400px;"></div>
                             </div>
                        </div>
                        <div class="clearfix"></div>
						<script language="JavaScript" type="text/javascript"><!--
							
							$(document).ready( function () {
	                            $('#checkcall_modal').on('shown.bs.modal', function () {
		                            $('#ck').locationpicker({
		                                location: {
		                                    latitude: 45.4894008,
		                                    longitude: -93.2476091
		                                },
		                                radius: 300,
		                                inputBinding: {
		                                    latitudeInput: $('#ck-lat'),
		                                    longitudeInput: $('#ck-lon'),
		                                    locationNameInput: $('#ck-address')
		                                },
		                                enableAutocomplete: true
		                            });
	                            
		                            $('#ck-load-title').text($('#ck-load').val());
	                                $('#ck').locationpicker('autosize');
	                                $.ajax({
		                                url: 'exp_recent_coords.php',
										data: {
											PW: 'Soya42',
											CODE: $('#ck-load').val()
										},
										dataType: "json",
										success: function(data) {
											//console.log(data.LAT, data.LON);
										
			                                $('#ck').locationpicker(
				                                'location', {
				                                    latitude: data.LAT,
				                                    longitude: data.LON
				                                }
			                                );
			                            }
			                        });
			                        
			                        $('.modal-dialog').draggable({
										handle: ".modal-header"
									});
	                            });
                            });
						//--></script>
                    </form>
				</div>
			</div>
		</div>
	</div>

