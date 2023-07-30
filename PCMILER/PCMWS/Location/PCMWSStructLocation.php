<?php
/**
 * File for class PCMWSStructLocation
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructLocation originally named Location
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructLocation extends PCMWSWsdlClass
{
    /**
     * The Address
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructAddress
     */
    public $Address;
    /**
     * The Coords
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructCoordinates
     */
    public $Coords;
    /**
     * The Region
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumDataRegion
     */
    public $Region;
    /**
     * The Label
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Label;
    /**
     * The PlaceName
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $PlaceName;
    /**
     * Constructor method for Location
     * @see parent::__construct()
     * @param PCMWSStructAddress $_address
     * @param PCMWSStructCoordinates $_coords
     * @param PCMWSEnumDataRegion $_region
     * @param string $_label
     * @param string $_placeName
     * @return PCMWSStructLocation
     */
    public function __construct($_address = NULL,$_coords = NULL,$_region = NULL,$_label = NULL,$_placeName = NULL)
    {
        parent::__construct(array('Address'=>$_address,'Coords'=>$_coords,'Region'=>$_region,'Label'=>$_label,'PlaceName'=>$_placeName),false);
    }
    /**
     * Get Address value
     * @return PCMWSStructAddress|null
     */
    public function getAddress()
    {
        return $this->Address;
    }
    /**
     * Set Address value
     * @param PCMWSStructAddress $_address the Address
     * @return PCMWSStructAddress
     */
    public function setAddress($_address)
    {
        return ($this->Address = $_address);
    }
    /**
     * Get Coords value
     * @return PCMWSStructCoordinates|null
     */
    public function getCoords()
    {
        return $this->Coords;
    }
    /**
     * Set Coords value
     * @param PCMWSStructCoordinates $_coords the Coords
     * @return PCMWSStructCoordinates
     */
    public function setCoords($_coords)
    {
        return ($this->Coords = $_coords);
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
     * Get Label value
     * @return string|null
     */
    public function getLabel()
    {
        return $this->Label;
    }
    /**
     * Set Label value
     * @param string $_label the Label
     * @return string
     */
    public function setLabel($_label)
    {
        return ($this->Label = $_label);
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
     * @return PCMWSStructLocation
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
