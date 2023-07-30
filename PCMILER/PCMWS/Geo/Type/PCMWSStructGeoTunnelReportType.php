<?php
/**
 * File for class PCMWSStructGeoTunnelReportType
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructGeoTunnelReportType originally named GeoTunnelReportType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructGeoTunnelReportType extends PCMWSStructReportType
{
    /**
     * The CiteInterval
     * @var double
     */
    public $CiteInterval;
    /**
     * Constructor method for GeoTunnelReportType
     * @see parent::__construct()
     * @param double $_citeInterval
     * @return PCMWSStructGeoTunnelReportType
     */
    public function __construct($_citeInterval = NULL)
    {
        PCMWSWsdlClass::__construct(array('CiteInterval'=>$_citeInterval),false);
    }
    /**
     * Get CiteInterval value
     * @return double|null
     */
    public function getCiteInterval()
    {
        return $this->CiteInterval;
    }
    /**
     * Set CiteInterval value
     * @param double $_citeInterval the CiteInterval
     * @return double
     */
    public function setCiteInterval($_citeInterval)
    {
        return ($this->CiteInterval = $_citeInterval);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructGeoTunnelReportType
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
