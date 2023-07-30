<?php
/**
 * File for class PCMWSStructGetReportsResponse
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructGetReportsResponse originally named GetReportsResponse
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructGetReportsResponse extends PCMWSWsdlClass
{
    /**
     * The GetReportsResult
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructReportResponse
     */
    public $GetReportsResult;
    /**
     * Constructor method for GetReportsResponse
     * @see parent::__construct()
     * @param PCMWSStructReportResponse $_getReportsResult
     * @return PCMWSStructGetReportsResponse
     */
    public function __construct($_getReportsResult = NULL)
    {
        parent::__construct(array('GetReportsResult'=>$_getReportsResult),false);
    }
    /**
     * Get GetReportsResult value
     * @return PCMWSStructReportResponse|null
     */
    public function getGetReportsResult()
    {
        return $this->GetReportsResult;
    }
    /**
     * Set GetReportsResult value
     * @param PCMWSStructReportResponse $_getReportsResult the GetReportsResult
     * @return PCMWSStructReportResponse
     */
    public function setGetReportsResult($_getReportsResult)
    {
        return ($this->GetReportsResult = $_getReportsResult);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructGetReportsResponse
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
