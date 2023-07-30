<?php
/**
 * File for class PCMWSStructImportAvoidFavorSet
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructImportAvoidFavorSet originally named ImportAvoidFavorSet
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructImportAvoidFavorSet extends PCMWSWsdlClass
{
    /**
     * The Request
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructImportAFSetRequest
     */
    public $Request;
    /**
     * Constructor method for ImportAvoidFavorSet
     * @see parent::__construct()
     * @param PCMWSStructImportAFSetRequest $_request
     * @return PCMWSStructImportAvoidFavorSet
     */
    public function __construct($_request = NULL)
    {
        parent::__construct(array('Request'=>$_request),false);
    }
    /**
     * Get Request value
     * @return PCMWSStructImportAFSetRequest|null
     */
    public function getRequest()
    {
        return $this->Request;
    }
    /**
     * Set Request value
     * @param PCMWSStructImportAFSetRequest $_request the Request
     * @return PCMWSStructImportAFSetRequest
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
     * @return PCMWSStructImportAvoidFavorSet
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
