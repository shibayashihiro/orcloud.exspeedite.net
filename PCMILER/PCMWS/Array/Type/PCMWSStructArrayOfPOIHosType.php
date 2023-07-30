<?php
/**
 * File for class PCMWSStructArrayOfPOIHosType
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfPOIHosType originally named ArrayOfPOIHosType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfPOIHosType extends PCMWSWsdlClass
{
    /**
     * The POIHosType
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * @var PCMWSEnumPOIHosType
     */
    public $POIHosType;
    /**
     * Constructor method for ArrayOfPOIHosType
     * @see parent::__construct()
     * @param PCMWSEnumPOIHosType $_pOIHosType
     * @return PCMWSStructArrayOfPOIHosType
     */
    public function __construct($_pOIHosType = NULL)
    {
        parent::__construct(array('POIHosType'=>$_pOIHosType),false);
    }
    /**
     * Get POIHosType value
     * @return PCMWSEnumPOIHosType|null
     */
    public function getPOIHosType()
    {
        return $this->POIHosType;
    }
    /**
     * Set POIHosType value
     * @param PCMWSEnumPOIHosType $_pOIHosType the POIHosType
     * @return PCMWSEnumPOIHosType
     */
    public function setPOIHosType($_pOIHosType)
    {
        return ($this->POIHosType = $_pOIHosType);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSEnumPOIHosType
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSEnumPOIHosType
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSEnumPOIHosType
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSEnumPOIHosType
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSEnumPOIHosType
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Add element to array
     * @see PCMWSWsdlClass::add()
     * @uses PCMWSEnumPOIHosType::valueIsValid()
     * @param PCMWSEnumPOIHosType $_item
     * @return PCMWSEnumPOIHosType
     */
    public function add($_item)
    {
        return PCMWSEnumPOIHosType::valueIsValid($_item)?parent::add($_item):false;
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string POIHosType
     */
    public function getAttributeName()
    {
        return 'POIHosType';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfPOIHosType
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
