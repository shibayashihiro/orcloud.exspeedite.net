<?php
/**
 * File for class PCMWSStructArrayOfGeofence
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfGeofence originally named ArrayOfGeofence
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfGeofence extends PCMWSWsdlClass
{
    /**
     * The Geofence
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructGeofence
     */
    public $Geofence;
    /**
     * Constructor method for ArrayOfGeofence
     * @see parent::__construct()
     * @param PCMWSStructGeofence $_geofence
     * @return PCMWSStructArrayOfGeofence
     */
    public function __construct($_geofence = NULL)
    {
        parent::__construct(array('Geofence'=>$_geofence),false);
    }
    /**
     * Get Geofence value
     * @return PCMWSStructGeofence|null
     */
    public function getGeofence()
    {
        return $this->Geofence;
    }
    /**
     * Set Geofence value
     * @param PCMWSStructGeofence $_geofence the Geofence
     * @return PCMWSStructGeofence
     */
    public function setGeofence($_geofence)
    {
        return ($this->Geofence = $_geofence);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructGeofence
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructGeofence
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructGeofence
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructGeofence
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructGeofence
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string Geofence
     */
    public function getAttributeName()
    {
        return 'Geofence';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfGeofence
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
