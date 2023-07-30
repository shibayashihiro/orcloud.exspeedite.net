<?php
/**
 * File for class PCMWSStructArrayOfStateCostReportLine
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfStateCostReportLine originally named ArrayOfStateCostReportLine
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfStateCostReportLine extends PCMWSWsdlClass
{
    /**
     * The StateCostReportLine
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructStateCostReportLine
     */
    public $StateCostReportLine;
    /**
     * Constructor method for ArrayOfStateCostReportLine
     * @see parent::__construct()
     * @param PCMWSStructStateCostReportLine $_stateCostReportLine
     * @return PCMWSStructArrayOfStateCostReportLine
     */
    public function __construct($_stateCostReportLine = NULL)
    {
        parent::__construct(array('StateCostReportLine'=>$_stateCostReportLine),false);
    }
    /**
     * Get StateCostReportLine value
     * @return PCMWSStructStateCostReportLine|null
     */
    public function getStateCostReportLine()
    {
        return $this->StateCostReportLine;
    }
    /**
     * Set StateCostReportLine value
     * @param PCMWSStructStateCostReportLine $_stateCostReportLine the StateCostReportLine
     * @return PCMWSStructStateCostReportLine
     */
    public function setStateCostReportLine($_stateCostReportLine)
    {
        return ($this->StateCostReportLine = $_stateCostReportLine);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructStateCostReportLine
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructStateCostReportLine
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructStateCostReportLine
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructStateCostReportLine
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructStateCostReportLine
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string StateCostReportLine
     */
    public function getAttributeName()
    {
        return 'StateCostReportLine';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfStateCostReportLine
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
