<?php
/**
 * File for class PCMWSStructSharedOptions
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructSharedOptions originally named SharedOptions
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructSharedOptions extends PCMWSWsdlClass
{
    /**
     * The EstimatedTimeOptions
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructEstimatedTimeOptions
     */
    public $EstimatedTimeOptions;
    /**
     * The TruckStyle
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumTruckStyle
     */
    public $TruckStyle;
    /**
     * The UseTraffic
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $UseTraffic;
    /**
     * Constructor method for SharedOptions
     * @see parent::__construct()
     * @param PCMWSStructEstimatedTimeOptions $_estimatedTimeOptions
     * @param PCMWSEnumTruckStyle $_truckStyle
     * @param boolean $_useTraffic
     * @return PCMWSStructSharedOptions
     */
    public function __construct($_estimatedTimeOptions = NULL,$_truckStyle = NULL,$_useTraffic = NULL)
    {
        parent::__construct(array('EstimatedTimeOptions'=>$_estimatedTimeOptions,'TruckStyle'=>$_truckStyle,'UseTraffic'=>$_useTraffic),false);
    }
    /**
     * Get EstimatedTimeOptions value
     * @return PCMWSStructEstimatedTimeOptions|null
     */
    public function getEstimatedTimeOptions()
    {
        return $this->EstimatedTimeOptions;
    }
    /**
     * Set EstimatedTimeOptions value
     * @param PCMWSStructEstimatedTimeOptions $_estimatedTimeOptions the EstimatedTimeOptions
     * @return PCMWSStructEstimatedTimeOptions
     */
    public function setEstimatedTimeOptions($_estimatedTimeOptions)
    {
        return ($this->EstimatedTimeOptions = $_estimatedTimeOptions);
    }
    /**
     * Get TruckStyle value
     * @return PCMWSEnumTruckStyle|null
     */
    public function getTruckStyle()
    {
        return $this->TruckStyle;
    }
    /**
     * Set TruckStyle value
     * @uses PCMWSEnumTruckStyle::valueIsValid()
     * @param PCMWSEnumTruckStyle $_truckStyle the TruckStyle
     * @return PCMWSEnumTruckStyle
     */
    public function setTruckStyle($_truckStyle)
    {
        if(!PCMWSEnumTruckStyle::valueIsValid($_truckStyle))
        {
            return false;
        }
        return ($this->TruckStyle = $_truckStyle);
    }
    /**
     * Get UseTraffic value
     * @return boolean|null
     */
    public function getUseTraffic()
    {
        return $this->UseTraffic;
    }
    /**
     * Set UseTraffic value
     * @param boolean $_useTraffic the UseTraffic
     * @return boolean
     */
    public function setUseTraffic($_useTraffic)
    {
        return ($this->UseTraffic = $_useTraffic);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructSharedOptions
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
