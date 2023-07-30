<?php
/**
 * File for class PCMWSStructArrayOfMapPointInfo
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfMapPointInfo originally named ArrayOfMapPointInfo
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfMapPointInfo extends PCMWSWsdlClass
{
    /**
     * The MapPointInfo
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructMapPointInfo
     */
    public $MapPointInfo;
    /**
     * Constructor method for ArrayOfMapPointInfo
     * @see parent::__construct()
     * @param PCMWSStructMapPointInfo $_mapPointInfo
     * @return PCMWSStructArrayOfMapPointInfo
     */
    public function __construct($_mapPointInfo = NULL)
    {
        parent::__construct(array('MapPointInfo'=>$_mapPointInfo),false);
    }
    /**
     * Get MapPointInfo value
     * @return PCMWSStructMapPointInfo|null
     */
    public function getMapPointInfo()
    {
        return $this->MapPointInfo;
    }
    /**
     * Set MapPointInfo value
     * @param PCMWSStructMapPointInfo $_mapPointInfo the MapPointInfo
     * @return PCMWSStructMapPointInfo
     */
    public function setMapPointInfo($_mapPointInfo)
    {
        return ($this->MapPointInfo = $_mapPointInfo);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructMapPointInfo
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructMapPointInfo
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructMapPointInfo
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructMapPointInfo
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructMapPointInfo
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string MapPointInfo
     */
    public function getAttributeName()
    {
        return 'MapPointInfo';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfMapPointInfo
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
