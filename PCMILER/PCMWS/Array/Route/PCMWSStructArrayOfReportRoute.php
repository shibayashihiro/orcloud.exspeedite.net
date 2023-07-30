<?php
/**
 * File for class PCMWSStructArrayOfReportRoute
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfReportRoute originally named ArrayOfReportRoute
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfReportRoute extends PCMWSWsdlClass
{
    /**
     * The ReportRoute
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructReportRoute
     */
    public $ReportRoute;
    /**
     * Constructor method for ArrayOfReportRoute
     * @see parent::__construct()
     * @param PCMWSStructReportRoute $_reportRoute
     * @return PCMWSStructArrayOfReportRoute
     */
    public function __construct($_reportRoute = NULL)
    {
        parent::__construct(array('ReportRoute'=>$_reportRoute),false);
    }
    /**
     * Get ReportRoute value
     * @return PCMWSStructReportRoute|null
     */
    public function getReportRoute()
    {
        return $this->ReportRoute;
    }
    /**
     * Set ReportRoute value
     * @param PCMWSStructReportRoute $_reportRoute the ReportRoute
     * @return PCMWSStructReportRoute
     */
    public function setReportRoute($_reportRoute)
    {
        return ($this->ReportRoute = $_reportRoute);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructReportRoute
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructReportRoute
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructReportRoute
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructReportRoute
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructReportRoute
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string ReportRoute
     */
    public function getAttributeName()
    {
        return 'ReportRoute';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfReportRoute
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
