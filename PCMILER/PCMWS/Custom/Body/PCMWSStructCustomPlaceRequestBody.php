<?php
/**
 * File for class PCMWSStructCustomPlaceRequestBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructCustomPlaceRequestBody originally named CustomPlaceRequestBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructCustomPlaceRequestBody extends PCMWSWsdlClass
{
    /**
     * The PlaceName
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $PlaceName;
    /**
     * Constructor method for CustomPlaceRequestBody
     * @see parent::__construct()
     * @param string $_placeName
     * @return PCMWSStructCustomPlaceRequestBody
     */
    public function __construct($_placeName = NULL)
    {
        parent::__construct(array('PlaceName'=>$_placeName),false);
    }
    /**
     * Get PlaceName value
     * @return string|null
     */
    public function getPlaceName()
    {
        return $this->PlaceName;
    }
    /**
     * Set PlaceName value
     * @param string $_placeName the PlaceName
     * @return string
     */
    public function setPlaceName($_placeName)
    {
        return ($this->PlaceName = $_placeName);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructCustomPlaceRequestBody
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
