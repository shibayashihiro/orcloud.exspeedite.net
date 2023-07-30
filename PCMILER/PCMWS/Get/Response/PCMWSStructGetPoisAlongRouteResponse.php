<?php
/**
 * File for class PCMWSStructGetPoisAlongRouteResponse
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructGetPoisAlongRouteResponse originally named GetPoisAlongRouteResponse
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructGetPoisAlongRouteResponse extends PCMWSWsdlClass
{
    /**
     * The GetPoisAlongRouteResult
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructPoisAlongRouteResponse
     */
    public $GetPoisAlongRouteResult;
    /**
     * Constructor method for GetPoisAlongRouteResponse
     * @see parent::__construct()
     * @param PCMWSStructPoisAlongRouteResponse $_getPoisAlongRouteResult
     * @return PCMWSStructGetPoisAlongRouteResponse
     */
    public function __construct($_getPoisAlongRouteResult = NULL)
    {
        parent::__construct(array('GetPoisAlongRouteResult'=>$_getPoisAlongRouteResult),false);
    }
    /**
     * Get GetPoisAlongRouteResult value
     * @return PCMWSStructPoisAlongRouteResponse|null
     */
    public function getGetPoisAlongRouteResult()
    {
        return $this->GetPoisAlongRouteResult;
    }
    /**
     * Set GetPoisAlongRouteResult value
     * @param PCMWSStructPoisAlongRouteResponse $_getPoisAlongRouteResult the GetPoisAlongRouteResult
     * @return PCMWSStructPoisAlongRouteResponse
     */
    public function setGetPoisAlongRouteResult($_getPoisAlongRouteResult)
    {
        return ($this->GetPoisAlongRouteResult = $_getPoisAlongRouteResult);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructGetPoisAlongRouteResponse
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
