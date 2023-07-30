<?php
/**
 * File for class PCMWSStructMileageReportType
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructMileageReportType originally named MileageReportType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructMileageReportType extends PCMWSStructReportType
{
    /**
     * The THoursWithSeconds
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $THoursWithSeconds;
    /**
     * Constructor method for MileageReportType
     * @see parent::__construct()
     * @param boolean $_tHoursWithSeconds
     * @return PCMWSStructMileageReportType
     */
    public function __construct($_tHoursWithSeconds = NULL)
    {
        PCMWSWsdlClass::__construct(array('THoursWithSeconds'=>$_tHoursWithSeconds),false);
    }
    /**
     * Get THoursWithSeconds value
     * @return boolean|null
     */
    public function getTHoursWithSeconds()
    {
        return $this->THoursWithSeconds;
    }
    /**
     * Set THoursWithSeconds value
     * @param boolean $_tHoursWithSeconds the THoursWithSeconds
     * @return boolean
     */
    public function setTHoursWithSeconds($_tHoursWithSeconds)
    {
        return ($this->THoursWithSeconds = $_tHoursWithSeconds);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructMileageReportType
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
