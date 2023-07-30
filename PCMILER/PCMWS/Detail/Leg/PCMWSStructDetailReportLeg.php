<?php
/**
 * File for class PCMWSStructDetailReportLeg
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructDetailReportLeg originally named DetailReportLeg
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructDetailReportLeg extends PCMWSWsdlClass
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
     * The ReportLines
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfDetailReportLine
     */
    public $ReportLines;
    /**
     * The Dest
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructGeocodeOutputLocation
     */
    public $Dest;
    /**
     * Constructor method for DetailReportLeg
     * @see parent::__construct()
     * @param PCMWSStructGeocodeOutputLocation $_origin
     * @param PCMWSStructArrayOfDetailReportLine $_reportLines
     * @param PCMWSStructGeocodeOutputLocation $_dest
     * @return PCMWSStructDetailReportLeg
     */
    public function __construct($_origin = NULL,$_reportLines = NULL,$_dest = NULL)
    {
        parent::__construct(array('Origin'=>$_origin,'ReportLines'=>($_reportLines instanceof PCMWSStructArrayOfDetailReportLine)?$_reportLines:new PCMWSStructArrayOfDetailReportLine($_reportLines),'Dest'=>$_dest),false);
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
     * Get ReportLines value
     * @return PCMWSStructArrayOfDetailReportLine|null
     */
    public function getReportLines()
    {
        return $this->ReportLines;
    }
    /**
     * Set ReportLines value
     * @param PCMWSStructArrayOfDetailReportLine $_reportLines the ReportLines
     * @return PCMWSStructArrayOfDetailReportLine
     */
    public function setReportLines($_reportLines)
    {
        return ($this->ReportLines = $_reportLines);
    }
    /**
     * Get Dest value
     * @return PCMWSStructGeocodeOutputLocation|null
     */
    public function getDest()
    {
        return $this->Dest;
    }
    /**
     * Set Dest value
     * @param PCMWSStructGeocodeOutputLocation $_dest the Dest
     * @return PCMWSStructGeocodeOutputLocation
     */
    public function setDest($_dest)
    {
        return ($this->Dest = $_dest);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructDetailReportLeg
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
