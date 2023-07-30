<?php
/**
 * File for class PCMWSStructArrayOfDetailReportLeg
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfDetailReportLeg originally named ArrayOfDetailReportLeg
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfDetailReportLeg extends PCMWSWsdlClass
{
    /**
     * The DetailReportLeg
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructDetailReportLeg
     */
    public $DetailReportLeg;
    /**
     * Constructor method for ArrayOfDetailReportLeg
     * @see parent::__construct()
     * @param PCMWSStructDetailReportLeg $_detailReportLeg
     * @return PCMWSStructArrayOfDetailReportLeg
     */
    public function __construct($_detailReportLeg = NULL)
    {
        parent::__construct(array('DetailReportLeg'=>$_detailReportLeg),false);
    }
    /**
     * Get DetailReportLeg value
     * @return PCMWSStructDetailReportLeg|null
     */
    public function getDetailReportLeg()
    {
        return $this->DetailReportLeg;
    }
    /**
     * Set DetailReportLeg value
     * @param PCMWSStructDetailReportLeg $_detailReportLeg the DetailReportLeg
     * @return PCMWSStructDetailReportLeg
     */
    public function setDetailReportLeg($_detailReportLeg)
    {
        return ($this->DetailReportLeg = $_detailReportLeg);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructDetailReportLeg
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructDetailReportLeg
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructDetailReportLeg
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructDetailReportLeg
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructDetailReportLeg
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string DetailReportLeg
     */
    public function getAttributeName()
    {
        return 'DetailReportLeg';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfDetailReportLeg
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
