<?php
/**
 * File for class PCMWSStructArrayOfMapGroupInfo
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfMapGroupInfo originally named ArrayOfMapGroupInfo
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfMapGroupInfo extends PCMWSWsdlClass
{
    /**
     * The MapGroupInfo
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructMapGroupInfo
     */
    public $MapGroupInfo;
    /**
     * Constructor method for ArrayOfMapGroupInfo
     * @see parent::__construct()
     * @param PCMWSStructMapGroupInfo $_mapGroupInfo
     * @return PCMWSStructArrayOfMapGroupInfo
     */
    public function __construct($_mapGroupInfo = NULL)
    {
        parent::__construct(array('MapGroupInfo'=>$_mapGroupInfo),false);
    }
    /**
     * Get MapGroupInfo value
     * @return PCMWSStructMapGroupInfo|null
     */
    public function getMapGroupInfo()
    {
        return $this->MapGroupInfo;
    }
    /**
     * Set MapGroupInfo value
     * @param PCMWSStructMapGroupInfo $_mapGroupInfo the MapGroupInfo
     * @return PCMWSStructMapGroupInfo
     */
    public function setMapGroupInfo($_mapGroupInfo)
    {
        return ($this->MapGroupInfo = $_mapGroupInfo);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructMapGroupInfo
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructMapGroupInfo
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructMapGroupInfo
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructMapGroupInfo
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructMapGroupInfo
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string MapGroupInfo
     */
    public function getAttributeName()
    {
        return 'MapGroupInfo';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfMapGroupInfo
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
