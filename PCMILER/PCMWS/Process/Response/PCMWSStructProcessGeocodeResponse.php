<?php
/**
 * File for class PCMWSStructProcessGeocodeResponse
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructProcessGeocodeResponse originally named ProcessGeocodeResponse
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructProcessGeocodeResponse extends PCMWSWsdlClass
{
    /**
     * The ProcessGeocodeResult
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructGeocodeResponse
     */
    public $ProcessGeocodeResult;
    /**
     * Constructor method for ProcessGeocodeResponse
     * @see parent::__construct()
     * @param PCMWSStructGeocodeResponse $_processGeocodeResult
     * @return PCMWSStructProcessGeocodeResponse
     */
    public function __construct($_processGeocodeResult = NULL)
    {
        parent::__construct(array('ProcessGeocodeResult'=>$_processGeocodeResult),false);
    }
    /**
     * Get ProcessGeocodeResult value
     * @return PCMWSStructGeocodeResponse|null
     */
    public function getProcessGeocodeResult()
    {
        return $this->ProcessGeocodeResult;
    }
    /**
     * Set ProcessGeocodeResult value
     * @param PCMWSStructGeocodeResponse $_processGeocodeResult the ProcessGeocodeResult
     * @return PCMWSStructGeocodeResponse
     */
    public function setProcessGeocodeResult($_processGeocodeResult)
    {
        return ($this->ProcessGeocodeResult = $_processGeocodeResult);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructProcessGeocodeResponse
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
