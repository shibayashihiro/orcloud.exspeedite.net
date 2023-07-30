<?php
/**
 * File for class PCMWSStructGetRoadSpeedsResponse
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructGetRoadSpeedsResponse originally named GetRoadSpeedsResponse
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructGetRoadSpeedsResponse extends PCMWSWsdlClass
{
    /**
     * The GetRoadSpeedsResult
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructRoadSpeedsResponse
     */
    public $GetRoadSpeedsResult;
    /**
     * Constructor method for GetRoadSpeedsResponse
     * @see parent::__construct()
     * @param PCMWSStructRoadSpeedsResponse $_getRoadSpeedsResult
     * @return PCMWSStructGetRoadSpeedsResponse
     */
    public function __construct($_getRoadSpeedsResult = NULL)
    {
        parent::__construct(array('GetRoadSpeedsResult'=>$_getRoadSpeedsResult),false);
    }
    /**
     * Get GetRoadSpeedsResult value
     * @return PCMWSStructRoadSpeedsResponse|null
     */
    public function getGetRoadSpeedsResult()
    {
        return $this->GetRoadSpeedsResult;
    }
    /**
     * Set GetRoadSpeedsResult value
     * @param PCMWSStructRoadSpeedsResponse $_getRoadSpeedsResult the GetRoadSpeedsResult
     * @return PCMWSStructRoadSpeedsResponse
     */
    public function setGetRoadSpeedsResult($_getRoadSpeedsResult)
    {
        return ($this->GetRoadSpeedsResult = $_getRoadSpeedsResult);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructGetRoadSpeedsResponse
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
