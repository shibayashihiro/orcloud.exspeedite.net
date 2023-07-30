<?php
/**
 * File for class PCMWSStructGetRoadSpeeds
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructGetRoadSpeeds originally named GetRoadSpeeds
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructGetRoadSpeeds extends PCMWSWsdlClass
{
    /**
     * The Request
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructRoadSpeedsRequest
     */
    public $Request;
    /**
     * Constructor method for GetRoadSpeeds
     * @see parent::__construct()
     * @param PCMWSStructRoadSpeedsRequest $_request
     * @return PCMWSStructGetRoadSpeeds
     */
    public function __construct($_request = NULL)
    {
        parent::__construct(array('Request'=>$_request),false);
    }
    /**
     * Get Request value
     * @return PCMWSStructRoadSpeedsRequest|null
     */
    public function getRequest()
    {
        return $this->Request;
    }
    /**
     * Set Request value
     * @param PCMWSStructRoadSpeedsRequest $_request the Request
     * @return PCMWSStructRoadSpeedsRequest
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
     * @return PCMWSStructGetRoadSpeeds
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
