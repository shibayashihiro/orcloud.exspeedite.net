<?php
/**
 * File for class PCMWSStructArrayOfDrawerType
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfDrawerType originally named ArrayOfDrawerType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfDrawerType extends PCMWSWsdlClass
{
    /**
     * The DrawerType
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * @var PCMWSEnumDrawerType
     */
    public $DrawerType;
    /**
     * Constructor method for ArrayOfDrawerType
     * @see parent::__construct()
     * @param PCMWSEnumDrawerType $_drawerType
     * @return PCMWSStructArrayOfDrawerType
     */
    public function __construct($_drawerType = NULL)
    {
        parent::__construct(array('DrawerType'=>$_drawerType),false);
    }
    /**
     * Get DrawerType value
     * @return PCMWSEnumDrawerType|null
     */
    public function getDrawerType()
    {
        return $this->DrawerType;
    }
    /**
     * Set DrawerType value
     * @param PCMWSEnumDrawerType $_drawerType the DrawerType
     * @return PCMWSEnumDrawerType
     */
    public function setDrawerType($_drawerType)
    {
        return ($this->DrawerType = $_drawerType);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSEnumDrawerType
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSEnumDrawerType
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSEnumDrawerType
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSEnumDrawerType
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSEnumDrawerType
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Add element to array
     * @see PCMWSWsdlClass::add()
     * @uses PCMWSEnumDrawerType::valueIsValid()
     * @param PCMWSEnumDrawerType $_item
     * @return PCMWSEnumDrawerType
     */
    public function add($_item)
    {
        return PCMWSEnumDrawerType::valueIsValid($_item)?parent::add($_item):false;
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string DrawerType
     */
    public function getAttributeName()
    {
        return 'DrawerType';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfDrawerType
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
