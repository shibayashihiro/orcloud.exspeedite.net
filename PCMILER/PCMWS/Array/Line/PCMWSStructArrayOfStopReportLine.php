<?php
/**
 * File for class PCMWSStructArrayOfStopReportLine
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfStopReportLine originally named ArrayOfStopReportLine
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfStopReportLine extends PCMWSWsdlClass
{
    /**
     * The StopReportLine
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructStopReportLine
     */
    public $StopReportLine;
    /**
     * Constructor method for ArrayOfStopReportLine
     * @see parent::__construct()
     * @param PCMWSStructStopReportLine $_stopReportLine
     * @return PCMWSStructArrayOfStopReportLine
     */
    public function __construct($_stopReportLine = NULL)
    {
        parent::__construct(array('StopReportLine'=>$_stopReportLine),false);
    }
    /**
     * Get StopReportLine value
     * @return PCMWSStructStopReportLine|null
     */
    public function getStopReportLine()
    {
        return $this->StopReportLine;
    }
    /**
     * Set StopReportLine value
     * @param PCMWSStructStopReportLine $_stopReportLine the StopReportLine
     * @return PCMWSStructStopReportLine
     */
    public function setStopReportLine($_stopReportLine)
    {
        return ($this->StopReportLine = $_stopReportLine);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructStopReportLine
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructStopReportLine
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructStopReportLine
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructStopReportLine
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructStopReportLine
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string StopReportLine
     */
    public function getAttributeName()
    {
        return 'StopReportLine';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfStopReportLine
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
