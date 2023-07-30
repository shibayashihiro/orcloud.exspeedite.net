<?php
/**
 * File for class PCMWSStructAFSetResponseBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructAFSetResponseBody originally named AFSetResponseBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructAFSetResponseBody extends PCMWSWsdlClass
{
    /**
     * The AvoidFavorSets
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfAFSet
     */
    public $AvoidFavorSets;
    /**
     * Constructor method for AFSetResponseBody
     * @see parent::__construct()
     * @param PCMWSStructArrayOfAFSet $_avoidFavorSets
     * @return PCMWSStructAFSetResponseBody
     */
    public function __construct($_avoidFavorSets = NULL)
    {
        parent::__construct(array('AvoidFavorSets'=>($_avoidFavorSets instanceof PCMWSStructArrayOfAFSet)?$_avoidFavorSets:new PCMWSStructArrayOfAFSet($_avoidFavorSets)),false);
    }
    /**
     * Get AvoidFavorSets value
     * @return PCMWSStructArrayOfAFSet|null
     */
    public function getAvoidFavorSets()
    {
        return $this->AvoidFavorSets;
    }
    /**
     * Set AvoidFavorSets value
     * @param PCMWSStructArrayOfAFSet $_avoidFavorSets the AvoidFavorSets
     * @return PCMWSStructArrayOfAFSet
     */
    public function setAvoidFavorSets($_avoidFavorSets)
    {
        return ($this->AvoidFavorSets = $_avoidFavorSets);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructAFSetResponseBody
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
