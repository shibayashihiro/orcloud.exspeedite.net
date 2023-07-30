<?php
/**
 * File for class PCMWSStructProcessRadiusSearch
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructProcessRadiusSearch originally named ProcessRadiusSearch
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructProcessRadiusSearch extends PCMWSWsdlClass
{
    /**
     * The Request
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructRadiusSearchRequest
     */
    public $Request;
    /**
     * Constructor method for ProcessRadiusSearch
     * @see parent::__construct()
     * @param PCMWSStructRadiusSearchRequest $_request
     * @return PCMWSStructProcessRadiusSearch
     */
    public function __construct($_request = NULL)
    {
        parent::__construct(array('Request'=>$_request),false);
    }
    /**
     * Get Request value
     * @return PCMWSStructRadiusSearchRequest|null
     */
    public function getRequest()
    {
        return $this->Request;
    }
    /**
     * Set Request value
     * @param PCMWSStructRadiusSearchRequest $_request the Request
     * @return PCMWSStructRadiusSearchRequest
     */
    public function setRequest($_request)
    {
        return ($this->Request = $_request);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructProcessRadiusSearch
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
