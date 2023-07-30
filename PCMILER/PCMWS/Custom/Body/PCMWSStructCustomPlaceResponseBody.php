<?php
/**
 * File for class PCMWSStructCustomPlaceResponseBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructCustomPlaceResponseBody originally named CustomPlaceResponseBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructCustomPlaceResponseBody extends PCMWSWsdlClass
{
    /**
     * The CustomPlaces
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfLocation
     */
    public $CustomPlaces;
    /**
     * Constructor method for CustomPlaceResponseBody
     * @see parent::__construct()
     * @param PCMWSStructArrayOfLocation $_customPlaces
     * @return PCMWSStructCustomPlaceResponseBody
     */
    public function __construct($_customPlaces = NULL)
    {
        parent::__construct(array('CustomPlaces'=>($_customPlaces instanceof PCMWSStructArrayOfLocation)?$_customPlaces:new PCMWSStructArrayOfLocation($_customPlaces)),false);
    }
    /**
     * Get CustomPlaces value
     * @return PCMWSStructArrayOfLocation|null
     */
    public function getCustomPlaces()
    {
        return $this->CustomPlaces;
    }
    /**
     * Set CustomPlaces value
     * @param PCMWSStructArrayOfLocation $_customPlaces the CustomPlaces
     * @return PCMWSStructArrayOfLocation
     */
    public function setCustomPlaces($_customPlaces)
    {
        return ($this->CustomPlaces = $_customPlaces);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructCustomPlaceResponseBody
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
