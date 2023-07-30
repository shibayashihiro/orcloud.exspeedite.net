<?php
/**
 * File for class PCMWSStructArrayOfRouteLabelOptions
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfRouteLabelOptions originally named ArrayOfRouteLabelOptions
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfRouteLabelOptions extends PCMWSWsdlClass
{
    /**
     * The RouteLabelOptions
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructRouteLabelOptions
     */
    public $RouteLabelOptions;
    /**
     * Constructor method for ArrayOfRouteLabelOptions
     * @see parent::__construct()
     * @param PCMWSStructRouteLabelOptions $_routeLabelOptions
     * @return PCMWSStructArrayOfRouteLabelOptions
     */
    public function __construct($_routeLabelOptions = NULL)
    {
        parent::__construct(array('RouteLabelOptions'=>$_routeLabelOptions),false);
    }
    /**
     * Get RouteLabelOptions value
     * @return PCMWSStructRouteLabelOptions|null
     */
    public function getRouteLabelOptions()
    {
        return $this->RouteLabelOptions;
    }
    /**
     * Set RouteLabelOptions value
     * @param PCMWSStructRouteLabelOptions $_routeLabelOptions the RouteLabelOptions
     * @return PCMWSStructRouteLabelOptions
     */
    public function setRouteLabelOptions($_routeLabelOptions)
    {
        return ($this->RouteLabelOptions = $_routeLabelOptions);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructRouteLabelOptions
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructRouteLabelOptions
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructRouteLabelOptions
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructRouteLabelOptions
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructRouteLabelOptions
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string RouteLabelOptions
     */
    public function getAttributeName()
    {
        return 'RouteLabelOptions';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfRouteLabelOptions
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
