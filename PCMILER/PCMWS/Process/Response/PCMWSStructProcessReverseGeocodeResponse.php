<?php
/**
 * File for class PCMWSStructProcessReverseGeocodeResponse
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructProcessReverseGeocodeResponse originally named ProcessReverseGeocodeResponse
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructProcessReverseGeocodeResponse extends PCMWSWsdlClass
{
    /**
     * The ProcessReverseGeocodeResult
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructGeocodeResponse
     */
    public $ProcessReverseGeocodeResult;
    /**
     * Constructor method for ProcessReverseGeocodeResponse
     * @see parent::__construct()
     * @param PCMWSStructGeocodeResponse $_processReverseGeocodeResult
     * @return PCMWSStructProcessReverseGeocodeResponse
     */
    public function __construct($_processReverseGeocodeResult = NULL)
    {
        parent::__construct(array('ProcessReverseGeocodeResult'=>$_processReverseGeocodeResult),false);
    }
    /**
     * Get ProcessReverseGeocodeResult value
     * @return PCMWSStructGeocodeResponse|null
     */
    public function getProcessReverseGeocodeResult()
    {
        return $this->ProcessReverseGeocodeResult;
    }
    /**
     * Set ProcessReverseGeocodeResult value
     * @param PCMWSStructGeocodeResponse $_processReverseGeocodeResult the ProcessReverseGeocodeResult
     * @return PCMWSStructGeocodeResponse
     */
    public function setProcessReverseGeocodeResult($_processReverseGeocodeResult)
    {
        return ($this->ProcessReverseGeocodeResult = $_processReverseGeocodeResult);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructProcessReverseGeocodeResponse
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
