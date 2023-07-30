<?php
/**
 * File for class PCMWSStructStateReportType
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructStateReportType originally named StateReportType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructStateReportType extends PCMWSStructReportType
{
    /**
     * The SortByRoute
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $SortByRoute;
    /**
     * Constructor method for StateReportType
     * @see parent::__construct()
     * @param boolean $_sortByRoute
     * @return PCMWSStructStateReportType
     */
    public function __construct($_sortByRoute = NULL)
    {
        PCMWSWsdlClass::__construct(array('SortByRoute'=>$_sortByRoute),false);
    }
    /**
     * Get SortByRoute value
     * @return boolean|null
     */
    public function getSortByRoute()
    {
        return $this->SortByRoute;
    }
    /**
     * Set SortByRoute value
     * @param boolean $_sortByRoute the SortByRoute
     * @return boolean
     */
    public function setSortByRoute($_sortByRoute)
    {
        return ($this->SortByRoute = $_sortByRoute);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructStateReportType
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
