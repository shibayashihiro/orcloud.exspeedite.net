<?php
/**
 * File for class PCMWSStructArrayOfDetailReportLine
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfDetailReportLine originally named ArrayOfDetailReportLine
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfDetailReportLine extends PCMWSWsdlClass
{
    /**
     * The DetailReportLine
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructDetailReportLine
     */
    public $DetailReportLine;
    /**
     * Constructor method for ArrayOfDetailReportLine
     * @see parent::__construct()
     * @param PCMWSStructDetailReportLine $_detailReportLine
     * @return PCMWSStructArrayOfDetailReportLine
     */
    public function __construct($_detailReportLine = NULL)
    {
        parent::__construct(array('DetailReportLine'=>$_detailReportLine),false);
    }
    /**
     * Get DetailReportLine value
     * @return PCMWSStructDetailReportLine|null
     */
    public function getDetailReportLine()
    {
        return $this->DetailReportLine;
    }
    /**
     * Set DetailReportLine value
     * @param PCMWSStructDetailReportLine $_detailReportLine the DetailReportLine
     * @return PCMWSStructDetailReportLine
     */
    public function setDetailReportLine($_detailReportLine)
    {
        return ($this->DetailReportLine = $_detailReportLine);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructDetailReportLine
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructDetailReportLine
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructDetailReportLine
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructDetailReportLine
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructDetailReportLine
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string DetailReportLine
     */
    public function getAttributeName()
    {
        return 'DetailReportLine';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfDetailReportLine
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
