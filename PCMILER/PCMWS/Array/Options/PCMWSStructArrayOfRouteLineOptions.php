<?php
/**
 * File for class PCMWSStructArrayOfRouteLineOptions
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfRouteLineOptions originally named ArrayOfRouteLineOptions
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfRouteLineOptions extends PCMWSWsdlClass
{
    /**
     * The RouteLineOptions
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructRouteLineOptions
     */
    public $RouteLineOptions;
    /**
     * Constructor method for ArrayOfRouteLineOptions
     * @see parent::__construct()
     * @param PCMWSStructRouteLineOptions $_routeLineOptions
     * @return PCMWSStructArrayOfRouteLineOptions
     */
    public function __construct($_routeLineOptions = NULL)
    {
        parent::__construct(array('RouteLineOptions'=>$_routeLineOptions),false);
    }
    /**
     * Get RouteLineOptions value
     * @return PCMWSStructRouteLineOptions|null
     */
    public function getRouteLineOptions()
    {
        return $this->RouteLineOptions;
    }
    /**
     * Set RouteLineOptions value
     * @param PCMWSStructRouteLineOptions $_routeLineOptions the RouteLineOptions
     * @return PCMWSStructRouteLineOptions
     */
    public function setRouteLineOptions($_routeLineOptions)
    {
        return ($this->RouteLineOptions = $_routeLineOptions);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructRouteLineOptions
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructRouteLineOptions
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructRouteLineOptions
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructRouteLineOptions
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructRouteLineOptions
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string RouteLineOptions
     */
    public function getAttributeName()
    {
        return 'RouteLineOptions';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfRouteLineOptions
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
