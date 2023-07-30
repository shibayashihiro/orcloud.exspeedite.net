<?php
/**
 * File for class PCMWSStructCalculateMilesReport
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructCalculateMilesReport originally named CalculateMilesReport
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructCalculateMilesReport extends PCMWSStructReport
{
    /**
     * The TMiles
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var double
     */
    public $TMiles;
    /**
     * Constructor method for CalculateMilesReport
     * @see parent::__construct()
     * @param double $_tMiles
     * @return PCMWSStructCalculateMilesReport
     */
    public function __construct($_tMiles = NULL)
    {
        PCMWSWsdlClass::__construct(array('TMiles'=>$_tMiles),false);
    }
    /**
     * Get TMiles value
     * @return double|null
     */
    public function getTMiles()
    {
        return $this->TMiles;
    }
    /**
     * Set TMiles value
     * @param double $_tMiles the TMiles
     * @return double
     */
    public function setTMiles($_tMiles)
    {
        return ($this->TMiles = $_tMiles);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructCalculateMilesReport
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
