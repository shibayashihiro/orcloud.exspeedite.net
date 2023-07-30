<?php
/**
 * File for class PCMWSStructGeocodeResponse
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructGeocodeResponse originally named GeocodeResponse
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructGeocodeResponse extends PCMWSWsdlClass
{
    /**
     * The Header
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructResponseHeader
     */
    public $Header;
    /**
     * The Body
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructGeocodeResponseBody
     */
    public $Body;
    /**
     * Constructor method for GeocodeResponse
     * @see parent::__construct()
     * @param PCMWSStructResponseHeader $_header
     * @param PCMWSStructGeocodeResponseBody $_body
     * @return PCMWSStructGeocodeResponse
     */
    public function __construct($_header = NULL,$_body = NULL)
    {
        parent::__construct(array('Header'=>$_header,'Body'=>$_body),false);
    }
    /**
     * Get Header value
     * @return PCMWSStructResponseHeader|null
     */
    public function getHeader()
    {
        return $this->Header;
    }
    /**
     * Set Header value
     * @param PCMWSStructResponseHeader $_header the Header
     * @return PCMWSStructResponseHeader
     */
    public function setHeader($_header)
    {
        return ($this->Header = $_header);
    }
    /**
     * Get Body value
     * @return PCMWSStructGeocodeResponseBody|null
     */
    public function getBody()
    {
        return $this->Body;
    }
    /**
     * Set Body value
     * @param PCMWSStructGeocodeResponseBody $_body the Body
     * @return PCMWSStructGeocodeResponseBody
     */
    public function setBody($_body)
    {
        return ($this->Body = $_body);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructGeocodeResponse
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
