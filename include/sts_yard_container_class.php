<?php

// $Id:$
// Yard Container Class - all things to do with yards and containers

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );

class sts_yard_container extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {
		global $sts_log_to_file;

		$this->debug = $debug;
		$this->primary_key = "YARD_CONTAINER_CODE";
		if( $this->debug ) echo "<p>Create sts_yard_container</p>";

		parent::__construct( $database, YARD_CONTAINER_TABLE, $debug);
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
    
    // Get a container number from a load number
    public function container_num( $load_code ) {
	    $result = false;
	    
	    $check = $this->database->get_one_row("
			SELECT S.ST_NUMBER
			FROM EXP_SHIPMENT S, EXP_STOP ST
			WHERE S.LOAD_CODE = $load_code
			UNION
			SELECT S.ST_NUMBER 
			FROM EXP_SHIPMENT S, EXP_STOP ST
			WHERE ST.LOAD_CODE = $load_code
			AND ST.SHIPMENT = S.SHIPMENT_CODE
			LIMIT 1;");
		
		if( is_array($check) && isset($check['ST_NUMBER']) )
			$result = $check['ST_NUMBER'];
	    
	    return $result;
    }

     // Get a shipment number from a load number
    public function shipment_num( $load_code ) {
	    $result = false;
	    
	    $check = $this->database->get_one_row("
			SELECT S.SHIPMENT_CODE
			FROM EXP_SHIPMENT S, EXP_STOP ST
			WHERE S.LOAD_CODE = $load_code
			UNION
			SELECT S.SHIPMENT_CODE 
			FROM EXP_SHIPMENT S, EXP_STOP ST
			WHERE ST.LOAD_CODE = $load_code
			AND ST.SHIPMENT = S.SHIPMENT_CODE
			LIMIT 1;");
		
		if( is_array($check) && isset($check['SHIPMENT_CODE']) )
			$result = $check['SHIPMENT_CODE'];
	    
	    return $result;
    }

    // Get a trailer number from a load number
    public function trailer_num( $load_code ) {
	    $result = false;
	    
	    $check = $this->database->get_one_row("
			SELECT T.UNIT_NUMBER
			FROM EXP_TRAILER T, EXP_LOAD L
			WHERE L.LOAD_CODE = $load_code
			AND L.TRAILER = T.TRAILER_CODE
			LIMIT 1");
		
		if( is_array($check) && isset($check['UNIT_NUMBER']) )
			$result = $check['UNIT_NUMBER'];
	    
	    return $result;
    }

   //! create a menu of yards
    public function yard_menu( $selected = false, $id = 'YARD', $match = 'yard', $onchange = false, $any = false ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
		$select = false;

		$choices = $this->database->get_multiple_rows("SELECT CONTACT_CODE, LABEL
			FROM EXP_CONTACT_INFO
			WHERE CONTACT_TYPE = '".$match."'
			AND (SELECT INTERMODAL FROM EXP_CLIENT WHERE CLIENT_CODE = CONTACT_CODE)
			AND EXP_CONTACT_INFO.ISDELETED = FALSE");

		if( is_array($choices) && count($choices) > 0) {
			
			$select = '<select class="form-control input-sm" name="'.$id.'" id="'.$id.'"'.($onchange ? ' onchange="form.submit();"' : '').'>
			';
			foreach( $choices as $row ) {
				$select .= '<option value="'.$row["CONTACT_CODE"].'"';
				if( $selected && $selected == $row["CONTACT_CODE"] )
					$select .= ' selected';
				$select .= '>'.$row["LABEL"].'</option>
				';
			}
			$select .= '</select>';
		}
			
		return $select;
	}
	
	//! SCR# 853 - Move container & trailer into yard
	public function add_into_yard( $stop_code, $yard_code ) {
		if( $this->debug ) echo"<p>".__METHOD__.": entry stop_code = $stop_code yard_code = $yard_code</p>";

		$check = $this->database->get_one_row("SELECT S.SHIPMENT, S.YARD_CODE, L.TRAILER,
			S.STOP_TYPE, S.IM_STOP_TYPE,
			(SELECT ST_NUMBER FROM EXP_SHIPMENT WHERE SHIPMENT_CODE = SHIPMENT) AS ST_NUMBER, IM_STOP_TYPE
			FROM EXP_STOP S, EXP_LOAD L
			WHERE S.STOP_CODE = $stop_code
			AND S.LOAD_CODE = L.LOAD_CODE");
		
		if( $this->debug ) {
			echo "<pre>".__METHOD__." check\n";
			var_dump($check);
			echo "</pre>";
		}
		
		if( is_array($check) &&
			isset($check['SHIPMENT']) && $check['SHIPMENT'] > 0 &&
			isset($yard_code) && $yard_code > 0 &&
			! empty($check['ST_NUMBER']) ){
			 //! empty($check['IM_STOP_TYPE']) && $check['IM_STOP_TYPE'] == 'dropyard'
			
			// Can't have duplicate rows with same container number
			$this->delete_row("ST_NUMBER = '".$check['ST_NUMBER']."'" );
			
			$changes = [
				'SHIPMENT_CODE' => $check['SHIPMENT'],
				'ST_NUMBER' => $check['ST_NUMBER'],
				'YARD_CODE' => $yard_code,
			];
			
			if( isset($check['STOP_TYPE']) && $check['STOP_TYPE'] == 'dropdock' )
				$changes['STOP_TYPE'] = 'docked';
			else
				$changes['STOP_TYPE'] = 'empty';
			
			if( isset($check['TRAILER']) && $check['TRAILER'] > 0 )
				$changes['TRAILER_CODE'] = $check['TRAILER'];

			$this->add( $changes );
			if( $this->debug ) echo"<p>".__METHOD__.": ADDED into yard $yard_code.</p>";
		} else {
			if( $this->debug ) echo"<p>".__METHOD__.": DON'T add into yard.</p>";
		}
	}

	//! SCR# 853 - Move container & trailer into yard
	public function leave_at_client( $shipment ) {
		if( $this->debug ) echo"<p>".__METHOD__.": entry shipment = $shipment</p>";
		$result = false;
		
		$check = $this->database->get_one_row("SELECT ST_NUMBER, CONS_CLIENT_CODE
			FROM EXP_SHIPMENT WHERE SHIPMENT_CODE = $shipment");
			
		if( is_array($check) && ! empty($check['ST_NUMBER'])
			&& ! empty($check['CONS_CLIENT_CODE']) ) {
			
			// Can't have duplicate rows
			$this->delete_row("SHIPMENT_CODE = ".$shipment.
				" AND ST_NUMBER = '".$check['ST_NUMBER']."'".
				" AND YARD_CODE = ".$check['CONS_CLIENT_CODE']);
			
			$changes = [
				'SHIPMENT_CODE' => $shipment,
				'ST_NUMBER' => $check['ST_NUMBER'],
				'YARD_CODE' => $check['CONS_CLIENT_CODE']
			];
			
			if( isset($check['TRAILER']) && $check['TRAILER'] > 0 )
				$changes['TRAILER_CODE'] = $check['TRAILER'];

			$result = $this->add( $changes );
		}
		
		return $result;
	}
	
	//! SCR# 853 - Move container & trailer out of yard
	public function remove_from_yard( $stop_code ) {
		if( $this->debug ) echo"<p>".__METHOD__.": entry stop_code = $stop_code</p>";
		
		$result = $this->delete_row( 'ST_NUMBER = (SELECT SH.ST_NUMBER
			FROM EXP_STOP S, EXP_SHIPMENT SH 
			WHERE S.STOP_CODE = '.$stop_code.'
			AND S.SHIPMENT = SH.SHIPMENT_CODE)');
		
		return $result;
	}

	public function in_yard() {
		$result = 0;
		
		$check = $this->database->get_one_row("SELECT count(*) NUM
			FROM EXP_YARD_CONTAINER");
			
		if(is_array($check) && isset($check['NUM']))
			$result = $check['NUM'];
		
		return $result;
	}

   //! create a menu of yards
    public function in_yard_menu( $selected = false, $id = 'YARD_CONTAINER', $match = '', $onchange = false, $any = false ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
		$select = false;

		$choices = $this->database->get_multiple_rows("
			SELECT *,
			(SELECT T.UNIT_NUMBER FROM EXP_TRAILER T
				WHERE X.TRAILER_CODE = T.TRAILER_CODE) AS UNIT_NUMBER,
			COALESCE((SELECT I.LABEL FROM EXP_CONTACT_INFO I
				WHERE X.YARD_CODE = I.CONTACT_CODE
				AND I.CONTACT_TYPE IN ('dock', 'consignee', 'yard')
				AND (SELECT INTERMODAL FROM EXP_CLIENT WHERE CLIENT_CODE = I.CONTACT_CODE)
				AND I.ISDELETED = FALSE
				LIMIT 1),
				(SELECT C.CLIENT_NAME FROM EXP_CLIENT C
				WHERE C.CLIENT_CODE = X.YARD_CODE)) AS LABEL
			FROM (
				SELECT Y.ST_NUMBER, Y.YARD_CONTAINER_CODE, Y.TRAILER_CODE, Y.YARD_CODE
				FROM EXP_YARD_CONTAINER Y
                WHERE Y.STOP_TYPE = 'empty') X
            GROUP BY Y.ST_NUMBER");

		if( is_array($choices) && count($choices) > 0) {
			
			$select = '<select class="form-control input-sm" name="'.$id.'" id="'.$id.'"'.($onchange ? ' onchange="form.submit();"' : '').'>
			';
			foreach( $choices as $row ) {
				$label = $row["ST_NUMBER"].' / '.$row["UNIT_NUMBER"].' @ '.$row["LABEL"];
				$select .= '<option value="'.$row["YARD_CONTAINER_CODE"].'"';
				if( $selected && $selected == $row["YARD_CONTAINER_CODE"] )
					$select .= ' selected';
				$select .= '>'.$label.'</option>
				';
			}
			$select .= '</select>';
		}
			
		return $select;
	}
	
	private function dt( $x ) {
		return isset($x) ? str_replace(' ', '&nbsp;', date("Y/m/d H:i", strtotime($x))):'';
	}


	
	public function yard_info( $yard_code ) {
		$result = $this->fetch_rows('YARD_CONTAINER_CODE = '.$yard_code, 
			'TRAILER_CODE, SHIPMENT_CODE, ST_NUMBER, YARD_CODE,
			(SELECT UNIT_NUMBER FROM EXP_TRAILER
				WHERE EXP_TRAILER.TRAILER_CODE = EXP_YARD_CONTAINER.TRAILER_CODE) AS UNIT_NUMBER' );
		return (is_array($result) && count($result) == 1 ? $result[0] : false);
	}
	
	public function container_info() {
		$yard = $this->database->get_multiple_rows("
			SELECT *,
				(SELECT UNIT_NUMBER FROM EXP_TRAILER
				WHERE EXP_TRAILER.TRAILER_CODE = X.TRAILER_CODE) AS UNIT_NUMBER,
			COALESCE((SELECT I.LABEL FROM EXP_CONTACT_INFO I
				WHERE X.YARD_CODE = I.CONTACT_CODE
				AND I.CONTACT_TYPE IN ('dock', 'consignee', 'yard')
				AND (SELECT INTERMODAL FROM EXP_CLIENT WHERE CLIENT_CODE = I.CONTACT_CODE)
				AND I.ISDELETED = FALSE
				LIMIT 1),
				(SELECT C.CLIENT_NAME FROM EXP_CLIENT C
				WHERE C.CLIENT_CODE = X.YARD_CODE)) AS LABEL,
			(SELECT FULLNAME FROM EXP_USER
				WHERE USER_CODE = X.CREATED_BY) AS FULLNAME
				
			FROM (
			select YARD_CONTAINER_CODE, ST_NUMBER, TRAILER_CODE, SHIPMENT_CODE, STOP_TYPE,
			COALESCE(YARD_CODE, (SELECT CONS_CLIENT_CODE FROM EXP_SHIPMENT
			WHERE EXP_SHIPMENT.SHIPMENT_CODE = EXP_YARD_CONTAINER.SHIPMENT_CODE)) AS YARD_CODE,
			CREATED_DATE, CREATED_BY
			from EXP_YARD_CONTAINER) X
		");
		
		echo '<h2><img src="images/container_icon.png" alt="container_icon" height="40"> Intermodal Containers <a class="btn btn-md btn-success" href="exp_im_containers.php"><span class="glyphicon glyphicon-refresh"></span></a>
		<a class="btn btn-md btn-default" href="index.php"><span class="glyphicon glyphicon-remove"></span> Back</a></h2>
		
	<h4>Containers in Yards (<span class="glyphicon glyphicon-warning-sign "></span> Hint: <a href="exp_addload.php">Create an empty load</a> and add stop to move from yard)</h4>
		
	<div class="table-responsive well well-sm">
	<table class="display table table-condensed table-bordered table-hover" id="YARD">
	<thead><tr class="exspeedite-bg"><th>&nbsp;</th><th>Container#</th>
		<th>Trailer#</th>
		<th>Shipment#</th>
		<th>Type</th>
		<th>Yard/Dock/Customer</th>
		<th>Date Added</th>
		<th>By</th>
	</tr>
	</thead>
	<tbody>';
	
	if( is_array($yard) && count($yard) > 0 ) {
		foreach($yard as $row) {
			echo '<tr>
				<td><a class="btn btn-danger btn-sm" onclick="confirmation(\'Delete container '.$row['ST_NUMBER'].' from the yard?<br>(no undo)\',\'exp_im_containers.php?DELETE='.$row['YARD_CONTAINER_CODE'].'\')"><span class="text-white"><span class="glyphicon glyphicon-trash"></span></span></a></td>
				<td>'.$row['ST_NUMBER'].'</td>
				<td>'.(isset($row['TRAILER_CODE']) && $row['TRAILER_CODE'] > 0 ?
				'<a href="exp_edittrailer.php?CODE='.$row['TRAILER_CODE'].'">'.$row['UNIT_NUMBER'].'</a>' : '').'</td>
				<td>'.(isset($row['SHIPMENT_CODE']) && $row['SHIPMENT_CODE'] > 0 ?
				'<a href="exp_addshipment.php?CODE='.$row['SHIPMENT_CODE'].'">'.$row['SHIPMENT_CODE'].'</a>' : '').'</td>
				<td>'.$row['STOP_TYPE'].'</td>
				<td><a href="exp_editclient.php?CODE='.$row['YARD_CODE'].'">'.$row['LABEL'].'</a></td>
				<td>'.$this->dt($row['CREATED_DATE']).'</td>
				<td><a href="exp_edituser.php?CODE='.$row['CREATED_BY'].'">'.$row['FULLNAME'].'</a></td>
			</tr>
			';
		}
	} else {
		echo '<td colspan="5">No data</td>';
	}
	echo '</tbody>
	</table>
	</div>
	';
	
	$active = $this->database->get_multiple_rows("
		SELECT S.ST_NUMBER, L.LOAD_CODE, C.STATUS_STATE, L.CURRENT_STOP, S.SHIPMENT_CODE, S.SS_NUMBER
		FROM EXP_LOAD L, EXP_SHIPMENT S, EXP_STOP ST, EXP_STATUS_CODES C
		WHERE 
        L.LOAD_CODE = ST.LOAD_CODE AND ST.SHIPMENT = S.SHIPMENT_CODE
		AND COALESCE(S.ST_NUMBER, '') != ''
		AND ST.IM_STOP_TYPE is NULL
		AND L.CURRENT_STATUS = C.STATUS_CODES_CODE
		AND C.BEHAVIOR NOT IN ('complete', 'approved', 'oapproved', 'billed', 'cancel')
        GROUP BY S.ST_NUMBER");
        
        //AND ST.IM_STOP_TYPE != 'reposition'
		
	echo '	<h4>Containers in Transit</h4>
	
	<div class="table-responsive well well-sm">
	<table class="display table table-condensed table-bordered table-hover" id="ACTIVE">
	<thead><tr class="exspeedite-bg"><th>Container#</th>
		<th>Load#</th>
		<th>Status</th>
		<th>Stop#</th>
		<th>Shipment#</th>
	</tr>
	</thead>
	<tbody>';
	
	if( is_array($active) && count($active) > 0 ) {
		foreach($active as $row) {
			echo '<tr>
				<td>'.$row['ST_NUMBER'].'</td>
				<td>'.(isset($row['LOAD_CODE']) && $row['LOAD_CODE'] > 0 ?
				'<a href="exp_viewload.php?CODE='.$row['LOAD_CODE'].'">'.$row['LOAD_CODE'].'</a>' : '').'</td>
				<td>'.$row['STATUS_STATE'].'</td>
				<td>'.$row['CURRENT_STOP'].'</td>
				<td><a href="exp_addshipment.php?CODE='.$row['SHIPMENT_CODE'].'">'.$row['SHIPMENT_CODE'].
				(empty($row['SS_NUMBER']) ? '' : ' / '.$row['SS_NUMBER']).'</a></td>
			</tr>
			';
		}
	} else {
		echo '<td colspan="5">No data</td>';
	}
	echo '</tbody>
	</table>
	</div>
	';

	}
	
	//! Check if a container number is already in use
	public function in_use( $name, $shipment ) {
		$result = false;
		if( $this->debug ) echo "<p>".__METHOD__.": entry, name = $name</p>";
		$check1 = $this->database->get_multiple_rows("
			SELECT YARD_CONTAINER_CODE, STOP_TYPE,
			COALESCE((SELECT I.LABEL FROM EXP_CONTACT_INFO I
				WHERE EXP_YARD_CONTAINER.YARD_CODE = I.CONTACT_CODE
				AND I.CONTACT_TYPE IN ('dock', 'consignee', 'yard')
				AND (SELECT INTERMODAL FROM EXP_CLIENT WHERE CLIENT_CODE = I.CONTACT_CODE)
				AND I.ISDELETED = FALSE
				LIMIT 1),
				(SELECT C.CLIENT_NAME FROM EXP_CLIENT C
				WHERE C.CLIENT_CODE = EXP_YARD_CONTAINER.YARD_CODE)) AS LABEL
			FROM EXP_YARD_CONTAINER
			WHERE ST_NUMBER = '".$name."'
			AND SHIPMENT_CODE != $shipment");
			
		if( is_array($check1) && count($check1) > 0 ) {
			$result = 'Container# '.$name.' already in use'.
				(empty($check1[0]['STOP_TYPE']) ? '' : ' ('.$check1[0]['STOP_TYPE'].')').
				(empty($check1[0]['LABEL']) ? '' : ', in '.$check1[0]['LABEL']);
		} else {
			$check2 = $this->database->get_multiple_rows("
				SELECT L.LOAD_CODE
				FROM EXP_LOAD L, EXP_SHIPMENT S, EXP_STOP ST, EXP_STATUS_CODES C
				WHERE 
		        L.LOAD_CODE = ST.LOAD_CODE AND ST.SHIPMENT = S.SHIPMENT_CODE
				AND S.ST_NUMBER = '".$name."'
				AND L.CURRENT_STATUS = C.STATUS_CODES_CODE
				AND C.BEHAVIOR NOT IN ('complete', 'approved', 'oapproved', 'billed', 'cancel')
		        GROUP BY S.ST_NUMBER");
		        
			if( is_array($check2) && count($check2) > 0 ) {
				$result = 'Container# '.$name.' already in use'.
					(empty($check2[0]['LOAD_CODE']) ? '' : ', on load# '.$check2[0]['LOAD_CODE']);
			}
		}
		
		return $result;
	}
	
	public function delete_from_yard( $code ) {
		$this->delete_row( "YARD_CONTAINER_CODE = ".$code );
	}
}

?>
