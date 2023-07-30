<?php
/**
 * File for class PCMWSStructGetRouteMatrixResponse
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructGetRouteMatrixResponse originally named GetRouteMatrixResponse
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructGetRouteMatrixResponse extends PCMWSWsdlClass
{
    /**
     * The GetRouteMatrixResult
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructRouteMatrixResponse
     */
    public $GetRouteMatrixResult;
    /**
     * Constructor method for GetRouteMatrixResponse
     * @see parent::__construct()
     * @param PCMWSStructRouteMatrixResponse $_getRouteMatrixResult
     * @return PCMWSStructGetRouteMatrixResponse
     */
    public function __construct($_getRouteMatrixResult = NULL)
    {
        parent::__construct(array('GetRouteMatrixResult'=>$_getRouteMatrixResult),false);
    }
    /**
     * Get GetRouteMatrixResult value
     * @return PCMWSStructRouteMatrixResponse|null
     */
    public function getGetRouteMatrixResult()
    {
        return $this->GetRouteMatrixResult;
    }
    /**
     * Set GetRouteMatrixResult value
     * @param PCMWSStructRouteMatrixResponse $_getRouteMatrixResult the GetRouteMatrixResult
     * @return PCMWSStructRouteMatrixResponse
     */
    public function setGetRouteMatrixResult($_getRouteMatrixResult)
    {
        return ($this->GetRouteMatrixResult = $_getRouteMatrixResult);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructGetRouteMatrixResponse
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
