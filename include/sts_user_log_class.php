<?php

// $Id: sts_user_log_class.php 4697 2022-03-09 23:02:23Z duncan $
// User log class

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );

class sts_user_log extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "LOG_CODE";
		if( $this->debug ) echo "<p>Create sts_user_log</p>";
		parent::__construct( $database, USER_LOG_TABLE, $debug);
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
    
    public function log_event( $event, $comments = false ) {
	    $result = false;
	    $choices = $this->get_enum_choices('LOG_EVENT');
	    if( is_array($choices) && in_array($event, $choices) ) {
		    $fields = array( 'USER_CODE' =>
		    	empty($_SESSION['EXT_USER_CODE']) ? 0 : $_SESSION['EXT_USER_CODE'],
		    	'LOG_EVENT' => $event );
		    if( $comments != false )
		    	$fields['COMMENTS'] = $this->trim_to_fit('COMMENTS', $comments);
		    if( ! empty($_SERVER["REMOTE_ADDR"]))
		    	$fields['IP_ADDRESS'] = $_SERVER["REMOTE_ADDR"];
		    
		    $result = $this->add($fields);
	    }
	    
	    return $result;
    }

     public function log_expired( $user_code, $comments = false ) {
	    if( ! empty($user_code) ) {
		    $fields = array( 'USER_CODE' =>  $user_code,
		    	'LOG_EVENT' => 'logout' );
		    if( $comments != false )
		    	$fields['COMMENTS'] = $comments;
		    
		    $this->add($fields);
	    }
    }

   //! create a menu of users
    public function user_menu( $selected = false, $id = 'LOG_USER', $match = '', $onchange = true, $any = false ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
		$select = false;

		$choices = $this->fetch_rows( $match, "DISTINCT USER_CODE, 
			(SELECT USERNAME FROM EXP_USER
			WHERE EXP_USER_LOG.USER_CODE = EXP_USER.USER_CODE) AS USERNAME",
			"USER_CODE ASC" );

		if( is_array($choices) && count($choices) > 0) {
			
			$select = '<select class="form-control input-sm" name="'.$id.'" id="'.$id.'"'.($onchange ? ' onchange="form.submit();"' : '').'>
			';
			if( $any ) {
				$select .= '<option value="0"';
				if( $selected && $selected == 0 )
					$select .= ' selected';
				$select .= '>All Users</option>
				';
			}
			foreach( $choices as $row ) {
				if( ! empty($row["USERNAME"])) {
					$select .= '<option value="'.$row["USER_CODE"].'"';
					if( $selected && $selected == $row["USER_CODE"] )
						$select .= ' selected';
					$select .= '>'.$row["USERNAME"].'</option>
					';
				}
			}
			$select .= '</select>';
		}
			
		return $select;
	}

    //! create a menu of events
    public function event_menu( $selected = false, $id = 'LOG_EVENT', $match = '', $onchange = true, $any = false ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
		$select = false;

		$choices = $this->get_enum_choices('LOG_EVENT');

		if( is_array($choices) && count($choices) > 0) {
			
			$select = '<select class="form-control input-sm" name="'.$id.'" id="'.$id.'"'.($onchange ? ' onchange="form.submit();"' : '').'>
			';
			if( $any ) {
				$select .= '<option value="all"';
				if( $selected && $selected == 'all' )
					$select .= ' selected';
				$select .= '>All Events</option>
				';
			}
			foreach( $choices as $event ) {
				$select .= '<option value="'.$event.'"';
				if( $selected && $selected == $event )
					$select .= ' selected';
				$select .= '>'.$event.'</option>
				';
			}
			$select .= '</select>';
		}
			
		return $select;
	}
	
	//! SCR# 652 - return the comments for a log entry
	public function get_ins_comments( $pk ) {
		$result = false;
		$check = $this->fetch_rows($this->primary_key." = ".$pk, "COMMENTS,
			(SELECT FULLNAME FROM EXP_USER 
			WHERE EXP_USER_LOG.USER_CODE = EXP_USER.USER_CODE
			LIMIT 1) AS FULLNAME");
		
		if( isset($check) && is_array($check) && count($check) == 1 &&
			is_array($check[0]) && 
			isset($check[0]["COMMENTS"]) ) {
			if( strpos($check[0]["COMMENTS"], 'General') !== false ||
				strpos($check[0]["COMMENTS"], 'Auto') !== false ||
				strpos($check[0]["COMMENTS"], 'Cargo') !== false ||
				strpos($check[0]["COMMENTS"], 'InsCurrency') !== false ) {
				
				$result = $check[0]["COMMENTS"];
				if( ! empty($check[0]["FULLNAME"])) {
					$result = '<p>User: '.$check[0]["FULLNAME"].'</p>
						<p>Event: '.$result.'</p>';
				}
			}
		}
		
		return $result;
	}

}

//! Layout Specifications - For use with sts_result

$sts_result_user_log_layout = array(
	'LOG_CODE' => array( 'format' => 'hidden' ),
	'CREATED_DATE' => array( 'label' => 'Date', 'format' => 'timestamp-s', 'length' => 80 ),
	'USER_CODE' => array( 'label' => 'User', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME',
		'link' => 'exp_edituser.php?CODE=' ),
	'LOG_EVENT' => array( 'label' => 'Event', 'format' => 'text' ),
	'IP_ADDRESS' => array( 'label' => 'IP', 'format' => 'text' ),
	'COMMENTS' => array( 'label' => 'Comments', 'format' => 'text' ),
);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_user_log_edit = array(
	'title' => '<span class="glyphicon glyphicon-user"></span> User Events',
	'sort' => 'CREATED_DATE desc',
);


?>
