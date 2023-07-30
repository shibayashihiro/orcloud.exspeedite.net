<?php
/**
 * File for class PCMWSStructReportRoute
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructReportRoute originally named ReportRoute
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructReportRoute extends PCMWSStructRoute
{
    /**
     * The ReportingOptions
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructReportOptions
     */
    public $ReportingOptions;
    /**
     * The ReportTypes
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfReportType
     */
    public $ReportTypes;
    /**
     * Constructor method for ReportRoute
     * @see parent::__construct()
     * @param PCMWSStructReportOptions $_reportingOptions
     * @param PCMWSStructArrayOfReportType $_reportTypes
     * @return PCMWSStructReportRoute
     */
    public function __construct($_reportingOptions = NULL,$_reportTypes = NULL)
    {
        PCMWSWsdlClass::__construct(array('ReportingOptions'=>$_reportingOptions,'ReportTypes'=>($_reportTypes instanceof PCMWSStructArrayOfReportType)?$_reportTypes:new PCMWSStructArrayOfReportType($_reportTypes)),false);
    }
    /**
     * Get ReportingOptions value
     * @return PCMWSStructReportOptions|null
     */
    public function getReportingOptions()
    {
        return $this->ReportingOptions;
    }
    /**
     * Set ReportingOptions value
     * @param PCMWSStructReportOptions $_reportingOptions the ReportingOptions
     * @return PCMWSStructReportOptions
     */
    public function setReportingOptions($_reportingOptions)
    {
        return ($this->ReportingOptions = $_reportingOptions);
    }
    /**
     * Get ReportTypes value
     * @return PCMWSStructArrayOfReportType|null
     */
    public function getReportTypes()
    {
        return $this->ReportTypes;
    }
    /**
     * Set ReportTypes value
     * @param PCMWSStructArrayOfReportType $_reportTypes the ReportTypes
     * @return PCMWSStructArrayOfReportType
     */
    public function setReportTypes($_reportTypes)
    {
        return ($this->ReportTypes = $_reportTypes);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructReportRoute
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
