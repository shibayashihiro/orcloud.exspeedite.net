<?php
/**
 * File for class PCMWSStructDirectionsReportLine
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructDirectionsReportLine originally named DirectionsReportLine
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructDirectionsReportLine extends PCMWSWsdlClass
{
    /**
     * The Warn
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Warn;
    /**
     * The Direction
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Direction;
    /**
     * The Dist
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Dist;
    /**
     * The Time
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Time;
    /**
     * The InterCh
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $InterCh;
    /**
     * The Delay
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Delay;
    /**
     * Constructor method for DirectionsReportLine
     * @see parent::__construct()
     * @param string $_warn
     * @param string $_direction
     * @param string $_dist
     * @param string $_time
     * @param string $_interCh
     * @param string $_delay
     * @return PCMWSStructDirectionsReportLine
     */
    public function __construct($_warn = NULL,$_direction = NULL,$_dist = NULL,$_time = NULL,$_interCh = NULL,$_delay = NULL)
    {
        parent::__construct(array('Warn'=>$_warn,'Direction'=>$_direction,'Dist'=>$_dist,'Time'=>$_time,'InterCh'=>$_interCh,'Delay'=>$_delay),false);
    }
    /**
     * Get Warn value
     * @return string|null
     */
    public function getWarn()
    {
        return $this->Warn;
    }
    /**
     * Set Warn value
     * @param string $_warn the Warn
     * @return string
     */
    public function setWarn($_warn)
    {
        return ($this->Warn = $_warn);
    }
    /**
     * Get Direction value
     * @return string|null
     */
    public function getDirection()
    {
        return $this->Direction;
    }
    /**
     * Set Direction value
     * @param string $_direction the Direction
     * @return string
     */
    public function setDirection($_direction)
    {
        return ($this->Direction = $_direction);
    }
    /**
     * Get Dist value
     * @return string|null
     */
    public function getDist()
    {
        return $this->Dist;
    }
    /**
     * Set Dist value
     * @param string $_dist the Dist
     * @return string
     */
    public function setDist($_dist)
    {
        return ($this->Dist = $_dist);
    }
    /**
     * Get Time value
     * @return string|null
     */
    public function getTime()
    {
        return $this->Time;
    }
    /**
     * Set Time value
     * @param string $_time the Time
     * @return string
     */
    public function setTime($_time)
    {
        return ($this->Time = $_time);
    }
    /**
     * Get InterCh value
     * @return string|null
     */
    public function getInterCh()
    {
        return $this->InterCh;
    }
    /**
     * Set InterCh value
     * @param string $_interCh the InterCh
     * @return string
     */
    public function setInterCh($_interCh)
    {
        return ($this->InterCh = $_interCh);
    }
    /**
     * Get Delay value
     * @return string|null
     */
    public function getDelay()
    {
        return $this->Delay;
    }
    /**
     * Set Delay value
     * @param string $_delay the Delay
     * @return string
     */
    public function setDelay($_delay)
    {
        return ($this->Delay = $_delay);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructDirectionsReportLine
     */
    public static function __set_state(array $_array)
    {
	    $_array[] = __CLASS__;
        return parent::__set_state($_array);
    }
    /**
     * Method returning the class name
     * @return string __CLASS__
     */
    public function __toString()
    {
        return __CLASS__;
    }
}
