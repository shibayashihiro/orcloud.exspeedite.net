<?php
/**
 * File for class PCMWSStructArrayOfComparisonReportLine
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfComparisonReportLine originally named ArrayOfComparisonReportLine
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfComparisonReportLine extends PCMWSWsdlClass
{
    /**
     * The ComparisonReportLine
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructComparisonReportLine
     */
    public $ComparisonReportLine;
    /**
     * Constructor method for ArrayOfComparisonReportLine
     * @see parent::__construct()
     * @param PCMWSStructComparisonReportLine $_comparisonReportLine
     * @return PCMWSStructArrayOfComparisonReportLine
     */
    public function __construct($_comparisonReportLine = NULL)
    {
        parent::__construct(array('ComparisonReportLine'=>$_comparisonReportLine),false);
    }
    /**
     * Get ComparisonReportLine value
     * @return PCMWSStructComparisonReportLine|null
     */
    public function getComparisonReportLine()
    {
        return $this->ComparisonReportLine;
    }
    /**
     * Set ComparisonReportLine value
     * @param PCMWSStructComparisonReportLine $_comparisonReportLine the ComparisonReportLine
     * @return PCMWSStructComparisonReportLine
     */
    public function setComparisonReportLine($_comparisonReportLine)
    {
        return ($this->ComparisonReportLine = $_comparisonReportLine);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructComparisonReportLine
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructComparisonReportLine
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructComparisonReportLine
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructComparisonReportLine
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructComparisonReportLine
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string ComparisonReportLine
     */
    public function getAttributeName()
    {
        return 'ComparisonReportLine';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfComparisonReportLine
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
