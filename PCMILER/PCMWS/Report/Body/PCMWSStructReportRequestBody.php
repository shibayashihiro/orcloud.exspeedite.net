<?php
/**
 * File for class PCMWSStructReportRequestBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructReportRequestBody originally named ReportRequestBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructReportRequestBody extends PCMWSWsdlClass
{
    /**
     * The ReportRoutes
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfReportRoute
     */
    public $ReportRoutes;
    /**
     * Constructor method for ReportRequestBody
     * @see parent::__construct()
     * @param PCMWSStructArrayOfReportRoute $_reportRoutes
     * @return PCMWSStructReportRequestBody
     */
    public function __construct($_reportRoutes = NULL)
    {
        parent::__construct(array('ReportRoutes'=>($_reportRoutes instanceof PCMWSStructArrayOfReportRoute)?$_reportRoutes:new PCMWSStructArrayOfReportRoute($_reportRoutes)),false);
    }
    /**
     * Get ReportRoutes value
     * @return PCMWSStructArrayOfReportRoute|null
     */
    public function getReportRoutes()
    {
        return $this->ReportRoutes;
    }
    /**
     * Set ReportRoutes value
     * @param PCMWSStructArrayOfReportRoute $_reportRoutes the ReportRoutes
     * @return PCMWSStructArrayOfReportRoute
     */
    public function setReportRoutes($_reportRoutes)
    {
        return ($this->ReportRoutes = $_reportRoutes);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructReportRequestBody
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
