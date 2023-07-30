<?php
/**
 * File for class PCMWSStructArrayOfRoadReportLine
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfRoadReportLine originally named ArrayOfRoadReportLine
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfRoadReportLine extends PCMWSWsdlClass
{
    /**
     * The RoadReportLine
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructRoadReportLine
     */
    public $RoadReportLine;
    /**
     * Constructor method for ArrayOfRoadReportLine
     * @see parent::__construct()
     * @param PCMWSStructRoadReportLine $_roadReportLine
     * @return PCMWSStructArrayOfRoadReportLine
     */
    public function __construct($_roadReportLine = NULL)
    {
        parent::__construct(array('RoadReportLine'=>$_roadReportLine),false);
    }
    /**
     * Get RoadReportLine value
     * @return PCMWSStructRoadReportLine|null
     */
    public function getRoadReportLine()
    {
        return $this->RoadReportLine;
    }
    /**
     * Set RoadReportLine value
     * @param PCMWSStructRoadReportLine $_roadReportLine the RoadReportLine
     * @return PCMWSStructRoadReportLine
     */
    public function setRoadReportLine($_roadReportLine)
    {
        return ($this->RoadReportLine = $_roadReportLine);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructRoadReportLine
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructRoadReportLine
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructRoadReportLine
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructRoadReportLine
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructRoadReportLine
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string RoadReportLine
     */
    public function getAttributeName()
    {
        return 'RoadReportLine';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfRoadReportLine
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
