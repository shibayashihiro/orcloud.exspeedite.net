<?php
/**
 * File for class PCMWSStructMapRoutesRequestBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructMapRoutesRequestBody originally named MapRoutesRequestBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructMapRoutesRequestBody extends PCMWSWsdlClass
{
    /**
     * The Map
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructMapRequestBody
     */
    public $Map;
    /**
     * The Routes
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfMapRoute
     */
    public $Routes;
    /**
     * Constructor method for MapRoutesRequestBody
     * @see parent::__construct()
     * @param PCMWSStructMapRequestBody $_map
     * @param PCMWSStructArrayOfMapRoute $_routes
     * @return PCMWSStructMapRoutesRequestBody
     */
    public function __construct($_map = NULL,$_routes = NULL)
    {
        parent::__construct(array('Map'=>$_map,'Routes'=>($_routes instanceof PCMWSStructArrayOfMapRoute)?$_routes:new PCMWSStructArrayOfMapRoute($_routes)),false);
    }
    /**
     * Get Map value
     * @return PCMWSStructMapRequestBody|null
     */
    public function getMap()
    {
        return $this->Map;
    }
    /**
     * Set Map value
     * @param PCMWSStructMapRequestBody $_map the Map
     * @return PCMWSStructMapRequestBody
     */
    public function setMap($_map)
    {
        return ($this->Map = $_map);
    }
    /**
     * Get Routes value
     * @return PCMWSStructArrayOfMapRoute|null
     */
    public function getRoutes()
    {
        return $this->Routes;
    }
    /**
     * Set Routes value
     * @param PCMWSStructArrayOfMapRoute $_routes the Routes
     * @return PCMWSStructArrayOfMapRoute
     */
    public function setRoutes($_routes)
    {
        return ($this->Routes = $_routes);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructMapRoutesRequestBody
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
