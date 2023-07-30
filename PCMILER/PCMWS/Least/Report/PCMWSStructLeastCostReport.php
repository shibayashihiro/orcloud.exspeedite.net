<?php
/**
 * File for class PCMWSStructLeastCostReport
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructLeastCostReport originally named LeastCostReport
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructLeastCostReport extends PCMWSStructReport
{
    /**
     * The Origin
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructGeocodeOutputLocation
     */
    public $Origin;
    /**
     * The Destination
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructGeocodeOutputLocation
     */
    public $Destination;
    /**
     * The ReportLines
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfComparisonReportLine
     */
    public $ReportLines;
    /**
     * Constructor method for LeastCostReport
     * @see parent::__construct()
     * @param PCMWSStructGeocodeOutputLocation $_origin
     * @param PCMWSStructGeocodeOutputLocation $_destination
     * @param PCMWSStructArrayOfComparisonReportLine $_reportLines
     * @return PCMWSStructLeastCostReport
     */
    public function __construct($_origin = NULL,$_destination = NULL,$_reportLines = NULL)
    {
        PCMWSWsdlClass::__construct(array('Origin'=>$_origin,'Destination'=>$_destination,'ReportLines'=>($_reportLines instanceof PCMWSStructArrayOfComparisonReportLine)?$_reportLines:new PCMWSStructArrayOfComparisonReportLine($_reportLines)),false);
    }
    /**
     * Get Origin value
     * @return PCMWSStructGeocodeOutputLocation|null
     */
    public function getOrigin()
    {
        return $this->Origin;
    }
    /**
     * Set Origin value
     * @param PCMWSStructGeocodeOutputLocation $_origin the Origin
     * @return PCMWSStructGeocodeOutputLocation
     */
    public function setOrigin($_origin)
    {
        return ($this->Origin = $_origin);
    }
    /**
     * Get Destination value
     * @return PCMWSStructGeocodeOutputLocation|null
     */
    public function getDestination()
    {
        return $this->Destination;
    }
    /**
     * Set Destination value
     * @param PCMWSStructGeocodeOutputLocation $_destination the Destination
     * @return PCMWSStructGeocodeOutputLocation
     */
    public function setDestination($_destination)
    {
        return ($this->Destination = $_destination);
    }
    /**
     * Get ReportLines value
     * @return PCMWSStructArrayOfComparisonReportLine|null
     */
    public function getReportLines()
    {
        return $this->ReportLines;
    }
    /**
     * Set ReportLines value
     * @param PCMWSStructArrayOfComparisonReportLine $_reportLines the ReportLines
     * @return PCMWSStructArrayOfComparisonReportLine
     */
    public function setReportLines($_reportLines)
    {
        return ($this->ReportLines = $_reportLines);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructLeastCostReport
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
