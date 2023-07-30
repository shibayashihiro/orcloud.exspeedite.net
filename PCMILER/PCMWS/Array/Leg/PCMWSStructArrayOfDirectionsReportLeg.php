<?php
/**
 * File for class PCMWSStructArrayOfDirectionsReportLeg
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfDirectionsReportLeg originally named ArrayOfDirectionsReportLeg
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfDirectionsReportLeg extends PCMWSWsdlClass
{
    /**
     * The DirectionsReportLeg
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructDirectionsReportLeg
     */
    public $DirectionsReportLeg;
    /**
     * Constructor method for ArrayOfDirectionsReportLeg
     * @see parent::__construct()
     * @param PCMWSStructDirectionsReportLeg $_directionsReportLeg
     * @return PCMWSStructArrayOfDirectionsReportLeg
     */
    public function __construct($_directionsReportLeg = NULL)
    {
        parent::__construct(array('DirectionsReportLeg'=>$_directionsReportLeg),false);
    }
    /**
     * Get DirectionsReportLeg value
     * @return PCMWSStructDirectionsReportLeg|null
     */
    public function getDirectionsReportLeg()
    {
        return $this->DirectionsReportLeg;
    }
    /**
     * Set DirectionsReportLeg value
     * @param PCMWSStructDirectionsReportLeg $_directionsReportLeg the DirectionsReportLeg
     * @return PCMWSStructDirectionsReportLeg
     */
    public function setDirectionsReportLeg($_directionsReportLeg)
    {
        return ($this->DirectionsReportLeg = $_directionsReportLeg);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructDirectionsReportLeg
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructDirectionsReportLeg
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructDirectionsReportLeg
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructDirectionsReportLeg
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructDirectionsReportLeg
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string DirectionsReportLeg
     */
    public function getAttributeName()
    {
        return 'DirectionsReportLeg';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfDirectionsReportLeg
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
