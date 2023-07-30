<?php
/**
 * File for class PCMWSStructSetCustomPlaceRequestBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructSetCustomPlaceRequestBody originally named SetCustomPlaceRequestBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructSetCustomPlaceRequestBody extends PCMWSWsdlClass
{
    /**
     * The CustomPlaces
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfCustomPlaceLocation
     */
    public $CustomPlaces;
    /**
     * Constructor method for SetCustomPlaceRequestBody
     * @see parent::__construct()
     * @param PCMWSStructArrayOfCustomPlaceLocation $_customPlaces
     * @return PCMWSStructSetCustomPlaceRequestBody
     */
    public function __construct($_customPlaces = NULL)
    {
        parent::__construct(array('CustomPlaces'=>($_customPlaces instanceof PCMWSStructArrayOfCustomPlaceLocation)?$_customPlaces:new PCMWSStructArrayOfCustomPlaceLocation($_customPlaces)),false);
    }
    /**
     * Get CustomPlaces value
     * @return PCMWSStructArrayOfCustomPlaceLocation|null
     */
    public function getCustomPlaces()
    {
        return $this->CustomPlaces;
    }
    /**
     * Set CustomPlaces value
     * @param PCMWSStructArrayOfCustomPlaceLocation $_customPlaces the CustomPlaces
     * @return PCMWSStructArrayOfCustomPlaceLocation
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
     * @return PCMWSStructSetCustomPlaceRequestBody
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
