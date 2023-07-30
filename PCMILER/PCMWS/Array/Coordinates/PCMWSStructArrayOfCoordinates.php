<?php
/**
 * File for class PCMWSStructArrayOfCoordinates
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfCoordinates originally named ArrayOfCoordinates
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfCoordinates extends PCMWSWsdlClass
{
    /**
     * The Coordinates
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructCoordinates
     */
    public $Coordinates;
    /**
     * Constructor method for ArrayOfCoordinates
     * @see parent::__construct()
     * @param PCMWSStructCoordinates $_coordinates
     * @return PCMWSStructArrayOfCoordinates
     */
    public function __construct($_coordinates = NULL)
    {
        parent::__construct(array('Coordinates'=>$_coordinates),false);
    }
    /**
     * Get Coordinates value
     * @return PCMWSStructCoordinates|null
     */
    public function getCoordinates()
    {
        return $this->Coordinates;
    }
    /**
     * Set Coordinates value
     * @param PCMWSStructCoordinates $_coordinates the Coordinates
     * @return PCMWSStructCoordinates
     */
    public function setCoordinates($_coordinates)
    {
        return ($this->Coordinates = $_coordinates);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructCoordinates
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructCoordinates
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructCoordinates
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructCoordinates
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructCoordinates
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string Coordinates
     */
    public function getAttributeName()
    {
        return 'Coordinates';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfCoordinates
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
