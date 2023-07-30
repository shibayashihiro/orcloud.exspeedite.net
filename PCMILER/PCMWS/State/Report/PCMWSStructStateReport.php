<?php
/**
 * File for class PCMWSStructStateReport
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructStateReport originally named StateReport
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructStateReport extends PCMWSStructReport
{
    /**
     * The MileageReportLines
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfStopReportLine
     */
    public $MileageReportLines;
    /**
     * The StateReportLines
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfStateCostReportLine
     */
    public $StateReportLines;
    /**
     * Constructor method for StateReport
     * @see parent::__construct()
     * @param PCMWSStructArrayOfStopReportLine $_mileageReportLines
     * @param PCMWSStructArrayOfStateCostReportLine $_stateReportLines
     * @return PCMWSStructStateReport
     */
    public function __construct($_mileageReportLines = NULL,$_stateReportLines = NULL)
    {
        PCMWSWsdlClass::__construct(array('MileageReportLines'=>($_mileageReportLines instanceof PCMWSStructArrayOfStopReportLine)?$_mileageReportLines:new PCMWSStructArrayOfStopReportLine($_mileageReportLines),'StateReportLines'=>($_stateReportLines instanceof PCMWSStructArrayOfStateCostReportLine)?$_stateReportLines:new PCMWSStructArrayOfStateCostReportLine($_stateReportLines)),false);
    }
    /**
     * Get MileageReportLines value
     * @return PCMWSStructArrayOfStopReportLine|null
     */
    public function getMileageReportLines()
    {
        return $this->MileageReportLines;
    }
    /**
     * Set MileageReportLines value
     * @param PCMWSStructArrayOfStopReportLine $_mileageReportLines the MileageReportLines
     * @return PCMWSStructArrayOfStopReportLine
     */
    public function setMileageReportLines($_mileageReportLines)
    {
        return ($this->MileageReportLines = $_mileageReportLines);
    }
    /**
     * Get StateReportLines value
     * @return PCMWSStructArrayOfStateCostReportLine|null
     */
    public function getStateReportLines()
    {
        return $this->StateReportLines;
    }
    /**
     * Set StateReportLines value
     * @param PCMWSStructArrayOfStateCostReportLine $_stateReportLines the StateReportLines
     * @return PCMWSStructArrayOfStateCostReportLine
     */
    public function setStateReportLines($_stateReportLines)
    {
        return ($this->StateReportLines = $_stateReportLines);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructStateReport
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
