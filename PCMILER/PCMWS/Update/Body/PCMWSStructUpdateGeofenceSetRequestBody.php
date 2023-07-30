<?php
/**
 * File for class PCMWSStructUpdateGeofenceSetRequestBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructUpdateGeofenceSetRequestBody originally named UpdateGeofenceSetRequestBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructUpdateGeofenceSetRequestBody extends PCMWSStructAddGeofenceSetRequestBody
{
    /**
     * The FenceSet
     * Meta informations extracted from the WSDL
     * - nillable : true
     * @var anyType
     */
    public $FenceSet;
    /**
     * Constructor method for UpdateGeofenceSetRequestBody
     * @see parent::__construct()
     * @param anyType $_fenceSet
     * @return PCMWSStructUpdateGeofenceSetRequestBody
     */
    public function __construct($_fenceSet = NULL)
    {
        PCMWSWsdlClass::__construct(array('FenceSet'=>$_fenceSet),false);
    }
    /**
     * Get FenceSet value
     * @return anyType|null
     */
    public function getFenceSet()
    {
        return $this->FenceSet;
    }
    /**
     * Set FenceSet value
     * @param anyType $_fenceSet the FenceSet
     * @return anyType
     */
    public function setFenceSet($_fenceSet)
    {
        return ($this->FenceSet = $_fenceSet);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructUpdateGeofenceSetRequestBody
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
