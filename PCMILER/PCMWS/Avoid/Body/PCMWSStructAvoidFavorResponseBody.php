<?php
/**
 * File for class PCMWSStructAvoidFavorResponseBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructAvoidFavorResponseBody originally named AvoidFavorResponseBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructAvoidFavorResponseBody extends PCMWSWsdlClass
{
    /**
     * The AvoidFavorList
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfAvoidFavor
     */
    public $AvoidFavorList;
    /**
     * Constructor method for AvoidFavorResponseBody
     * @see parent::__construct()
     * @param PCMWSStructArrayOfAvoidFavor $_avoidFavorList
     * @return PCMWSStructAvoidFavorResponseBody
     */
    public function __construct($_avoidFavorList = NULL)
    {
        parent::__construct(array('AvoidFavorList'=>($_avoidFavorList instanceof PCMWSStructArrayOfAvoidFavor)?$_avoidFavorList:new PCMWSStructArrayOfAvoidFavor($_avoidFavorList)),false);
    }
    /**
     * Get AvoidFavorList value
     * @return PCMWSStructArrayOfAvoidFavor|null
     */
    public function getAvoidFavorList()
    {
        return $this->AvoidFavorList;
    }
    /**
     * Set AvoidFavorList value
     * @param PCMWSStructArrayOfAvoidFavor $_avoidFavorList the AvoidFavorList
     * @return PCMWSStructArrayOfAvoidFavor
     */
    public function setAvoidFavorList($_avoidFavorList)
    {
        return ($this->AvoidFavorList = $_avoidFavorList);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructAvoidFavorResponseBody
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
