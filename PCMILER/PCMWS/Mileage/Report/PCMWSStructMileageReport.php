<?php
/**
 * File for class PCMWSStructMileageReport
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructMileageReport originally named MileageReport
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructMileageReport extends PCMWSStructReport
{
    /**
     * The ReportLines
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfStopReportLine
     */
    public $ReportLines;
    /**
     * The TrafficDataUsed
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $TrafficDataUsed;
    /**
     * Constructor method for MileageReport
     * @see parent::__construct()
     * @param PCMWSStructArrayOfStopReportLine $_reportLines
     * @param boolean $_trafficDataUsed
     * @return PCMWSStructMileageReport
     */
    public function __construct($_reportLines = NULL,$_trafficDataUsed = NULL)
    {
        PCMWSWsdlClass::__construct(array('ReportLines'=>($_reportLines instanceof PCMWSStructArrayOfStopReportLine)?$_reportLines:new PCMWSStructArrayOfStopReportLine($_reportLines),'TrafficDataUsed'=>$_trafficDataUsed),false);
    }
    /**
     * Get ReportLines value
     * @return PCMWSStructArrayOfStopReportLine|null
     */
    public function getReportLines()
    {
        return $this->ReportLines;
    }
    /**
     * Set ReportLines value
     * @param PCMWSStructArrayOfStopReportLine $_reportLines the ReportLines
     * @return PCMWSStructArrayOfStopReportLine
     */
    public function setReportLines($_reportLines)
    {
        return ($this->ReportLines = $_reportLines);
    }
    /**
     * Get TrafficDataUsed value
     * @return boolean|null
     */
    public function getTrafficDataUsed()
    {
        return $this->TrafficDataUsed;
    }
    /**
     * Set TrafficDataUsed value
     * @param boolean $_trafficDataUsed the TrafficDataUsed
     * @return boolean
     */
    public function setTrafficDataUsed($_trafficDataUsed)
    {
        return ($this->TrafficDataUsed = $_trafficDataUsed);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructMileageReport
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
