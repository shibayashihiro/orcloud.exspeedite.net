<?php
/**
 * File for class PCMWSStructGetAvoidFavorResponse
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructGetAvoidFavorResponse originally named GetAvoidFavorResponse
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructGetAvoidFavorResponse extends PCMWSWsdlClass
{
    /**
     * The GetAvoidFavorResult
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructAvoidFavorResponse
     */
    public $GetAvoidFavorResult;
    /**
     * Constructor method for GetAvoidFavorResponse
     * @see parent::__construct()
     * @param PCMWSStructAvoidFavorResponse $_getAvoidFavorResult
     * @return PCMWSStructGetAvoidFavorResponse
     */
    public function __construct($_getAvoidFavorResult = NULL)
    {
        parent::__construct(array('GetAvoidFavorResult'=>$_getAvoidFavorResult),false);
    }
    /**
     * Get GetAvoidFavorResult value
     * @return PCMWSStructAvoidFavorResponse|null
     */
    public function getGetAvoidFavorResult()
    {
        return $this->GetAvoidFavorResult;
    }
    /**
     * Set GetAvoidFavorResult value
     * @param PCMWSStructAvoidFavorResponse $_getAvoidFavorResult the GetAvoidFavorResult
     * @return PCMWSStructAvoidFavorResponse
     */
    public function setGetAvoidFavorResult($_getAvoidFavorResult)
    {
        return ($this->GetAvoidFavorResult = $_getAvoidFavorResult);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructGetAvoidFavorResponse
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
