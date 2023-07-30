<?php
/**
 * File for class PCMWSStructStopLocation
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructStopLocation originally named StopLocation
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructStopLocation extends PCMWSStructLocation
{
    /**
     * The Costs
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructStopCosts
     */
    public $Costs;
    /**
     * The IsViaPoint
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $IsViaPoint;
    /**
     * Constructor method for StopLocation
     * @see parent::__construct()
     * @param PCMWSStructStopCosts $_costs
     * @param boolean $_isViaPoint
     * @return PCMWSStructStopLocation
     */
    public function __construct($_costs = NULL,$_isViaPoint = NULL)
    {
        PCMWSWsdlClass::__construct(array('Costs'=>$_costs,'IsViaPoint'=>$_isViaPoint),false);
    }
    /**
     * Get Costs value
     * @return PCMWSStructStopCosts|null
     */
    public function getCosts()
    {
        return $this->Costs;
    }
    /**
     * Set Costs value
     * @param PCMWSStructStopCosts $_costs the Costs
     * @return PCMWSStructStopCosts
     */
    public function setCosts($_costs)
    {
        return ($this->Costs = $_costs);
    }
    /**
     * Get IsViaPoint value
     * @return boolean|null
     */
    public function getIsViaPoint()
    {
        return $this->IsViaPoint;
    }
    /**
     * Set IsViaPoint value
     * @param boolean $_isViaPoint the IsViaPoint
     * @return boolean
     */
    public function setIsViaPoint($_isViaPoint)
    {
        return ($this->IsViaPoint = $_isViaPoint);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructStopLocation
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
