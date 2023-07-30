<?php

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

// This defines the formatting of the %STOPS_GO_HERE% section.
// If not found, the default version in the stop class will be used.
// Copy this file and change option/MANIFEST_STOPS to point to it.

// This is also affected by option/HIDE_STOPS_MANIFEST and option/HIDE_SHIPMENT_MANIFEST

// You will need to edit the header and layout sections
// Be sure to use HTML that works in emails, not all HTML does...

// %NAME% is replaced with the field of that name
// #NAME#text# - this is conditional, will display text if the field NAME exists and has a value
// Within text, use @ if you want to show a #

//
// SEQUENCE_NO		-	Stop number
// STOP_TYPE		-	Stop type
// NAME				-	Name (of business) at stop
// ADDRESS			-	Address line 1
// ADDRESS2			-	Address line 2
// CITY				-	City
// STATE			-	State
// ZIP_CODE			-	ZIP/Postal code
// CONTACT			-	Contact at stop
// POS				-	PO numbers
// APPT				-	Appointment confirmation #
// STOP_DISTANCE	-	Distance from last stop
// PHONE			-	Phone # at stop
// SHIPMENT			-	Shipment #
// SS_NUMBER		-	Office reference number
// REF_NUMBER		-	Reference number
// BOL_NUMBER		-	BOL number
// PICKUP_NUMBER	-	Pickup number
// CUSTOMER_NUMBER	-	Customer number
// DUE				-	When stop is due
// COMM				-	Commodity
// NOTES			-	Notes
// PIECES2			-	Number of pieces
// PALLETS2			-	Number of pallets
// WEIGHT2			-	Weight
// TEMP2			-	Temperature
// HAZMAT			-	HAZMAT
// UN_NUMBERS		-	UN numbers
// STOP_COMMENT		- 	Comment


//! SCR# 303 - Added NOTES column

$sts_email_carrier_stops = array(	//! $sts_email_carrier_stops
	'header' => '
	<table width="98%" align="center" border="0" cellspacing="0">
	<tr valign="top">
		<th align="center" class="text-center">
			#
			<hr>
		</th>
		<th>
			Type
			<hr>
		</th>
		<th>
			Shipper/Consignee
			<hr>
		</th>
		<th>
			Shipment#
			<hr>
		</th>
		<th>
			When
			<hr>
		</th>
		<th>
			Cmdty
			<hr>
		</th>
		<th>
			Notes
			<hr>
		</th>
		<th align="right" class="text-right">
			Pcs
			<hr>
		</th>
		<th align="right" class="text-right">
			Pallets
			<hr>
		</th>
		<th align="right" class="text-right">
			Weight
			<hr>
		</th>
		<th align="right" class="text-right">
			Temp
			<hr>
		</th>
	</tr>',

	'layout' => '
	<br>
	<tr valign="top">
		<td align="center">
			%SEQUENCE_NO%
		</td>
		<td>
			%STOP_TYPE%
		</td>
		<td>
			<strong>%NAME%</strong><br>
			%ADDRESS%<br>
			#ADDRESS2#%ADDRESS2%<br>#
			#CITY#%CITY%, ##STATE#%STATE%, ##ZIP_CODE#%ZIP_CODE%<br>#
			#CONTACT#Contact: %CONTACT%<br>#
			#POS#PO@\'s: %POS%<br>#
			#APPT#Conf@: %APPT%<br>#
			#STOP_DISTANCE#%STOP_DISTANCE% miles<br>#
			#PHONE#Phone: %PHONE%#
			<br>
		</td>
		<td>
			<strong>%SHIPMENT%#SS_NUMBER# / %SS_NUMBER%#</strong><br>
			#REF_NUMBER#REF@: %REF_NUMBER%<br>#
			#BOL_NUMBER#BOL@: %BOL_NUMBER%<br>#
			#PICKUP_NUMBER#Pickup@: %PICKUP_NUMBER%<br>#
			#CUSTOMER_NUMBER#Customer@: %CUSTOMER_NUMBER%<br>#
			#STOP_COMMENT#%STOP_COMMENT%<br>#
		</td>
		<td>
			%DUE%
		</td>
		<td>
			%COMM%
			#HAZMAT#<br><span class="hazmat"><strong>%HAZMAT%</strong> %UN_NUMBERS%</span>#
		</td>
		<td>
			%NOTES%
		</td>
		<td xpieces align="right">
			%PIECES2%
		</td>
		<td xpallets align="right">
			%PALLETS2%
		</td>
		<td xweight align="right">
			%WEIGHT2%
		</td>
		<td xtemp align="right">
			%TEMP2%
		</td>
	</tr>
	',

	'footer' => '</table>
		' );

?>