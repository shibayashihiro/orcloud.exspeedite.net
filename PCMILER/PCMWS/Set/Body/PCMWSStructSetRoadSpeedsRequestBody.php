<?php
/**
 * File for class PCMWSStructSetRoadSpeedsRequestBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructSetRoadSpeedsRequestBody originally named SetRoadSpeedsRequestBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructSetRoadSpeedsRequestBody extends PCMWSWsdlClass
{
    /**
     * The RoadSpeeds
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfRoadSpeed
     */
    public $RoadSpeeds;
    /**
     * Constructor method for SetRoadSpeedsRequestBody
     * @see parent::__construct()
     * @param PCMWSStructArrayOfRoadSpeed $_roadSpeeds
     * @return PCMWSStructSetRoadSpeedsRequestBody
     */
    public function __construct($_roadSpeeds = NULL)
    {
        parent::__construct(array('RoadSpeeds'=>($_roadSpeeds instanceof PCMWSStructArrayOfRoadSpeed)?$_roadSpeeds:new PCMWSStructArrayOfRoadSpeed($_roadSpeeds)),false);
    }
    /**
     * Get RoadSpeeds value
     * @return PCMWSStructArrayOfRoadSpeed|null
     */
    public function getRoadSpeeds()
    {
        return $this->RoadSpeeds;
    }
    /**
     * Set RoadSpeeds value
     * @param PCMWSStructArrayOfRoadSpeed $_roadSpeeds the RoadSpeeds
     * @return PCMWSStructArrayOfRoadSpeed
     */
    public function setRoadSpeeds($_roadSpeeds)
    {
        return ($this->RoadSpeeds = $_roadSpeeds);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructSetRoadSpeedsRequestBody
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
