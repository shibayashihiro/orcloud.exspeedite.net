<?php

// This is the base EDI class, not used directly

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');
require_once( "sts_setting_class.php" );

define('EXPECT_MULTIPLE', true);

define('EDI_204', 'SM');
define('EDI_214', 'QM');
define('EDI_824', 'AG');
define('EDI_990', 'GF');

define('ISA11_ID',			'U');
define('ISA12_VERSION',		'00401');
define('GS07_AGENCY',		'X');
define('GS08_VERSION',		'004010');

define('MATCH_NONE',		0);
define('MATCH_EXACT',		1);
define('MATCH_SUBSET',		2);
define('MATCH_SUPERSET',	3);

class sts_edi extends sts_table {
	private $setting_table;

	private $edi_enabled;
	private $edi_log;
	private $edi_diag_level;	// Text
	private $diag_level;		// numeric version
	private $message = "";

	//! ID description
	public $id_description = array(
		'ISA' => 'Interchange Control Header',
		'GS' => 'Functional Group Header',
		'ST' => 'Transaction Set Header',
		'B2' => 'Beginning Segment for Shipment Information Transaction',
		'B2A' => 'Set Purpose',
		'L11' => 'Business Instructions and Reference Number',
		'G62' => 'Date/Time',
		'AT5' => 'Bill of Lading Handling Requirements',
		'PLD' => 'Pallet Information',
		'NTE' => 'Note/Special Instruction',
		'N1' => 'Name',
		'N2' => 'Additional Name Information',
		'N3' => 'Address Information',
		'N4' => 'Geographic Location',
		'G61' => 'Contact',
		'N7' => 'Equipment Details',
		'S5' => 'Stop Off Details',
		'AT8' => 'Shipment Weight, Packaging and Quantity Data',
		'L5' => 'Description, Marks and Numbers',
		'OID' => 'Order Identification Detail',
		'L3' => 'Total Weight and Charges',
		'SE' => 'Transaction Set Trailer',
		'GE' => 'Functional Group Trailer',
		'IEA' => 'Interchange Control Trailer',
		'LH1' => 'Hazardous Identification Information',
		'LH2' => 'Hazardous Classification Information',
		'LH3' => 'Hazardous Material Shipping Name',
		'LFH' => 'Freeform Hazardous Material Information',
		'LAD' => 'Lading Detail',
		'B1' => 'Beginning Segment for Booking or Pick-up/Delivery',
		'N9' => 'Reference Identification',
		'K1' => 'Remarks',

		'B10' => 'Beginning Segment for Shipment Status Message',
		'MS3' => 'Route Information',
		'LX' => 'Assigned Number',
		'AT7' => 'Shipment Status Details',
		'MS1' => 'Equipment Shipment or Real Property Location',
		'MS2' => 'Equipment or Container Owner and Type',
		'MS3' => 'Interline Information',
		'Q7' => 'Lading Exception code',
		
		'BGN' => 'Beginning Segment for Shipment Information Transaction',
		'OTI' => 'Original Transaction Identification',
		'REF' => 'Reference Identification',
		'TED' => 'Technical Error Description',
	);
	
	//! Field description
	public $field_description = array(
		'ISA01' => 'Authorization Information Qualifier',
		'ISA02' => 'Authorization Information',
		'ISA03' => 'Security Information Qualifier',
		'ISA04' => 'Security Information',
		'ISA05' => 'Interchange ID Qualifier',
		'ISA06' => 'Interchange Sender ID',
		'ISA07' => 'Interchange ID Qualifier',
		'ISA08' => 'Interchange Receiver ID',
		'ISA09' => 'Interchange Date',
		'ISA10' => 'Interchange Time',
		'ISA11' => 'Interchange Control Standards Identifier',
		'ISA12' => 'Interchange Control Version Number',
		'ISA13' => 'Interchange Control Number',
		'ISA14' => 'Acknowledgment Requested',
		'ISA15' => 'Usage Indicator',
		'ISA16' => 'Component Element Separator',
		
		'GS01' => 'Functional Identifier Code',
		'GS02' => 'Application Sender\'s Code',
		'GS03' => 'Application Receiver\'s Code',
		'GS04' => 'Date',
		'GS05' => 'Time',
		'GS06' => 'Group Control Number',
		'GS07' => 'Responsible Agency Code',
		'GS08' => 'Version / Release / Industry Identifier Code',
	
		'ST01' => 'Transaction Set Identifier Code',
		'ST02' => 'Transaction Set Control Number',
	
		'B202' => 'Standard Carrier Alpha Code',
		'B204' => 'Shipment Identification Number',
		'B206' => 'Shipment Method of Payment',
	
		'B2A01' => 'Transaction Set Purpose Code',
		'B2A02' => 'Application Type',
	
		'L1101' => 'Reference Identification',
		'L1102' => 'Reference Identification Qualifier',
		'L1103' => 'Description',
		
		'G6201' => 'Date Qualifier',
		'G6202' => 'Date',
		'G6203' => 'Time Qualifier',
		'G6204' => 'Time',
		'G6205' => 'Time Code',
		
		'AT501' => 'Special Handling Code',
		'AT502' => 'Special Services Code',
		'AT503' => 'Special Handling Description',

		'PLD01' => 'Quantity',
		'PLD02' => 'Pallet Exchange Code',
		'PLD03' => 'Weight Unit Code',
		'PLD04' => 'Weight',
		
		'NTE01' => 'Note Reference Code',
		'NTE02' => 'Description',
		
		'N101' => 'Entity Identifier Code',
		'N102' => 'Name',
		'N103' => 'Identification Code Qualifier',
		'N104' => 'Identification Code',
		'N105' => 'Entity Relationship Code',
		'N106' => 'Entity Identifier Code',
		
		'N201' => 'Name',
		'N202' => 'Name',
	
		'N301' => 'Address Information',
		'N302' => 'Address Information',
	
		'N401' => 'City Name',
		'N402' => 'State or Province Code',
		'N403' => 'Postal Code',
		'N404' => 'Country Code',
		'N405' => 'Location Qualifier',
		'N406' => 'Location Identifier',
	
		'G6101' => 'Contact Function Code',
		'G6102' => 'Name',
		'G6103' => 'Communication Number Qualifier',
		'G6104' => 'Communication Number',
		'G6105' => 'Contact Inquiry Reference',
	
		'N702' => 'Equipment Number',
		'N711' => 'Equipment Desc Code',
		'N715' => 'Equipment Length',
		'N722' => 'Equipment Type',
	
		'S501' => 'Stop Sequence Number',
		'S502' => 'CodeList Summary',
		'S503' => 'Weight',
		'S504' => 'Weight Unit Code',
		'S505' => 'Number of Units Shipped',
		'S506' => 'Unit or Basis for Measurement Code',
		'S507' => 'Volume',
		'S508' => 'Volume Unit Qualifier',
		
		'AT801' => 'Weight Qualifier',
		'AT802' => 'Weight Unit Code',
		'AT803' => 'Weight',
		'AT804' => 'Lading Quantity',
		'AT805' => 'Lading Quantity',
		'AT806' => 'Volume Unit Qualifier',
		'AT807' => 'Volume',
		
		'L502' => 'Lading Description',
		'L503' => 'Commodity Code',
		'L504' => 'Commodity Code Qualifier',
		'L505' => 'Packaging Code',
		'L506' => 'Marks and Numbers',
		'L508' => 'Commodity Code Qualifier',
		'L509' => 'Commodity Code Qualifier',
		
		'OID01' => 'Reference Identification',
		'OID02' => 'Purchase Order Number',
		'OID03' => 'Reference Identification',
		'OID04' => 'Unit or Basis for Measurement Code',
		'OID05' => 'Quantity',
		'OID06' => 'Weight Unit Code',
		'OID07' => 'Weight',
		'OID08' => 'Volume Unit Qualifier',
		'OID09' => 'Volume',
		
		'L301' => 'Weight',
		'L302' => 'Weight Qualifier',
		'L303' => 'Freight Rate',
		'L304' => 'Rate/Value Qualifier',
		'L305' => 'Charge',
		'L306' => 'Advances',
		'L307' => 'Prepaid Amount',
		'L308' => 'Special Charge or Allowance Code',
		'L309' => 'Volume',
		'L310' => 'Volume Unit Qualifier',
		'L311' => 'Lading Quantity',
		'L312' => 'Weight Unit Code',
		'L313' => 'Tariff Number',
		'L314' => 'Declared Value',
		'L315' => 'Rate/Value Qualifier',
		
		'SE01' => 'Number of Included Segments',
		'SE02' => 'Transaction Set Control Number',
		
		'GE01' => 'Number of Transaction Sets Included',
		'GE02' => 'Group Control Number',
		
		'IEA01' => 'Number of Included Functional Groups',
		'IEA02' => 'Interchange Control Number',
		
		'LH101' => 'Unit or Basis for Measurement Code',
		'LH102' => 'Lading Quantity',
		'LH103' => 'UN/NA Identification Code',
		'LH104' => 'Hazardous Materials Page',
		'LH105' => 'Commodity Code',
		'LH106' => 'Unit or Basis for Measurement Code',
		'LH107' => 'Quantity',
		'LH108' => 'Compartment ID Code',
		'LH109' => 'Residue Indicator Code',
		'LH110' => 'Packing Group Code',
		'LH111' => 'Interim Hazardous Material Regulatory Number',
	
		'LH201' => 'Hazardous Class',
		
		'LH301' => 'Hazardous Material Shipping Name',
		'LH302' => 'Haz Mat Naming Qualifier',

		'LAD01' => 'Packaging Form Code',
		'LAD02' => 'Lading Quantity',
		'LAD07' => 'Prod/Serv ID Qualifier',
		'LAD08' => 'Prod/Serv ID',
		'LAD09' => 'Prod/Serv ID Qualifier',
		'LAD10' => 'Prod/Serv ID',
		'LAD11' => 'Prod/Serv ID Qualifier',
		'LAD12' => 'Prod/Serv ID',
		'LAD13' => 'Lading Description',

		'B101' => 'Standard Carrier Alpha Code',
		'B102' => 'Shipment Identification Number',
		'B103' => 'Date',
		'B104' => 'Reservation Action Code',

		'N901' => 'Reference Identification Qualifier',
		'N902' => 'Reference Identification',

		'B1001' => 'Reference Identification',
		'B1002' => 'Shipment Identification Number',
		'B1003' => 'Standard Carrier Alpha Code',

		'LX01' => 'Assigned Number',

		'AT701' => 'Shipment Status Code',
		'AT702' => 'Shipment Status Or Appointment Reason Code',
		'AT703' => 'Shipment Appointment Status Code',
		'AT704' => 'Shipment Status Or Appointment Reason Code',
		'AT705' => 'Date',
		'AT706' => 'Time',
		'AT707' => 'Time Code',

		'MS101' => 'City Name',
		'MS102' => 'State Or Province Code',
		'MS103' => 'Country Code',

		'BGN01' => 'Transaction Set Purpose Code',
		'BGN02' => 'Reference Identification',
		'BGN03' => 'Date',
		'BGN04' => 'Time',
		'BGN05' => 'Time Code',
		'BGN06' => 'Reference Identification',
		'BGN07' => 'Transaction Type Code',
		'BGN08' => 'Action Code',
		'BGN09' => 'Security Level Code',

		'OTI01' => 'Application Acknowledgement Code',
		'OTI02' => 'Reference Identification Qualifier',
		'OTI03' => 'Reference Identification',
		'OTI04' => 'Application Sender’s Code',
		'OTI05' => 'Application Receiver’s Code',
		'OTI06' => 'Date',
		'OTI07' => 'Time',
		'OTI08' => 'Group Control Number',
		'OTI09' => 'Transaction Set Control Number',
		'OTI10' => 'Transaction Set Identifier Code',
		'OTI11' => 'Version / Release / Industry Identifier Code',

		'REF01' => 'Reference Identification Qualifier',
		'REF02' => 'Reference Identification',
		'REF03' => 'Description',

		'TED01' => 'Application Error Condition Code',
		'TED02' => 'Free Form Message',
		'TED03' => 'Segment ID Code',
		'TED04' => 'Segment Position in Transaction Set',
		'TED05' => 'Element Position in Segment',
		'TED06' => 'Data Element Reference Number',
		'TED07' => 'Copy of Bad Data Element',
		'TED08' => 'Data Element New Content',
	);
	
	//! Field values
	public $field_value = array(
		'ISA01' => array(
			'00' => 'No Authorization Information Present'
		),
		'ISA03' => array(
			'00' => 'No Security Information Present'
		),
		'ISA05' => array(
			'01' => 'Duns (Dun & Bradstreet)'
		),
		'ISA14' => array(
			'0' => 'No Acknowledgment Requested'
		),
		'ISA15' => array(
			'P' => 'Production Data',
			'T' => 'Test Data'
		),
	
		'GS01' => array(
			EDI_204 => 'Motor Carrier Load Tender (204)',
			EDI_990 => 'Response to a Load Tender (990)',
			EDI_214 => 'Transportation Carrier Shipment Status Message (214)',
			EDI_824 => 'Application Advice (824)',
		),
		'GS07' => array(
			'X' => 'Accredited Standards Committee X12'
		),
	
		'ST01' => array(
			'204' => 'Motor Carrier Load Tender'
		),
		'B206' => array(
			'PP' => 'Prepaid (by seller)',
			'CC' => 'Collect',
			'TP' => 'Third Party',
			'DF' => 'Defined by Buyer & Seller',
		),
		'B2A01' => array(
			'00' => 'Original',
			'01' => 'Cancellation',
			'02' => 'Add',
			'03' => 'Delete',
			'04' => 'Change',
			'05' => 'Replacement',
		),
		'B2A02' => array(
			'LT' => 'Truckload (TL) Carrier Only',
			'PC' => 'Partial Load (LTL), Carrier Consolidate',
		),
		'L1101' => array(
			'IMC' => 'Intermodal',
			'LTL' => 'Less Than Truckload',
			'LTLB' => 'Less Than Truckload Bonded',
			'SPEX' => 'Spot Rate Expedited',
			'SPTL' => 'Spot Rate Truckload',
			'TL' => 'Truckload',
			'TLB' => 'Truckload Bonded',
		),
		'L1102' => array(
			'11' => 'Account Number (Penske must return in 214)',
			'12' => 'Billing Account',
			'2I' => 'Tracking Number ID',
			'4C' => 'Shipment Destination code',
			'4M' => 'Special Move Reference Number',
			'82' => 'Data item description',
			'55' => 'Sequence Number',

			'AO' => 'Appointment Number',
			'BM' => 'Bill of Lading Number',
			'CO' => 'Customer Order Number',
			'CN' => 'Carrier’s Reference Number (PRO/Invoice)',
			'CR' => 'Customer Reference Number',
			'DJ' => 'Delivery Ticket Number',
			'DO' => 'Delivery Order Number',
			'EU' => 'End user Purchase Order Number',
			'F8' => 'Original Reference Number',
			'FSN' => 'Assigned Sequence Number',
			'GZ' => 'General Ledger Account',
			'IA' => 'Internal Vendor Number',
			'IC' => 'Inbound-to Party',
			'IX' => 'Item Number',
			'KK' => 'Order type',
			//'KK' => 'Delivery Reference',
			'MB' => 'Master Bill of Lading',
			'MJ' => 'Model Number',
			'ON' => 'Shipping Customer Order Number',
			'OS' => 'Outbound-from Party',
			'P8' => 'Pickup Reference Number',
			'PO' => 'Purchase Order Number',
			'PU' => 'Pick Up Number',
			//'PU' => 'Previous Bill of Lading Number',
			'QN' => 'Stop Sequence Number',
			'QY' => 'Service Performed Code',
			'RU' => 'Route Number',
			'SI' => 'Shippers Identification Number For Shipment (SID)',
			'ZZ' => 'Mutually Defined (Penske Load)',
		),
		'G6201' => array(
			'09' => 'Process Date',
			'10' => 'Requested Ship Date/Pick-up Date',
			'17' => 'Estimated Delivery Date',
			'37' => 'Ship Not Before Date', 
            '38' => 'Ship Not Later Than Date',
            '53' => 'Deliver Not Before Date',
			'54' => 'Deliver No Later Than Date',
			'64' => 'Must Respond By',
			'67' => 'Delivered By This Date',
			'68' => 'Requested Delivery Date',
			'69' => 'Scheduled Pick-Up Date',
			'70' => 'Scheduled Delivery Date',
			'77' => 'Pickup Appointment Scheduled Date',
			'78' => 'Delivery Appointment Scheduled Date',
			'79' => 'Pickup Requested Scheduled Date',
			'80' => 'Delivery Requested Scheduled Date',
			'81' => 'Pickup Appointment Granted Date',
			'82' => 'Delivery Appointment Granted Date',
			'96' => 'Scheduled Pick-up Date, Needs Confirmation',
			'97' => 'Scheduled Delivery Date, Needs Confirmation',
			'98' => 'Scheduled Pick-up Date, Appointment Confirmed',
			'99' => 'Scheduled Delivery Date, Appointment Confirmed',
			'CA' => 'Cutoff Date',
		),
		'G6203' => array(
			'1' => 'Must Respond By',
			'2' => 'Pickup Appointment Scheduled Time',
			'3' => 'Delivery Appointment Scheduled Time',
			'4' => 'Pickup Requested Scheduled Time',
			'5' => 'Delivery Requested Scheduled Time',
			'6' => 'Pickup Appointment Granted Time',
			'7' => 'Delivery Appointment Granted Time',
			'G' => 'Earliest Requested Deliver Time',
			'I' => 'Earliest Requested Pick Up Time',
			'K' => 'Latest Requested Pick Up Time',
			'L' => 'Latest Requested Delivery Time',
			'U' => 'Scheduled Pick Up Time',
			'X' => 'Scheduled Delivery Time',
			'Y' => 'Requested Ship Date/Pick-up Time',
			'Z' => 'Requested Delivery Time',
		),
		'G6205' => array(
			'AT' => 'Alaska Time',
			'CT' => 'Central Time',
			'ET' => 'Eastern Time',
			'HT' => 'Hawaii-Aleutian Time',
			'MT' => 'Mountain Time',
			'PT' => 'Pacific Time',
			'LT' => 'Local Time',
		),
		'AT501' => array(
			'CC' => 'Container, Consolidated Load',
			'EE' => 'Electronic Equipment Transfer',
			'EX' => 'Explosive Flammable Gas',
			'FR' => 'Fragile - Handle with Care',
			'HM' => 'Endorsed as Hazardous Material',
			'HQT' => 'High Cube Trailer Rates',
			'HZD' => 'Hazardous Cargo On Deck',
			'IMS' => 'Intermodal Shipment Service',
			'KMD' => 'Keep Material Dry',
			'LTT' => 'Less Than Truckload',
			'MRF' => 'Refrigerated',
			'NS' => 'Notify Shipper Before Reconsignment',
			'OTC' => 'Temperature Control',
			'PUP' => 'PUP Trailer Rates Apply',
			'TDC' => 'Truckload-Double Operator-Common Carrier',
			'TSC' => 'Truckload-Single Operator-Common Carrier',
		),
		'AT502' => array(
			'CC' => 'Carrier Unload',
			'CU' => 'Consignee Unload',
			'D0031' => 'Driver Assisted Unloading',
			'D1' => 'One - Day Service',
			'D2' => 'Two - Day Service',
			'DH' => 'Drop and Hook Receiving',
			'DL' => 'Delivery',
			'EU' => 'Exclusive Use Of Equipment',
			'EX' => 'Expedited Service',
			'F1' => 'Full Service',
			'H1' => 'Temperature Protection',
			'L1' => 'Shipper Load, Carrier Count',
			'LL' => 'Loading Service',
			'PL' => 'Palletizing',
			'RD' => 'Residential Delivery',
			'SD' => 'Shrinkage Allowance',
			'S1' => 'Shipper Load, Consignee Unload',
			'S2' => 'Slip Sheet, Truck',
			'V1' => 'Drop Yard',
			'V2' => 'Drop Dock',
			'XX' => 'Third Party Pallets',
			'YY' => 'Split Pickup',
			'ZZ' => 'Mutually Defined',
		),
		'AT504' => array(
			'CE' => 'Centigrade, Celsius',
			'FA' => 'Fahrenheit',
		),
		'PLD03' => array(
			'K' => 'Kilograms',
			'L' => 'Pounds',
		),
		'NTE01' => array(
			'ADD' => 'Additional Information',
			'BOL' => 'Bill of Lading Note',
			'CAH' => 'Basis for Calculation',
			'CBH' => 'Monetary Amount Description',
			'CCE' => 'Contract Details',
			'EED' => 'Equipment Description',
			'GEN' => 'Entire Transaction Set',
			'INT' => 'General order comments',
			'LOC' => 'Location Description Information',
			'SPH' => 'Special Handling',
			'WHI' => 'Warehouse Instruction',
			'ZZZ' => 'Miles',
			//'ZZZ' => 'Mutually Defined',
		),
		'N101' => array(
			'BN' => 'Beneficial Owner',
			'BT' => 'Bill-to-Party',
			'CA' => 'Carrier',
			'CN' => 'Consignee',
			'IM' => 'Importer',
			'PF' => 'Party To Receive Freight Bill',
			'DT' => 'Destination Terminal',
			'OT' => 'Origin Terminal',
			'RD' => 'Destination Intermodal Ramp',
			'RO' => 'Original Intermodal Ramp',
			'SF' => 'Ship From',
			'SH' => 'Shipper',
			'ST' => 'Ship To',
			'XQ' => 'Canadian Customs Broker',
			'XU' => 'United States Customs Broker',
		),
		'G6101' => array(
			'BI' => 'Bill Inquiry Contact',
			'BJ' => 'Operations Contact',
			'CN' => 'General Contact',
			'DI' => 'Delivery Instructions Contact',
			'IC' => 'Information Contact',
			'ZZ' => 'Mutually Defined',
			'OC' => 'Order Contact',
			'TA' => 'Traffic Administrator',
			'TD' => 'Tender Developer',
		),
		'G6103' => array(
			'EM' => 'Electronic Mail',
			'FX' => 'Facsimile',
			'TE' => 'Telephone',
			'AD' => 'Delivery Location Phone',
		),
		'N711' => array(		
			'BR' => 'Barge',
			'BX' => 'Boxcar',
			'CC' => 'Container resting on a Chassis',
			'CH' => 'Chassis',
			'CN' => 'Container',
			'CV' => 'Closed Van',
			'CZ' => 'Refrigerated Container',
			'DD' => 'Double-Drop Trailer',
			'FR' => 'Flat Bed Trailer - Removable Sides',
			'FT' => 'Flat Bed Trailer',
			'RA' => 'Fixed-Rack, Flat-Bed Trailer',
			'RD' => 'Fixed-Rack, Double Drop Trailer',
			'RR' => 'Rail Car',
			'RS' => 'Fixed-Rack, Single-Drop Trailer',
			'RT' => 'Controlled Temperature Trailer (Reefer)',
			'SD' => 'Single-Drop Trailer',
			'ST' => 'Removable Side Trailer',
			'SV' => 'Special Requirements',
			'TA' => 'Trailer, Heated/Insulated/Ventilated',
			'TB' => 'Trailer, Boat',
			'TF' => 'Trailer, Dry Freight',
			'TG' => 'Trailer, Tank (Gas)',
			'TI' => 'Trailer, Insulated',
			//'TJ' => 'Trailer, Tank (Chemicals)',
			'TJ' => 'Bulk Tanker',
			'TK' => 'Trailer, Tank (Food Grade-Liquid)',
			'TL' => 'Trailer (not otherwise specified)',
			'TM' => 'Trailer, Insulated/Ventilated',
			'TN' => 'Tank Car',
			'TP' => 'Trailer, Pneumatic',
			'TQ' => 'Trailer, Electric Heat',
			'TR' => 'Reefer',
			//'TR' => 'Tractor',
			'TV' => 'Truck, Van',
			'TW' => 'Trailer, Refrigerated',
			'VT' => 'Vessel, Ocean, Containership',
		),
		'N722' => array(
			'101' => '101 Inch Dry Van',
			'101M' => '101 Inch Dry Van Dimensional',
			'48FB' => '48 Foot Flatbed',
			'48FT' => '48 Foot Dry Van',
			'48HZ' => '48 Foot HazMat Trailer',
			'53DM' => '53 Foot Dry Van Dimensional',
			'53FB' => '53 Foot Flatbed',
			'53FT' => '53 Foot Van',
			'53IM' => '53 Foot Intermodal',
			'CON' => 'Conestoga',
			'DDVT' => 'Double Deck Dry Van Trailer',
			'TONU' => 'Truck Order Not Used',
			'VAN' => 'Dry Van',
		),
		'S502' => array(
			'CL' => 'Complete Load',
			'CU' => 'Complete Unload',
			'LD' => 'Load',
			'PL' => 'Partial Load',
			'PU' => 'Partial Unload',
			'UL' => 'Unload',
		),
		'S504' => array(
			'E' => 'Metric Ton',
			'K' => 'Kilograms',
			'L' => 'Pounds',
			'M' => 'Measurement Ton',
		),
		'S508' => array(
			'E' => 'Cubic Feet',
			'F' => '100 Board Feet',
			'G' => 'Gallons',
			'X' => 'Cubic Meters',
		),
		'AT801' => array(
			'E' => 'Estimated Net Weight',
			'G' => 'Gross Weight',
			'N' => 'Actual Net Weight',
			'A3' => 'Shippers Weight',
			'PA' => 'Pallet Weight',
		),
		'AT802' => array(
			'E' => 'Metric Ton',
			'K' => 'Kilograms',
			'L' => 'Pounds',
			'M' => 'Measurement Ton',
		),
		'AT806' => array(
			'E' => 'Cubic Feet',
			'F' => '100 Board Feet',
			'G' => 'Gallons',
			'X' => 'Cubic Meters',
		),
		'L504' => array(
			'N' => 'National Motor Freight Classification (NMFC)',
			'Z' => 'Mutually defined',
		),
		'L505' => array(
			'BBL' => 'Barrel',
			'BLK' => 'Bulk',
			'CAS' => 'Case',
			'CTN' => 'Carton',
			'DRM' => 'Drum',
			'PCS' => 'Pieces',
			'PKG' => 'Package',
			'PLT' => 'Pallet',
			'ROL' => 'Roll',
			'SKD' => 'Skid',
			'UNT' => 'Unit',
		),
		'L509' => array(
			'N' => 'National Motor Freight Classification (NMFC)',
			'Z' => 'Mutually defined',
		),
		'OID04' => array(
			'BN' => 'Bulk',
			'BR' => 'Barrel',
			'CA' => 'Case',
			'CT' => 'Carton',
			'DR' => 'Drum',
			'EA' => 'Each',
			'GA' => 'Gallon',
			'LB' => 'Pound',
			'PC' => 'Piece',
			'PK' => 'Package',
			'PL' => 'Pallet/Unit Load',
			'RL' => 'Roll',
			'SV' => 'Skid',
			'UN' => 'Unit',
		),
		'S506' => array(
			'BN' => 'Bulk',
			'BR' => 'Barrel',
			'CA' => 'Case',
			'CT' => 'Carton',
			'DR' => 'Drum',
			'EA' => 'Each',
			'GA' => 'Gallon',
			'LB' => 'Pound',
			'PC' => 'Piece',
			'PK' => 'Package',
			'PL' => 'Pallet/Unit Load',
			'RL' => 'Roll',
			'SV' => 'Skid',
			'UN' => 'Unit',
		),
		'OID06' => array(
			'E' => 'Metric Ton',
			'K' => 'Kilograms',
			'L' => 'Pounds',
			'M' => 'Measurement Ton',
		),
		'OID08' => array(
			'E' => 'Cubic Feet',
			'F' => '100 Board Feet',
			'G' => 'Gallons',
			'X' => 'Cubic Meters',
		),
		'L302' => array(
			'G' => 'Gross Weight',
		),
		'L304' => array(
			'CW' => 'Per Hundred Weight',
			'FR' => 'Flat Rate',
			'PS' => 'Per Shipment',
		),
		'L308' => array(
			'400' => 'Freight',
			'405' => 'Fuel Surcharge',
			'LHS' => 'Linehaul Service',
		),
		'L310' => array(
			'E' => 'Cubic Feet',
			'F' => '100 Board Feet',
			'G' => 'Gallons',
			'X' => 'Cubic Meters',
		),
		'L312' => array(
			'E' => 'Metric Ton',
			'K' => 'Kilograms',
			'L' => 'Pounds',
			'M' => 'Measurement Ton',
		),
		'LH101' => array(
			'BN' => 'Bulk',
			'BR' => 'Barrel',
			'CA' => 'Case',
			'CT' => 'Carton',
			'DR' => 'Drum',
			'EA' => 'Each',
			'GA' => 'Gallon',
			'LB' => 'Pound',
			'PC' => 'Piece',
			'PK' => 'Package',
			'PL' => 'Pallet/Unit Load',
			'RL' => 'Roll',
			'SV' => 'Skid',
			'UN' => 'Unit',
		),
		'LAD01' => array(
			'BOX' => 'Box',
			'CNT' => 'Container',
			'PCS' => 'Pieces',
		),
		'LAD07' => array(
			'MN' => 'Model Number',
		),
		'LAD09' => array(
			'ZZ' => 'Mutually Defined (VIN)',
		),
		'B104' => array(
			'A' => 'Reservation Accepted',
			'D' => 'Reservation Cancelled',
			'R' => 'Delete',
		),
		'N901' => array(
			'CN' => 'Carrier\'s Reference Number',
		),
		'AT701' => array(
			'A3' => 'Shipment Returned to Shipper',
			'A7' => 'Refused by Consignee',
			'A9' => 'Shipment Damaged',
			'AF' => 'Carrier Departed Pick-up Location with Shipment',
			'AG' => 'Estimated Delivery',
			'AH' => 'Attempted Delivery',
			'AI' => 'Shipment has been Reconsigned',
			'AJ' => 'Tendered for Delivery',
			'AP' => 'Delivery Not Completed',
			'AR' => 'Rail Arrival at Destination Intermodal Ramp',
			'AV' => 'Available for Delivery',
			'B6' => 'Estimated to Arrive at Carrier Terminal',
			'BA' => 'Connecting Line or Cartage Pick-up',
			'BC' => 'Storage in Transit',
			'CD' => 'Carrier departed Delivery Location',
			'CL' => 'Trailer Closed Out',
			'D1' => 'Completed Unloading at Delivery Location',
			'J1' => 'Delivered to Connecting Line',
			'K1' => 'Arrived at Customs',
			'OO' => 'Paperwork Received - Did not Receive Shipment or Equipment',
			'P1' => 'Departed Terminal Location',
			'PR' => 'U.S. Customs Hold at In-Bond Location',
			'R1' => 'Received from Prior Carrier',
			'S1' => 'Trailer Spotted at Consignee\'s Location',
			'SD' => 'Shipment Delayed',
			'X1' => 'Arrived at Delivery Location',
			'X2' => 'Estimated Date and/or Time of Arrival at Consignee\'s Location',
			'X3' => 'Arrived at Pick-up Location',
			'X4' => 'Arrived at Terminal Location',
			'X6' => 'En Route to Delivery Location',
		),
		'AT702' => array(
			'A1' => 'Missed Delivery',
			'A2' => 'Incorrect Address',
			'A3' => 'Indirect Delivery',
			'A5' => 'Unable to Locate',
			'A6' => 'Address Corrected - Delivery Attempted',
			'AA' => 'Mis-sort',
			'AD' => 'Customer Requested Future Delivery',
			'AE' => 'Restricted Articles Unacceptable',
			'AF' => 'Accident',
			'AG' => 'Consignee Related',
			'AH' => 'Driver Related',
			'AI' => 'Mechanical Breakdown',
			'AJ' => 'Other Carrier Related',
			'AK' => 'Damaged, Rewrapped in Hub',
			'AL' => 'Previous Stop',
			'AM' => 'Shipper Related',
			'AN' => 'Holiday - Closed',
			'AO' => 'Weather or Natural Disaster Related',
			'AR' => 'Improper International Paperwork',
			'AS' => 'Hold Due to Customs Documentation Problems',
			'AT' => 'Unable to Contact Recipient for Broker Information',
			'AV' => 'Exceeds Service Limitations',
			'AW' => 'Past Cut-off Time',
			'AX' => 'Insufficient Pick-up Time',
			'AY' => 'Missed Pick-up',
			'AZ' => 'Alternate Carrier Delivered',
			'B1' => 'Consignee Closed',
			'B2' => 'Trap for Customer',
			'B5' => 'Held for Consignee',
			'B8' => 'Improper Unloading Facility or Equipment',
			'B9' => 'Receiving Time Restricted',
			'BB' => 'Held per Shipper',
			'BC' => 'Missing Documents',
			'BD' => 'Border Clearance',
			'BE' => 'Road Conditions',
			'BG' => 'Other',
			'BI' => 'Cartage Agent',
			'BK' => 'Prearranged Appointment',
			'BL' => 'Held for Protective Service',
			'BS' => 'Refused by Customer',
			'BT' => 'Returned to Shipper',
			'C1' => 'Waiting for Customer Pick-up',
			'C2' => 'Credit Hold',
			'C3' => 'Suspended at Customer Request',
			'C4' => 'Customer Vacation',
			'C5' => 'Customer Strike',
			'C6' => 'Waiting Shipping Instructions',
			'C7' => 'Waiting for Customer Specified Carrier',
			'C8' => 'Collect on Delivery Required',
			'C9' => 'Cash Not Available From Consignee',
			'CB' => 'No Requested Arrival Date Provided by Shipper',
			'CC' => 'No Requested Arrival Time Provided by Shipper',
			'D1' => 'Carrier Dispatch Error',
			'D2' => 'Driver Not Available',
			'NA' => 'Normal Appointment',
			'NS' => 'Normal Status',
			'P1' => 'Processing Delay',
			'P2' => 'Waiting Inspection',
			'P3' => 'Production Falldown',
			'P4' => 'Held for Full Carrier Load',
			'RC' => 'Reconsigned',
			'S1' => 'Delivery Shortage',
			'T2' => 'Tractor, Conventional, Not Available',
			'T3' => 'Trailer not Available',
			'T4' => 'Trailer Not Usable Due to Prior Product',
			'T5' => 'Trailer Class Not Available',
			'T6' => 'Trailer Volume Not Available',
			'T7' => 'Insufficient Delivery Time',

		),
		'AT703' => array(
			'AA' => 'Pick-up Appointment Date and/or Time',
			'AB' => 'Delivery Appointment Date and/or Time',
		),
		'AT704' => array(
			'NA' => 'Normal Appointment',
			'BG' => 'Other',
			'BF' => 'Carrier Keying Error',
			'AM' => 'Shipper Related',
			'AJ' => 'Other Carrier Related',
			'AG' => 'Consignee Related',
			'NS' => 'Normal Status',
		),
		'AT707' => array(
			'LT' => 'Local Time',
		),
		'BGN01' => array(
			'00' => 'Original',
		),
		'OTI01' => array(
			'TR' => 'Transaction Set rejected',
			'TE' => 'Transaction Set accept with error',
		),
		'OTI02' => array(
			'CN' => 'Carriers Reference Number',
		),
		'REF01' => array(
			'SI' => 'SID – Shipment Identification',
			'11' => 'Customer',
		),
		'TED01' => array(
			'007' => 'Missing Data',
			'011' => 'Not Matching',
			'848' => 'Incorrect Data',
		),
	);
	
	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {
		global $sts_error_level_label, $sts_crm_dir;

		$this->debug = $debug;
		$this->primary_key = "EDI_CODE";
		$this->database = $database;
		$this->setting_table = sts_setting::getInstance($database, $debug);
		$this->edi_enabled = ($this->setting_table->get("api", "EDI_ENABLED") == 'true');
		$this->edi_log =				$this->setting_table->get( 'api', 'EDI_LOG_FILE' );
		if( isset($this->edi_log) && $this->edi_log <> '' && 
			$this->edi_log[0] <> '/' && $this->edi_log[0] <> '\\' 
			&& $this->edi_log[1] <> ':' )
			$this->edi_log = $sts_crm_dir.$this->edi_log;

		$this->edi_diag_level =		$this->setting_table->get( 'api', 'EDI_DIAG_LEVEL' );
		$this->diag_level =  array_search(strtolower($this->edi_diag_level), $sts_error_level_label);
		if( $this->diag_level === false ) $this->diag_level = EXT_ERROR_ALL;

		$myclass = get_class ();
		if( $debug ) echo "<p>Create $myclass</p>";
		parent::__construct( $database, EDI_TABLE, $debug);
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

	public function log_event( $message, $level = EXT_ERROR_ERROR ) {
		//if( $this->debug ) echo "<p>log_event: $this->edi_log, $message, level=$level</p>";
		$this->message = $message;
		if( $this->diag_level >= $level ) {
			if( (file_exists($this->edi_log) && is_writable($this->edi_log)) ||
				is_writable(dirname($this->edi_log)) ) 
				file_put_contents($this->edi_log, date('m/d/Y h:i:s A')." pid=".getmypid().
					" msg=".$message."\n\n", FILE_APPEND);
		}
	}
	
	public function getMessage() {
		return $this->message;
	}
	
	public function enabled() {
		return $this->edi_enabled;
	}
	
	public function get_field_value( $field, $value ) {
		return isset($this->field_value[$field]) && isset($this->field_value[$field][$value]) ?
			$this->field_value[$field][$value] : '';
	}
	
	public function get_field_key( $field, $value ) {
		$result = '';
		if( isset($this->field_value[$field]) ) {
			$reverse = array_flip($this->field_value[$field]);
			if( isset($reverse[$value]))
				$result = $reverse[$value];
		}
		return $result;
	}
		
	//! lookup a path within the edi structure, return the element
	// $edi - the structure representing the EDI message
	// $path - the path, like unix path /section/section/element
	// $expect_multiple - if true, you want multiple matches, and get an array returned
	//					- if false, you don't want multiple, and will trigger an exception
	public function lookup_edi_path( $edi, $path, $expect_multiple = false ) {
		//if( $this->debug ) echo "<p>".__METHOD__.": Search for $path".($expect_multiple ? ", multiple" : "").".</p>";
		$this->log_event( __METHOD__.": Search for $path".($expect_multiple ? ", multiple" : "").".", EXT_ERROR_DEBUG);
		$elements = explode('/', $path);
		$multiple_matches = array();
		$this->lookup_count = 0;

		$pointer = &$edi;
		$last_element = end($elements);
		foreach($elements as $element) {
			// Search for $element
			if( isset($pointer[$element]) ) {
				//if( $this->debug ) echo "<p>lookup_edi_path: value match e=$element</p>";
				$pointer = &$pointer[$element];
				$found = true;
			} else {
				$found = false;
				foreach($pointer as $next_level) {
					if( isset($next_level["section"]) && $next_level["section"] == $element ) {
						//if( $this->debug ) echo "<p>lookup_edi_path: section match e=$element le=$last_element</p>";
						if( $element == $last_element ) {
							$multiple_matches[] = $next_level["segments"];
							$found = true;
						} else {
							$pointer = &$next_level["segments"];
							$found = true;
							break;
						}
					} else if( isset($next_level["id"]) && $next_level["id"] == $element ) {
						//if( $this->debug ) echo "<p>lookup_edi_path: id match e=$element le=$last_element</p>";
						if( $element == $last_element ) {
							$multiple_matches[] = $next_level["fields"];
							$found = true;
						} else {
							$pointer = &$next_level["fields"];
							$found = true;
							break;
						}
					}
				}
			}
			if( ! $found ) {
				$pointer = false;
				break;
			}
		}
		
		$this->lookup_count = count($multiple_matches);
		if( $this->lookup_count > 0 ) {
			if( $this->lookup_count > 1 ) {
				if( ! $expect_multiple ) {
					$prev = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)[1];					
					throw new Exception('lookup_edi_path: not expecting multiple matches, found '.$this->lookup_count.' '.$path.'<br>called from '.$prev['function']);
				}
				$pointer = $multiple_matches;
			} else {
				$pointer = $expect_multiple ? $multiple_matches : $multiple_matches[0];
			}
		} else if( $pointer )
			$this->lookup_count = 1;
		return $pointer;
	}

	public function edi_get_type_sid( $edi ) {
		$type = $this->lookup_edi_path( $edi, 'Not Defined/GS/GS01' );

		switch( $type ) {
			case EDI_204:
				$result = '204';
				$sid = $this->lookup_edi_path( $edi, '204/Heading/B2/B204' );
				break;

			case EDI_990:
				$result = '990';
				$sid = $this->lookup_edi_path( $edi, '990/B1/B102' );
				break;

			case EDI_214:
				$result = '214';
				$sid = $this->lookup_edi_path( $edi, '214/B10/B1002' );
				break;

			case EDI_824:
				$result = '824';
				$sid = 'unknown';
				break;

			case false:
			default:
				$result = 'unknown';
				$sid = 'unknown';
		}
		return array($result, $sid);
	}

	public function new_filenames( $client, $filenames ) {
		if( is_array($filenames) && count($filenames) > 0 )
			$filenames_str = "('".implode("', '", $filenames)."')";
		else if( is_array($filenames) )
			$filenames_str = "()";
		else
			$filenames_str = "false";
		$this->log_event( __METHOD__.": client = $client filenames = $filenames_str", EXT_ERROR_DEBUG);
		$result = $filenames;
		if( is_array($filenames) && count($filenames) > 0 ) {
			$check = $this->fetch_rows( "EDI_CLIENT = '".$client."'
				AND FILENAME IN ".$filenames_str, "DISTINCT FILENAME");
			if( is_array($check) && count($check) > 0 ) {
				$matching = array();
				foreach( $check as $row ) {
					$matching[] = $row["FILENAME"];
				}
				$result = array_diff($filenames, $matching);
				if($this->debug) {
					echo "<pre>";
					var_dump($filenames);
					var_dump($matching);
					var_dump($result);
					echo "</pre>";
				}
			}
		}
		if( is_array($result) && count($result) > 0 )
			$filenames_str = "('".implode("', '", $result)."')";
		else if( is_array($result) )
			$filenames_str = "()";
		else
			$filenames_str = "false";
		$this->log_event( __METHOD__.": return $filenames_str", EXT_ERROR_DEBUG);
		return $result;
	}
	
			
}

?>
