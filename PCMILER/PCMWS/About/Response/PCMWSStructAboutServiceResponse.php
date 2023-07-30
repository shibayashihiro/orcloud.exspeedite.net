<?php
/**
 * File for class PCMWSStructAboutServiceResponse
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructAboutServiceResponse originally named AboutServiceResponse
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructAboutServiceResponse extends PCMWSWsdlClass
{
    /**
     * The AboutServiceResult
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructResponse
     */
    public $AboutServiceResult;
    /**
     * Constructor method for AboutServiceResponse
     * @see parent::__construct()
     * @param PCMWSStructResponse $_aboutServiceResult
     * @return PCMWSStructAboutServiceResponse
     */
    public function __construct($_aboutServiceResult = NULL)
    {
        parent::__construct(array('AboutServiceResult'=>$_aboutServiceResult),false);
    }
    /**
     * Get AboutServiceResult value
     * @return PCMWSStructResponse|null
     */
    public function getAboutServiceResult()
    {
        return $this->AboutServiceResult;
    }
    /**
     * Set AboutServiceResult value
     * @param PCMWSStructResponse $_aboutServiceResult the AboutServiceResult
     * @return PCMWSStructResponse
     */
    public function setAboutServiceResult($_aboutServiceResult)
    {
        return ($this->AboutServiceResult = $_aboutServiceResult);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructAboutServiceResponse
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
