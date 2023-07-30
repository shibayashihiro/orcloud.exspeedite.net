<?php
/**
 * File for class PCMWSStructGetETAOutOfRouteReportResponse
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructGetETAOutOfRouteReportResponse originally named GetETAOutOfRouteReportResponse
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructGetETAOutOfRouteReportResponse extends PCMWSWsdlClass
{
    /**
     * The GetETAOutOfRouteReportResult
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructReportResponse
     */
    public $GetETAOutOfRouteReportResult;
    /**
     * Constructor method for GetETAOutOfRouteReportResponse
     * @see parent::__construct()
     * @param PCMWSStructReportResponse $_getETAOutOfRouteReportResult
     * @return PCMWSStructGetETAOutOfRouteReportResponse
     */
    public function __construct($_getETAOutOfRouteReportResult = NULL)
    {
        parent::__construct(array('GetETAOutOfRouteReportResult'=>$_getETAOutOfRouteReportResult),false);
    }
    /**
     * Get GetETAOutOfRouteReportResult value
     * @return PCMWSStructReportResponse|null
     */
    public function getGetETAOutOfRouteReportResult()
    {
        return $this->GetETAOutOfRouteReportResult;
    }
    /**
     * Set GetETAOutOfRouteReportResult value
     * @param PCMWSStructReportResponse $_getETAOutOfRouteReportResult the GetETAOutOfRouteReportResult
     * @return PCMWSStructReportResponse
     */
    public function setGetETAOutOfRouteReportResult($_getETAOutOfRouteReportResult)
    {
        return ($this->GetETAOutOfRouteReportResult = $_getETAOutOfRouteReportResult);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructGetETAOutOfRouteReportResponse
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
