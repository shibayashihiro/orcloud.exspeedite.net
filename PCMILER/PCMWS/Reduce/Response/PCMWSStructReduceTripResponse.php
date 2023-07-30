<?php
/**
 * File for class PCMWSStructReduceTripResponse
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructReduceTripResponse originally named ReduceTripResponse
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructReduceTripResponse extends PCMWSWsdlClass
{
    /**
     * The ReduceTripResult
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructReportResponse
     */
    public $ReduceTripResult;
    /**
     * Constructor method for ReduceTripResponse
     * @see parent::__construct()
     * @param PCMWSStructReportResponse $_reduceTripResult
     * @return PCMWSStructReduceTripResponse
     */
    public function __construct($_reduceTripResult = NULL)
    {
        parent::__construct(array('ReduceTripResult'=>$_reduceTripResult),false);
    }
    /**
     * Get ReduceTripResult value
     * @return PCMWSStructReportResponse|null
     */
    public function getReduceTripResult()
    {
        return $this->ReduceTripResult;
    }
    /**
     * Set ReduceTripResult value
     * @param PCMWSStructReportResponse $_reduceTripResult the ReduceTripResult
     * @return PCMWSStructReportResponse
     */
    public function setReduceTripResult($_reduceTripResult)
    {
        return ($this->ReduceTripResult = $_reduceTripResult);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructReduceTripResponse
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
