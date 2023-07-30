<?php
/**
 * File for class PCMWSStructSetRoadSpeedsResponse
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructSetRoadSpeedsResponse originally named SetRoadSpeedsResponse
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructSetRoadSpeedsResponse extends PCMWSWsdlClass
{
    /**
     * The SetRoadSpeedsResult
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructResponse
     */
    public $SetRoadSpeedsResult;
    /**
     * Constructor method for SetRoadSpeedsResponse
     * @see parent::__construct()
     * @param PCMWSStructResponse $_setRoadSpeedsResult
     * @return PCMWSStructSetRoadSpeedsResponse
     */
    public function __construct($_setRoadSpeedsResult = NULL)
    {
        parent::__construct(array('SetRoadSpeedsResult'=>$_setRoadSpeedsResult),false);
    }
    /**
     * Get SetRoadSpeedsResult value
     * @return PCMWSStructResponse|null
     */
    public function getSetRoadSpeedsResult()
    {
        return $this->SetRoadSpeedsResult;
    }
    /**
     * Set SetRoadSpeedsResult value
     * @param PCMWSStructResponse $_setRoadSpeedsResult the SetRoadSpeedsResult
     * @return PCMWSStructResponse
     */
    public function setSetRoadSpeedsResult($_setRoadSpeedsResult)
    {
        return ($this->SetRoadSpeedsResult = $_setRoadSpeedsResult);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructSetRoadSpeedsResponse
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
