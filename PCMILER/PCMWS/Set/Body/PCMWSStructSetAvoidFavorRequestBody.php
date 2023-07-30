<?php
/**
 * File for class PCMWSStructSetAvoidFavorRequestBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructSetAvoidFavorRequestBody originally named SetAvoidFavorRequestBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructSetAvoidFavorRequestBody extends PCMWSWsdlClass
{
    /**
     * The ActionItems
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfAvoidFavorSet
     */
    public $ActionItems;
    /**
     * Constructor method for SetAvoidFavorRequestBody
     * @see parent::__construct()
     * @param PCMWSStructArrayOfAvoidFavorSet $_actionItems
     * @return PCMWSStructSetAvoidFavorRequestBody
     */
    public function __construct($_actionItems = NULL)
    {
        parent::__construct(array('ActionItems'=>($_actionItems instanceof PCMWSStructArrayOfAvoidFavorSet)?$_actionItems:new PCMWSStructArrayOfAvoidFavorSet($_actionItems)),false);
    }
    /**
     * Get ActionItems value
     * @return PCMWSStructArrayOfAvoidFavorSet|null
     */
    public function getActionItems()
    {
        return $this->ActionItems;
    }
    /**
     * Set ActionItems value
     * @param PCMWSStructArrayOfAvoidFavorSet $_actionItems the ActionItems
     * @return PCMWSStructArrayOfAvoidFavorSet
     */
    public function setActionItems($_actionItems)
    {
        return ($this->ActionItems = $_actionItems);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructSetAvoidFavorRequestBody
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
