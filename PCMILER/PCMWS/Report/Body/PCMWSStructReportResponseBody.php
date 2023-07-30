<?php
/**
 * File for class PCMWSStructReportResponseBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructReportResponseBody originally named ReportResponseBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructReportResponseBody extends PCMWSWsdlClass
{
    /**
     * The Reports
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfReport
     */
    public $Reports;
    /**
     * Constructor method for ReportResponseBody
     * @see parent::__construct()
     * @param PCMWSStructArrayOfReport $_reports
     * @return PCMWSStructReportResponseBody
     */
    public function __construct($_reports = NULL)
    {
        parent::__construct(array('Reports'=>($_reports instanceof PCMWSStructArrayOfReport)?$_reports:new PCMWSStructArrayOfReport($_reports)),false);
    }
    /**
     * Get Reports value
     * @return PCMWSStructArrayOfReport|null
     */
    public function getReports()
    {
        return $this->Reports;
    }
    /**
     * Set Reports value
     * @param PCMWSStructArrayOfReport $_reports the Reports
     * @return PCMWSStructArrayOfReport
     */
    public function setReports($_reports)
    {
        return ($this->Reports = $_reports);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructReportResponseBody
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
