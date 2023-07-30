<?php
/**
 * File for class PCMWSStructReverseGeoCoord
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructReverseGeoCoord originally named ReverseGeoCoord
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructReverseGeoCoord extends PCMWSStructCoordinates
{
    /**
     * The Region
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumDataRegion
     */
    public $Region;
    /**
     * The SpeedLimitOption
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructSpeedLimitOptions
     */
    public $SpeedLimitOption;
    /**
     * Constructor method for ReverseGeoCoord
     * @see parent::__construct()
     * @param PCMWSEnumDataRegion $_region
     * @param PCMWSStructSpeedLimitOptions $_speedLimitOption
     * @return PCMWSStructReverseGeoCoord
     */
    public function __construct($_region = NULL,$_speedLimitOption = NULL)
    {
        PCMWSWsdlClass::__construct(array('Region'=>$_region,'SpeedLimitOption'=>$_speedLimitOption),false);
    }
    /**
     * Get Region value
     * @return PCMWSEnumDataRegion|null
     */
    public function getRegion()
    {
        return $this->Region;
    }
    /**
     * Set Region value
     * @uses PCMWSEnumDataRegion::valueIsValid()
     * @param PCMWSEnumDataRegion $_region the Region
     * @return PCMWSEnumDataRegion
     */
    public function setRegion($_region)
    {
        if(!PCMWSEnumDataRegion::valueIsValid($_region))
        {
            return false;
        }
        return ($this->Region = $_region);
    }
    /**
     * Get SpeedLimitOption value
     * @return PCMWSStructSpeedLimitOptions|null
     */
    public function getSpeedLimitOption()
    {
        return $this->SpeedLimitOption;
    }
    /**
     * Set SpeedLimitOption value
     * @param PCMWSStructSpeedLimitOptions $_speedLimitOption the SpeedLimitOption
     * @return PCMWSStructSpeedLimitOptions
     */
    public function setSpeedLimitOption($_speedLimitOption)
    {
        return ($this->SpeedLimitOption = $_speedLimitOption);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructReverseGeoCoord
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
