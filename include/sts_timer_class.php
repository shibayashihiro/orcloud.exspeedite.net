<?php

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

class sts_timer
{
    private $start_time = NULL;
    private $end_time = NULL;

    private function getmicrotime()
    {
      list($usec, $sec) = explode(" ", microtime());
      return ((float)$usec + (float)$sec);
    }

    function start()
    {
      $this->start_time = $this->getmicrotime();
    }

    function stop()
    {
      $this->end_time = $this->getmicrotime();
    }

    function split()
    {
        if (is_null($this->start_time))
        {
            exit('Timer: start method not called !');
            return false;
        }
        return round(($this->getmicrotime() - $this->start_time), 4);
    }
    
    function result()
    {
        if (is_null($this->start_time))
        {
            exit('Timer: start method not called !');
            return false;
        }
        else if (is_null($this->end_time))
        {
            exit('Timer: stop method not called !');
            return false;
        }

        return round(($this->end_time - $this->start_time), 4);
    }

    # an alias of result function
    function time()
    {
        $this->result();
    }

}

if( ! function_exists('debug_log_timers') ) {
	function debug_log_timers( $msg, $connect, $query, $overall ) {
		echo "<p>$msg
			connect=".number_format((float) $connect,4)."s (".number_format((float) $connect/$overall*100,2)."%)
			query=".number_format((float) $query,4)."s (".number_format((float) $query/$overall*100,2)."%)
			overall=".number_format((float) $overall,4)."s (100%)</p>";
	}
}

?>