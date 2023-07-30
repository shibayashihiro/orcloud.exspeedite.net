<?php 

// $Id: sts_clientbilling.php 5030 2023-04-12 20:31:34Z duncan $
// Enter client billing functions

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_shipment_class.php" );

function log_financial( $shipment_table, $shipment, $operation, $pallet_rate, $hand_pallet,
	$handling_charge, $freight, $extra_charge, $stop_off, $weekend,
	$detention, $un_detention, $rmp_mil, $fuel_cost, $adjust_value,
	$selection_fee, $discount, $total, $c, $code, $rate ) {
	if( isset($shipment_table) && isset($shipment) && $shipment > 0 ) {
		$comments = [];
		if( $pallet_rate > 0 )		$comments[] = 'pallet=$'.number_format($pallet_rate,2).' '.$c;
		if( $hand_pallet > 0 )		$comments[] = 'hpallet=$'.number_format($hand_pallet,2).' '.$c;
		if( $handling_charge > 0 )	$comments[] = 'handling=$'.number_format($handling_charge,2).' '.$c;
		if( $freight > 0 )			$comments[] = 'freight=$'.number_format($freight,2).' '.$c;
		if( $extra_charge > 0 )		$comments[] = 'extra=$'.number_format($extra_charge,2).' '.$c;
		if( $stop_off > 0 )			$comments[] = 'stopoff=$'.number_format($stop_off,2).' '.$c;
		if( $weekend > 0 )			$comments[] = 'weekend=$'.number_format($weekend,2).' '.$c;
		if( $detention > 0 )		$comments[] = 'det=$'.number_format($detention,2).' '.$c;
		if( $un_detention > 0 )		$comments[] = 'un.det=$'.number_format($un_detention,2).' '.$c;
		if( $rmp_mil > 0 )			$comments[] = 'rpm=$'.number_format($rmp_mil,2).' '.$c;
		if( $fuel_cost > 0 )		$comments[] = 'fsc=$'.number_format($fuel_cost,2).' '.$c;
		if( $adjust_value > 0 )		$comments[] = 'adj=$'.number_format($adjust_value,2).' '.$c;
		if( $selection_fee > 0 )	$comments[] = 'select=$'.number_format($selection_fee,2).' '.$c;
		if( $discount > 0 )			$comments[] = 'disc=$'.number_format($discount,2).' '.$c;

		if( count($code) > 0 ) {
			for($i=0;$i<count($code);$i++) {
				$comments[] = $code[$i].'=$'.number_format($rate[$i],2).' '.$c;
			}
		}

		if( $total > 0 )			$comments[] = '$total=$'.number_format($total,2).' '.$c;
		
		$comment = $operation.' Billing: '.implode(', ', $comments);
		
		$shipment_table->add_shipment_status($shipment, $comment);
	}
}

function check_financial( $shipment_table, $shipment, $pallet_rate, $hand_pallet,
	$handling_charge, $freight, $extra_charge, $stop_off, $weekend,
	$detention, $un_detention, $rmp_mil, $fuel_cost, $adjust_value,
	$selection_fee, $discount, $total, $rate ) {
	
	global $exspeedite_db, $sts_debug;
	$result = false;
		
	$sum = $pallet_rate + $hand_pallet + $handling_charge +
		$freight + $extra_charge + $stop_off + $weekend +
		$detention + $un_detention + $rmp_mil + $fuel_cost +
		$adjust_value + $selection_fee - $discount;
		
	if( count($rate) > 0 ) {
		foreach( $rate as $r ) {
			$sum += $r;
		}
	}
	
	if( round($sum,2) == round($total,2) ) {	// Use rounding to avoid minor difference.
		$shipment_table->add_shipment_status( $shipment,
			'Double Check: total ($'.number_format($total,2).') matches sum ($'.number_format($sum,2).')');
		$result = true;
	} else {
		$fix = $exspeedite_db->get_one_row("UPDATE EXP_CLIENT_BILLING
			SET TOTAL = ".round($sum,2)."
			WHERE SHIPMENT_ID = ".$shipment);	

		$shipment_table->add_shipment_status( $shipment,
			'Double Check: total ($'.number_format($total,2).') DOES NOT MATCH sum of charges ($'
				.number_format($sum,2).') DIFFERENCE='.number_format($total - $sum,2).
				($fix ? ' UPDATED' : ' NOT UPDATED') );
		
		require_once( "include/sts_email_class.php" );
		$email = sts_email::getInstance($exspeedite_db, $sts_debug);
		$email->send_alert('Shipment: '.$shipment.' - total ($'.number_format($total,2).') DOES NOT MATCH sum of charges ($'
				.number_format($sum,2).') DIFFERENCE='.number_format($total - $sum,2).
				($fix ? ' UPDATED' : ' NOT UPDATED') );
		
		$result = false;
	}
	return $result;	
}

?>