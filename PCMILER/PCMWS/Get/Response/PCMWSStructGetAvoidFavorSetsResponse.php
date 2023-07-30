<?php
/**
 * File for class PCMWSStructGetAvoidFavorSetsResponse
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructGetAvoidFavorSetsResponse originally named GetAvoidFavorSetsResponse
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructGetAvoidFavorSetsResponse extends PCMWSWsdlClass
{
    /**
     * The GetAvoidFavorSetsResult
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructAFSetResponse
     */
    public $GetAvoidFavorSetsResult;
    /**
     * Constructor method for GetAvoidFavorSetsResponse
     * @see parent::__construct()
     * @param PCMWSStructAFSetResponse $_getAvoidFavorSetsResult
     * @return PCMWSStructGetAvoidFavorSetsResponse
     */
    public function __construct($_getAvoidFavorSetsResult = NULL)
    {
        parent::__construct(array('GetAvoidFavorSetsResult'=>$_getAvoidFavorSetsResult),false);
    }
    /**
     * Get GetAvoidFavorSetsResult value
     * @return PCMWSStructAFSetResponse|null
     */
    public function getGetAvoidFavorSetsResult()
    {
        return $this->GetAvoidFavorSetsResult;
    }
    /**
     * Set GetAvoidFavorSetsResult value
     * @param PCMWSStructAFSetResponse $_getAvoidFavorSetsResult the GetAvoidFavorSetsResult
     * @return PCMWSStructAFSetResponse
     */
    public function setGetAvoidFavorSetsResult($_getAvoidFavorSetsResult)
    {
        return ($this->GetAvoidFavorSetsResult = $_getAvoidFavorSetsResult);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructGetAvoidFavorSetsResponse
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
