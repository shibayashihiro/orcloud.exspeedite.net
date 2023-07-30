<?php
/**
 * File for class PCMWSStructFuelReport
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructFuelReport originally named FuelReport
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructFuelReport extends PCMWSStructReport
{
    /**
     * The FuelStops
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfFuelStopLine
     */
    public $FuelStops;
    /**
     * The FuelSummary
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructFuelReportSummary
     */
    public $FuelSummary;
    /**
     * Constructor method for FuelReport
     * @see parent::__construct()
     * @param PCMWSStructArrayOfFuelStopLine $_fuelStops
     * @param PCMWSStructFuelReportSummary $_fuelSummary
     * @return PCMWSStructFuelReport
     */
    public function __construct($_fuelStops = NULL,$_fuelSummary = NULL)
    {
        PCMWSWsdlClass::__construct(array('FuelStops'=>($_fuelStops instanceof PCMWSStructArrayOfFuelStopLine)?$_fuelStops:new PCMWSStructArrayOfFuelStopLine($_fuelStops),'FuelSummary'=>$_fuelSummary),false);
    }
    /**
     * Get FuelStops value
     * @return PCMWSStructArrayOfFuelStopLine|null
     */
    public function getFuelStops()
    {
        return $this->FuelStops;
    }
    /**
     * Set FuelStops value
     * @param PCMWSStructArrayOfFuelStopLine $_fuelStops the FuelStops
     * @return PCMWSStructArrayOfFuelStopLine
     */
    public function setFuelStops($_fuelStops)
    {
        return ($this->FuelStops = $_fuelStops);
    }
    /**
     * Get FuelSummary value
     * @return PCMWSStructFuelReportSummary|null
     */
    public function getFuelSummary()
    {
        return $this->FuelSummary;
    }
    /**
     * Set FuelSummary value
     * @param PCMWSStructFuelReportSummary $_fuelSummary the FuelSummary
     * @return PCMWSStructFuelReportSummary
     */
    public function setFuelSummary($_fuelSummary)
    {
        return ($this->FuelSummary = $_fuelSummary);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructFuelReport
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
