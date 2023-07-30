<?php
/**
 * File for class PCMWSStructGetCustomPlacesResponse
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructGetCustomPlacesResponse originally named GetCustomPlacesResponse
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructGetCustomPlacesResponse extends PCMWSWsdlClass
{
    /**
     * The GetCustomPlacesResult
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructCustomPlaceResponse
     */
    public $GetCustomPlacesResult;
    /**
     * Constructor method for GetCustomPlacesResponse
     * @see parent::__construct()
     * @param PCMWSStructCustomPlaceResponse $_getCustomPlacesResult
     * @return PCMWSStructGetCustomPlacesResponse
     */
    public function __construct($_getCustomPlacesResult = NULL)
    {
        parent::__construct(array('GetCustomPlacesResult'=>$_getCustomPlacesResult),false);
    }
    /**
     * Get GetCustomPlacesResult value
     * @return PCMWSStructCustomPlaceResponse|null
     */
    public function getGetCustomPlacesResult()
    {
        return $this->GetCustomPlacesResult;
    }
    /**
     * Set GetCustomPlacesResult value
     * @param PCMWSStructCustomPlaceResponse $_getCustomPlacesResult the GetCustomPlacesResult
     * @return PCMWSStructCustomPlaceResponse
     */
    public function setGetCustomPlacesResult($_getCustomPlacesResult)
    {
        return ($this->GetCustomPlacesResult = $_getCustomPlacesResult);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructGetCustomPlacesResponse
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
