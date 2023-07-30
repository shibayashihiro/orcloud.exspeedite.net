<?php
/**
 * File for class PCMWSStructRouteMatrixRequestBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructRouteMatrixRequestBody originally named RouteMatrixRequestBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructRouteMatrixRequestBody extends PCMWSWsdlClass
{
    /**
     * The Origins
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfLocation
     */
    public $Origins;
    /**
     * The Destinations
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfLocation
     */
    public $Destinations;
    /**
     * The Options
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructRouteOptions
     */
    public $Options;
    /**
     * Constructor method for RouteMatrixRequestBody
     * @see parent::__construct()
     * @param PCMWSStructArrayOfLocation $_origins
     * @param PCMWSStructArrayOfLocation $_destinations
     * @param PCMWSStructRouteOptions $_options
     * @return PCMWSStructRouteMatrixRequestBody
     */
    public function __construct($_origins = NULL,$_destinations = NULL,$_options = NULL)
    {
        parent::__construct(array('Origins'=>($_origins instanceof PCMWSStructArrayOfLocation)?$_origins:new PCMWSStructArrayOfLocation($_origins),'Destinations'=>($_destinations instanceof PCMWSStructArrayOfLocation)?$_destinations:new PCMWSStructArrayOfLocation($_destinations),'Options'=>$_options),false);
    }
    /**
     * Get Origins value
     * @return PCMWSStructArrayOfLocation|null
     */
    public function getOrigins()
    {
        return $this->Origins;
    }
    /**
     * Set Origins value
     * @param PCMWSStructArrayOfLocation $_origins the Origins
     * @return PCMWSStructArrayOfLocation
     */
    public function setOrigins($_origins)
    {
        return ($this->Origins = $_origins);
    }
    /**
     * Get Destinations value
     * @return PCMWSStructArrayOfLocation|null
     */
    public function getDestinations()
    {
        return $this->Destinations;
    }
    /**
     * Set Destinations value
     * @param PCMWSStructArrayOfLocation $_destinations the Destinations
     * @return PCMWSStructArrayOfLocation
     */
    public function setDestinations($_destinations)
    {
        return ($this->Destinations = $_destinations);
    }
    /**
     * Get Options value
     * @return PCMWSStructRouteOptions|null
     */
    public function getOptions()
    {
        return $this->Options;
    }
    /**
     * Set Options value
     * @param PCMWSStructRouteOptions $_options the Options
     * @return PCMWSStructRouteOptions
     */
    public function setOptions($_options)
    {
        return ($this->Options = $_options);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructRouteMatrixRequestBody
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
