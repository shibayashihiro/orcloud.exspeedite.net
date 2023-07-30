<?php
/**
 * File for class PCMWSStructArrayOfPOIGenericType
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfPOIGenericType originally named ArrayOfPOIGenericType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfPOIGenericType extends PCMWSWsdlClass
{
    /**
     * The POIGenericType
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * @var PCMWSEnumPOIGenericType
     */
    public $POIGenericType;
    /**
     * Constructor method for ArrayOfPOIGenericType
     * @see parent::__construct()
     * @param PCMWSEnumPOIGenericType $_pOIGenericType
     * @return PCMWSStructArrayOfPOIGenericType
     */
    public function __construct($_pOIGenericType = NULL)
    {
        parent::__construct(array('POIGenericType'=>$_pOIGenericType),false);
    }
    /**
     * Get POIGenericType value
     * @return PCMWSEnumPOIGenericType|null
     */
    public function getPOIGenericType()
    {
        return $this->POIGenericType;
    }
    /**
     * Set POIGenericType value
     * @param PCMWSEnumPOIGenericType $_pOIGenericType the POIGenericType
     * @return PCMWSEnumPOIGenericType
     */
    public function setPOIGenericType($_pOIGenericType)
    {
        return ($this->POIGenericType = $_pOIGenericType);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSEnumPOIGenericType
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSEnumPOIGenericType
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSEnumPOIGenericType
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSEnumPOIGenericType
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSEnumPOIGenericType
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Add element to array
     * @see PCMWSWsdlClass::add()
     * @uses PCMWSEnumPOIGenericType::valueIsValid()
     * @param PCMWSEnumPOIGenericType $_item
     * @return PCMWSEnumPOIGenericType
     */
    public function add($_item)
    {
        return PCMWSEnumPOIGenericType::valueIsValid($_item)?parent::add($_item):false;
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string POIGenericType
     */
    public function getAttributeName()
    {
        return 'POIGenericType';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfPOIGenericType
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
