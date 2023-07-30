<?php
/**
 * File for class PCMWSStructDetailReportType
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructDetailReportType originally named DetailReportType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructDetailReportType extends PCMWSStructReportType
{
    /**
     * The SeparateHeadingFromRoute
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $SeparateHeadingFromRoute;
    /**
     * The SegmentEndpoints
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $SegmentEndpoints;
    /**
     * Constructor method for DetailReportType
     * @see parent::__construct()
     * @param boolean $_separateHeadingFromRoute
     * @param boolean $_segmentEndpoints
     * @return PCMWSStructDetailReportType
     */
    public function __construct($_separateHeadingFromRoute = NULL,$_segmentEndpoints = NULL)
    {
        PCMWSWsdlClass::__construct(array('SeparateHeadingFromRoute'=>$_separateHeadingFromRoute,'SegmentEndpoints'=>$_segmentEndpoints),false);
    }
    /**
     * Get SeparateHeadingFromRoute value
     * @return boolean|null
     */
    public function getSeparateHeadingFromRoute()
    {
        return $this->SeparateHeadingFromRoute;
    }
    /**
     * Set SeparateHeadingFromRoute value
     * @param boolean $_separateHeadingFromRoute the SeparateHeadingFromRoute
     * @return boolean
     */
    public function setSeparateHeadingFromRoute($_separateHeadingFromRoute)
    {
        return ($this->SeparateHeadingFromRoute = $_separateHeadingFromRoute);
    }
    /**
     * Get SegmentEndpoints value
     * @return boolean|null
     */
    public function getSegmentEndpoints()
    {
        return $this->SegmentEndpoints;
    }
    /**
     * Set SegmentEndpoints value
     * @param boolean $_segmentEndpoints the SegmentEndpoints
     * @return boolean
     */
    public function setSegmentEndpoints($_segmentEndpoints)
    {
        return ($this->SegmentEndpoints = $_segmentEndpoints);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructDetailReportType
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
