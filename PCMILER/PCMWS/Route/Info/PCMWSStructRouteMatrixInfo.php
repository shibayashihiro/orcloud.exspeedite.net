<?php
/**
 * File for class PCMWSStructRouteMatrixInfo
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructRouteMatrixInfo originally named RouteMatrixInfo
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructRouteMatrixInfo extends PCMWSWsdlClass
{
    /**
     * The Success
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $Success;
    /**
     * The Errors
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfError
     */
    public $Errors;
    /**
     * The Time
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Time;
    /**
     * The Distance
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Distance;
    /**
     * Constructor method for RouteMatrixInfo
     * @see parent::__construct()
     * @param boolean $_success
     * @param PCMWSStructArrayOfError $_errors
     * @param string $_time
     * @param string $_distance
     * @return PCMWSStructRouteMatrixInfo
     */
    public function __construct($_success = NULL,$_errors = NULL,$_time = NULL,$_distance = NULL)
    {
        parent::__construct(array('Success'=>$_success,'Errors'=>($_errors instanceof PCMWSStructArrayOfError)?$_errors:new PCMWSStructArrayOfError($_errors),'Time'=>$_time,'Distance'=>$_distance),false);
    }
    /**
     * Get Success value
     * @return boolean|null
     */
    public function getSuccess()
    {
        return $this->Success;
    }
    /**
     * Set Success value
     * @param boolean $_success the Success
     * @return boolean
     */
    public function setSuccess($_success)
    {
        return ($this->Success = $_success);
    }
    /**
     * Get Errors value
     * @return PCMWSStructArrayOfError|null
     */
    public function getErrors()
    {
        return $this->Errors;
    }
    /**
     * Set Errors value
     * @param PCMWSStructArrayOfError $_errors the Errors
     * @return PCMWSStructArrayOfError
     */
    public function setErrors($_errors)
    {
        return ($this->Errors = $_errors);
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
     * Get Distance value
     * @return string|null
     */
    public function getDistance()
    {
        return $this->Distance;
    }
    /**
     * Set Distance value
     * @param string $_distance the Distance
     * @return string
     */
    public function setDistance($_distance)
    {
        return ($this->Distance = $_distance);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructRouteMatrixInfo
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
