<?php

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once( "include/sts_config.php" );
require_once( "include/sts_edi_class.php" );
require_once( "include/sts_ftp_class.php" );

$sts_debug = isset($_GET['debug']);

$tmp = "ISA*00*          *00*          *02*RUAN           *02*CSPH           *151203*1629*U*00401*000000001*0*T*>~GS*SM*RUAN*CSPH*20151203*1629*1*X*004010~ST*204*0001~B2**CSPH**R1062788**PP~B2A*00*LT~L11*R1062788*SI~L11*1004682-1000-0420-120-GVU-093*BM~G62*64*20160201*1*1600*LT~PLD*1~NTE*INT*1004682-1000-0420-120-GVU-093 - 1004682-1000-0420-120-GVU-092 - ~NTE*INT*1004682-1000-0420-120-GVU-091 - 1004682-1000-0420-120-GVU-090 - ~NTE*INT*1004682-1000-0420-120-GVU-089 - 1004682-1000-0420-120-GVU-088 - ~NTE*INT*1004682-1000-0420-120-GVU-087 - 1004682-1000-0420-120-GVU-086 - ~NTE*INT*1004682-1000-0420-120-GVU-085 - 1004682-1000-0420-120-GVU-084 - ~NTE*INT*1004682-1000-0420-120-GVU-083 - 1004682-1000-0420-120-GVU-082 - ~NTE*INT*1004682-1000-0420-120-GVU-081 - MUST SIGN LOG BOOK!!! If delivering to Hoffman ~NTE*INT*Enclosures in Anoka, bring all to 1000 North St.~N1*PF*LOGISTICS BILLING RUAN TRANSPORTATION*93*T105~N3*PO BOX 9319~N4*DES MOINES*IA*50309*USA~G61*BJ*GILBERT URIAS*TE*515-245-2458~N7**119009*********TV****5300~S5*1*LD~G62*10*20151204*Y*0900*LT~AT8*G*L*200*1~PLD*1~N1*SF*UPS CUSTOMER CENTER*25*UPSEAG~N3*555 Opperman Drive~N4*EAGAN*MN*55120*USA~G61*IC*UPS CUSTOMER CENTER*TE*555-555-55555~L5**GAUGE,LCD-CAN (013 425 CR ACE)*0420-120*Z~OID*1004682-1000-0420-120-GVU-093***EA*1~S5*2*UL~G62*68*20151204*Z*0931*LT~AT8*G*L*200*1~PLD*1~N1*ST*RCDC*25*RCDBRO~N3*7101 WINNETKA AVE N~N4*BROOKLYN PARK*MN*55428*USA~G61*IC*AARON SCHROEDER*TE*555-555-5555~L5**GAUGE,LCD-CAN (013 425 CR ACE)*0420-120*Z~OID*1004682-1000-0420-120-GVU-093***EA*1~L3*200*G*550*PS*******1*L~SE*42*0001~GE*1*1~IEA*1*000000001~";

//! Start

if( isset($_GET["TRYIT"]) ) {
	$edi = sts_edi::getInstance($exspeedite_db, $sts_debug);
	$edi->accept_204( 8631 );
} else
if( isset($_GET["PW"]) && $_GET["PW"]=='angry' ) {

	$edi = sts_edi::getInstance($exspeedite_db, $sts_debug);
	
	$count = $edi->import_204s( FTP_CLIENT_STS );	// Simplified interface
	echo "<p>done count=$count</p>";
	
	/*
	$ftp = sts_ftp::getInstance($exspeedite_db, $sts_debug);
	
	$contents = $ftp->ftp_connect(FTP_CLIENT_STS);	// or FTP_CLIENT_RUAN
	
	//$ftp->ftp_put_contents ("fuzzy.edi", $tmp);
	if( isset($_GET["SEE990"]) ) {
		echo "<p>990 Example:</p>";
		echo "<pre>";
		var_dump($edi->create_ruan_990( "R1062788", 'A', "1234" ));
		echo "</pre>";
	}else if( isset($_GET["SEND990"]) ) {
		// Create a 990, create a file name, and FTP it to client
		$ftp->ftp_put_contents (
			$ftp->create_RUAN_filename(), 
			$edi->create_ruan_990( "R1062788", 'A', "1234" )
		);
	} else {
		if( $contents ) {
			foreach( $contents as $filename ) {
				$edi_raw = $ftp->ftp_get_contents($filename);
				
				$edi->tokenize( $edi_raw );	// Convert the text file to tokens
				
				$edi_204 = $edi->parse_edi();		// Parse the tokens according to 204 schema
				
				if( $edi_204 ) {
					echo $edi->dump_edi( $edi_204 );
					$edi->map_ruan_204( $edi_204 );
				}
			}
		} else
			echo "<p>Error: ".$ftp->last_error."</p>";
	}
	// close the connection
	$ftp->ftp_close();
	*/
} else
	echo "<h1>403 Permission Denied</h1>

<p>You do not have permission for this request ".$_SERVER["REQUEST_URI"]."</p>

<p>Your IP address (".$_SERVER["REMOTE_ADDR"]."), GPS coordinates (N38 53.86205 W77 2.19162)  have been sent to the <strong>FBI Cyber Anti Crime Unit</strong>.</p>
<p>You have 17 minutes...</p>
";

?>