<?php
/**
 * File for class PCMWSStructSetCustomPlacesResponse
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructSetCustomPlacesResponse originally named SetCustomPlacesResponse
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructSetCustomPlacesResponse extends PCMWSWsdlClass
{
    /**
     * The SetCustomPlacesResult
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructResponse
     */
    public $SetCustomPlacesResult;
    /**
     * Constructor method for SetCustomPlacesResponse
     * @see parent::__construct()
     * @param PCMWSStructResponse $_setCustomPlacesResult
     * @return PCMWSStructSetCustomPlacesResponse
     */
    public function __construct($_setCustomPlacesResult = NULL)
    {
        parent::__construct(array('SetCustomPlacesResult'=>$_setCustomPlacesResult),false);
    }
    /**
     * Get SetCustomPlacesResult value
     * @return PCMWSStructResponse|null
     */
    public function getSetCustomPlacesResult()
    {
        return $this->SetCustomPlacesResult;
    }
    /**
     * Set SetCustomPlacesResult value
     * @param PCMWSStructResponse $_setCustomPlacesResult the SetCustomPlacesResult
     * @return PCMWSStructResponse
     */
    public function setSetCustomPlacesResult($_setCustomPlacesResult)
    {
        return ($this->SetCustomPlacesResult = $_setCustomPlacesResult);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructSetCustomPlacesResponse
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
