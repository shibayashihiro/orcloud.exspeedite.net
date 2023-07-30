<?php
/**
 * File for class PCMWSStructArrayOfOutOfRouteReportLine
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfOutOfRouteReportLine originally named ArrayOfOutOfRouteReportLine
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfOutOfRouteReportLine extends PCMWSWsdlClass
{
    /**
     * The OutOfRouteReportLine
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructOutOfRouteReportLine
     */
    public $OutOfRouteReportLine;
    /**
     * Constructor method for ArrayOfOutOfRouteReportLine
     * @see parent::__construct()
     * @param PCMWSStructOutOfRouteReportLine $_outOfRouteReportLine
     * @return PCMWSStructArrayOfOutOfRouteReportLine
     */
    public function __construct($_outOfRouteReportLine = NULL)
    {
        parent::__construct(array('OutOfRouteReportLine'=>$_outOfRouteReportLine),false);
    }
    /**
     * Get OutOfRouteReportLine value
     * @return PCMWSStructOutOfRouteReportLine|null
     */
    public function getOutOfRouteReportLine()
    {
        return $this->OutOfRouteReportLine;
    }
    /**
     * Set OutOfRouteReportLine value
     * @param PCMWSStructOutOfRouteReportLine $_outOfRouteReportLine the OutOfRouteReportLine
     * @return PCMWSStructOutOfRouteReportLine
     */
    public function setOutOfRouteReportLine($_outOfRouteReportLine)
    {
        return ($this->OutOfRouteReportLine = $_outOfRouteReportLine);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructOutOfRouteReportLine
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructOutOfRouteReportLine
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructOutOfRouteReportLine
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructOutOfRouteReportLine
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructOutOfRouteReportLine
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string OutOfRouteReportLine
     */
    public function getAttributeName()
    {
        return 'OutOfRouteReportLine';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfOutOfRouteReportLine
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
