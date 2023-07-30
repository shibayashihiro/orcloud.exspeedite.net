<?php

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "Parse EDI";
require_once( "include/header_inc.php" );

require_once( "include/sts_edi_parser_class.php" );
require_once( "include/sts_edi_map_ruan_class.php" );
require_once( "include/sts_edi_map_penske_class.php" );
require_once( "include/sts_ftp_class.php" );

$sts_debug = isset($_GET['debug']) || isset($_POST['debug']);

//! Start

if( ! isset($_POST["EDI"])) {
?>
<div class="container-full theme-showcase" role="main">

	<form class="form-horizontal" role="form" action="exp_edi_parser.php" method="post" enctype="multipart/form-data" name="EDI_IN" id="EDI_IN">
		<div class="form-group">
			<?php if( $sts_debug ) { ?>
			<input name="debug" type="hidden" value="true">	
			<?php } ?>
			<label for="EDI" class="control-label">Paste your EDI in here</label>				
			<textarea class="form-control" name="EDI" id="EDI"  
			placeholder="put 204 here" maxlength="16777215" cols="80" rows="15"></textarea>
			
			<div class="checkbox">
			<label>
				<input type="checkbox" id="IMPORT" name="IMPORT"> Import to Exspeedite
			</label>
			</div>
			<button name="save" type="submit" class="btn btn-danger">Parse</button>
		</div>
	</form>
</div>

<?php	
} else {

	echo '<div class="container-full theme-showcase" role="main">';
	
	$edi = sts_edi_parser::getInstance($exspeedite_db, $sts_debug);
	
	$edi->tokenize( $_POST["EDI"] );	// Convert the text file to tokens
	
	$edi_parsed = $edi->parse_edi();		// Parse the tokens
	
	if( $edi_parsed ) {
	
		//echo "<pre>".$edi->dump_edi( $edi_parsed, false )."</pre>";
		echo $edi->dump_edi( $edi_parsed );
		
		if( isset($_POST["IMPORT"]) && $_POST["IMPORT"] == 'on' ) {
			$ftp = sts_ftp::getInstance($exspeedite_db, $sts_debug);

			list($type, $sid) = $edi->edi_get_type_sid( $edi_parsed );
	
			$notdef = $edi->lookup_edi_path( $edi_parsed, 'Not Defined');
			$client = $edi->lookup_edi_path( $edi_parsed, 'Not Defined/ISA/ISA06' );

			if( isset($client) && $client <> '' &&
				isset($type) && $type == '204' ) {

				//! Select which mapping class to use.
				switch( $ftp->edi_format( $client ) ) {
					case EDI_MAPPING_RUAN:
						$map = sts_edi_map_ruan::getInstance($exspeedite_db, $sts_debug);
						break;
					
					case EDI_MAPPING_PENSKE:
						$map = sts_edi_map_penske::getInstance($exspeedite_db, $sts_debug);
						break;
				
					default:
						$map = false;
				}
			
				$changes = array( 'DIRECTION' => 'in',
					'EDI_TIME' => date("Y-m-d H:i:s"), 'CONTENT' => $_POST["EDI"],
					'EDI_CLIENT' => $client, 'FILENAME' => 'none',
					'COMMENTS' => 'Imported via Inject',
					'EDI_TYPE' => $type, 'IDENTIFIER' => $sid );
				$purpose = $edi->lookup_edi_path( $edi_parsed, '204/Heading/B2A/B2A01' );
				if( $purpose ) 
					$changes["B2A01_PURPOSE"] = $edi->get_field_value( "B2A01", $purpose );

				if( $map ) {
					// If multiple 204s in an X12, we get multiple entries
					try {
						$save = $map->add($changes);
						$edi_204s = $map->lookup_edi_path( $edi_parsed, '204', EXPECT_MULTIPLE );
						if( is_array($edi_204s) && ! empty($edi_204s) ) {
							foreach($edi_204s as $edi_204) {
								$result = $map->map_204( $client, $notdef, $edi_204 );
								if( $result ) {
									$map->update($save,
										array('EDI_204_PRIMARY' => $result));
									echo "<br><h2>204 Has Been Imported -> $result</h2>";
								}
							}
						}
					} catch (Exception $e) {
					    echo "<br><h2>Error: ",  $e->getMessage(), "</h2>";
					    $map->log_event( "exp_edi_parser: Caught exception: ".$e->getMessage(), EXT_ERROR_ERROR);
					}

				} else {
					echo "<br><h2>Not recognized client ($client)/format</h2>";
				}
			} else
				echo "<br><h2>Not a 204 or client not found</h2>";
		}
				
	} else
		echo "<p>".$edi->getMessage()."</p>";
		
	echo '<p><a href="exp_edi_parser.php">Back</a></p>
	</div>';
	
}


	
	
?>