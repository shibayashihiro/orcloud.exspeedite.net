<?php
/**
 * File for class PCMWSStructArrayOfDirectionsReportLine
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfDirectionsReportLine originally named ArrayOfDirectionsReportLine
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfDirectionsReportLine extends PCMWSWsdlClass
{
    /**
     * The DirectionsReportLine
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructDirectionsReportLine
     */
    public $DirectionsReportLine;
    /**
     * Constructor method for ArrayOfDirectionsReportLine
     * @see parent::__construct()
     * @param PCMWSStructDirectionsReportLine $_directionsReportLine
     * @return PCMWSStructArrayOfDirectionsReportLine
     */
    public function __construct($_directionsReportLine = NULL)
    {
        parent::__construct(array('DirectionsReportLine'=>$_directionsReportLine),false);
    }
    /**
     * Get DirectionsReportLine value
     * @return PCMWSStructDirectionsReportLine|null
     */
    public function getDirectionsReportLine()
    {
        return $this->DirectionsReportLine;
    }
    /**
     * Set DirectionsReportLine value
     * @param PCMWSStructDirectionsReportLine $_directionsReportLine the DirectionsReportLine
     * @return PCMWSStructDirectionsReportLine
     */
    public function setDirectionsReportLine($_directionsReportLine)
    {
        return ($this->DirectionsReportLine = $_directionsReportLine);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructDirectionsReportLine
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructDirectionsReportLine
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructDirectionsReportLine
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructDirectionsReportLine
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructDirectionsReportLine
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string DirectionsReportLine
     */
    public function getAttributeName()
    {
        return 'DirectionsReportLine';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfDirectionsReportLine
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
