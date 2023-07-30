<?php
/**
 * File for class PCMWSStructDirectionsReportType
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructDirectionsReportType originally named DirectionsReportType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructDirectionsReportType extends PCMWSStructReportType
{
    /**
     * The CondenseDirections
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $CondenseDirections;
    /**
     * Constructor method for DirectionsReportType
     * @see parent::__construct()
     * @param boolean $_condenseDirections
     * @return PCMWSStructDirectionsReportType
     */
    public function __construct($_condenseDirections = NULL)
    {
        PCMWSWsdlClass::__construct(array('CondenseDirections'=>$_condenseDirections),false);
    }
    /**
     * Get CondenseDirections value
     * @return boolean|null
     */
    public function getCondenseDirections()
    {
        return $this->CondenseDirections;
    }
    /**
     * Set CondenseDirections value
     * @param boolean $_condenseDirections the CondenseDirections
     * @return boolean
     */
    public function setCondenseDirections($_condenseDirections)
    {
        return ($this->CondenseDirections = $_condenseDirections);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructDirectionsReportType
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
