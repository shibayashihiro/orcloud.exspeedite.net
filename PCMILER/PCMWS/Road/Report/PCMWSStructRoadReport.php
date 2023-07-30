<?php
/**
 * File for class PCMWSStructRoadReport
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructRoadReport originally named RoadReport
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructRoadReport extends PCMWSStructReport
{
    /**
     * The ReportLines
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfRoadReportLine
     */
    public $ReportLines;
    /**
     * The Disclaimers
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfstring
     */
    public $Disclaimers;
    /**
     * Constructor method for RoadReport
     * @see parent::__construct()
     * @param PCMWSStructArrayOfRoadReportLine $_reportLines
     * @param PCMWSStructArrayOfstring $_disclaimers
     * @return PCMWSStructRoadReport
     */
    public function __construct($_reportLines = NULL,$_disclaimers = NULL)
    {
        PCMWSWsdlClass::__construct(array('ReportLines'=>($_reportLines instanceof PCMWSStructArrayOfRoadReportLine)?$_reportLines:new PCMWSStructArrayOfRoadReportLine($_reportLines),'Disclaimers'=>($_disclaimers instanceof PCMWSStructArrayOfstring)?$_disclaimers:new PCMWSStructArrayOfstring($_disclaimers)),false);
    }
    /**
     * Get ReportLines value
     * @return PCMWSStructArrayOfRoadReportLine|null
     */
    public function getReportLines()
    {
        return $this->ReportLines;
    }
    /**
     * Set ReportLines value
     * @param PCMWSStructArrayOfRoadReportLine $_reportLines the ReportLines
     * @return PCMWSStructArrayOfRoadReportLine
     */
    public function setReportLines($_reportLines)
    {
        return ($this->ReportLines = $_reportLines);
    }
    /**
     * Get Disclaimers value
     * @return PCMWSStructArrayOfstring|null
     */
    public function getDisclaimers()
    {
        return $this->Disclaimers;
    }
    /**
     * Set Disclaimers value
     * @param PCMWSStructArrayOfstring $_disclaimers the Disclaimers
     * @return PCMWSStructArrayOfstring
     */
    public function setDisclaimers($_disclaimers)
    {
        return ($this->Disclaimers = $_disclaimers);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructRoadReport
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
