<?php
/**
 * File for class PCMWSStructGetAvoidFavorSetRequestBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructGetAvoidFavorSetRequestBody originally named GetAvoidFavorSetRequestBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructGetAvoidFavorSetRequestBody extends PCMWSStructCustomDataSetRequestBody
{
    /**
     * The Detail
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $Detail;
    /**
     * Constructor method for GetAvoidFavorSetRequestBody
     * @see parent::__construct()
     * @param boolean $_detail
     * @return PCMWSStructGetAvoidFavorSetRequestBody
     */
    public function __construct($_detail = NULL)
    {
        PCMWSWsdlClass::__construct(array('Detail'=>$_detail),false);
    }
    /**
     * Get Detail value
     * @return boolean|null
     */
    public function getDetail()
    {
        return $this->Detail;
    }
    /**
     * Set Detail value
     * @param boolean $_detail the Detail
     * @return boolean
     */
    public function setDetail($_detail)
    {
        return ($this->Detail = $_detail);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructGetAvoidFavorSetRequestBody
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
