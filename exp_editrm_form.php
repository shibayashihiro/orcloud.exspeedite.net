<?php 

// $Id: exp_editrm_form.php 4350 2021-03-02 19:14:52Z duncan $
// Edit RM class - Edit class for tractors/trailers

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );
define( '_STS_EDITOR', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "Edit R&M Form";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_rm_form_class.php" );

if( isset($_POST) && isset($_POST['FORM_CODE']) )
	$_GET['CODE'] = $_POST['FORM_CODE'];

$rm_form_table = sts_rm_form::getInstance($exspeedite_db, $sts_debug);

$sts_form_edit_rm_form_form = $rm_form_table->class_checkboxes( $sts_form_edit_rm_form_form, $_GET['CODE'] );

$rm_form_form = new sts_form($sts_form_edit_rm_form_form, $sts_form_edit_rm_form_fields, $rm_form_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $rm_form_form->process_edit_form();

	if( $result ) {
		$rm_form_table->process_class_checkboxes($_POST['FORM_CODE']);

		if( $sts_debug ) die; // So we can see the results
		reload_page ( "exp_listrm_form.php" );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$rm_form_table->error()."</p>";
	echo $rm_form_form->render( $value );
} else if( isset($_GET['CODE']) ) {
	$result = $rm_form_table->fetch_rows($rm_form_table->primary_key." = ".$_GET['CODE']);
	echo $rm_form_form->render( $result[0] );
}

if( isset($_GET['CODE']) ) {

require_once( "include/sts_result_class.php" );
require_once( "include/sts_insp_list_item_class.php" );

$insp_list_item_table = sts_insp_list_item::getInstance($exspeedite_db, $sts_debug);

$match = "RM_FORM = ".$_GET['CODE'];
$sts_result_insp_list_item_edit['add'] .= "?RM_FORM=".$_GET['CODE'];

$rslt = new sts_result( $insp_list_item_table, $match, $sts_debug );
echo $rslt->render( $sts_result_insp_list_item_layout, $sts_result_insp_list_item_edit, false, false );


} ?>
</div>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		var editor;
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");
			<?php if( ! $sts_debug ) { ?>
			//document.documentElement.style.overflow = 'hidden';  // firefox, chrome
			//document.body.scroll = "no"; // ie only
			<?php } ?>

			$('#checkall').click(function () {
				$('.classes').each(function() {
					$(this).prop('checked', true);
				});
			});
			
			$('#checknone').click(function () {
				$('.classes').each(function() {
					$(this).prop('checked', false);
				});
			});
			

			editor = new $.fn.dataTable.Editor( {
				"ajax": {
					"url": "exp_listinsp_list_itemajax.php",
					"data": function( d ) {
						d.match = encodeURIComponent("<?php echo $match; ?>");
					}
				},
			    table: '#EXP_INSP_LIST_ITEMS',
				"idSrc": 'DT_RowAttr.ITEM_CODE',
		        fields: [ 
		        	<?php foreach($sts_result_insp_list_item_layout as $key => $row) {
			        	if( isset($row['label']) && $row["format"] <> 'hidden' )
			        		echo "{ label: '".$row['label']."', name: '".$key."' },\n";
		        	}
		        	?>
		        ]
			});


			var table = $('#EXP_INSP_LIST_ITEMS').DataTable({
				//"dom": "Bfrtip",
        		//"bLengthChange": false,
        		stateSave: true,
		        "bFilter": true,
		        "bSort": true,
		        "bInfo": true,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - 280) + "px",
				//"sScrollXInner": "120%",
		        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				"bPaginate": true,
				"bScrollCollapse": false,
				//"bSortClasses": false,
				"order": [[ 1, "asc" ]],
				//"processing": true,
				"serverSide": true,
				//"deferRender": true,
				"ajax": {
					"url": "exp_listinsp_list_itemajax.php",
					"data": function( d ) {
						d.match = encodeURIComponent("<?php echo $match; ?>");
					}
				},
				"columns": [
					{ "searchable": false, "orderable": false },
					<?php
						foreach( $sts_result_insp_list_item_layout as $key => $row ) {
							if( $row["format"] <> 'hidden') {
								$classes = (isset($row["align"]) ? 'text-'.$row["align"] : '').
									(isset($row["orderable"]) && $row["orderable"] ? ' reorder' : '');
								
								echo '{ data: "'.$key.'", searchable: '.
								(isset($row["searchable"]) && ! $row["searchable"] ? 'false' : 'true' ).
								(empty($classes) ? '' : ", className: '".$classes."'").
								($key == 'ITEM_CODE7' ? ', visible: false,' : '' ).
									(isset($row["length"]) ? ', width: "'.$row["length"].'px"' : '').' },
						';
							}
						}
					?>
				],
		        columnDefs: [
		            { orderable: false, targets: [ 0,2,3,4,5,6 ] }
		        ],
				
		        rowReorder: {
		            dataSrc: 'SEQUENCE_NO',
		            editor:  editor,
		            selector: 'td.reorder',
		            update: false
		        },
		        rowId: 'DT_RowAttr.ITEM_CODE',
				//select: true,
			});

			table.on( 'pre-row-reorder', function ( node, index ) {
			    console.log( 'Row reorder started: ' );
			} );			

			table.on( 'row-reorder', function ( e, diff, edit ) {
			    console.log( 'Reorder started on row: ', edit.triggerRow.data().DT_RowAttr.ITEM_CODE );
				for ( var i=0, ien=diff.length ; i<ien ; i++ ) {
					var rowData = table.row( diff[i].node ).data();
					//console.log('rowData = ', rowData );
					
					console.log( rowData.DT_RowAttr.ITEM_CODE+' updated to be in position '+
					diff[i].newData+' (was '+diff[i].oldData+')');
				}

			} );			

			editor
		        .on( 'postCreate postRemove', function () {
		            // After create or edit, a number of other rows might have been affected -
		            // so we need to reload the table, keeping the paging in the current position
		            console.log('postCreate postRemove');
		            table.ajax.reload( null, false );
		        } );
			
			if( window.HANDLE_RESIZE_EVENTS ) {
				$(window).bind('resize', function(e) {
					console.log('resize event triggered');
					if (window.RT) clearTimeout(window.RT);
					window.RT = setTimeout(function() {
						this.location.reload(false); /* false to get page from cache */
					}, 100);
				});
			}
			
		});
	//--></script>


<?php

require_once( "include/footer_inc.php" );
?>

