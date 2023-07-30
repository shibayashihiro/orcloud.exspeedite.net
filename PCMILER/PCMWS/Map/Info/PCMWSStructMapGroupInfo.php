<?php
/**
 * File for class PCMWSStructMapGroupInfo
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructMapGroupInfo originally named MapGroupInfo
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructMapGroupInfo extends PCMWSWsdlClass
{
    /**
     * The DevX
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $DevX;
    /**
     * The DevY
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $DevY;
    /**
     * The ID
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $ID;
    /**
     * The Latitude
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $Latitude;
    /**
     * The Longitude
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $Longitude;
    /**
     * The MaxLat
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $MaxLat;
    /**
     * The MaxLon
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $MaxLon;
    /**
     * The MinLat
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $MinLat;
    /**
     * The MinLon
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $MinLon;
    /**
     * The NumPoints
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $NumPoints;
    /**
     * Constructor method for MapGroupInfo
     * @see parent::__construct()
     * @param int $_devX
     * @param int $_devY
     * @param int $_iD
     * @param int $_latitude
     * @param int $_longitude
     * @param int $_maxLat
     * @param int $_maxLon
     * @param int $_minLat
     * @param int $_minLon
     * @param int $_numPoints
     * @return PCMWSStructMapGroupInfo
     */
    public function __construct($_devX = NULL,$_devY = NULL,$_iD = NULL,$_latitude = NULL,$_longitude = NULL,$_maxLat = NULL,$_maxLon = NULL,$_minLat = NULL,$_minLon = NULL,$_numPoints = NULL)
    {
        parent::__construct(array('DevX'=>$_devX,'DevY'=>$_devY,'ID'=>$_iD,'Latitude'=>$_latitude,'Longitude'=>$_longitude,'MaxLat'=>$_maxLat,'MaxLon'=>$_maxLon,'MinLat'=>$_minLat,'MinLon'=>$_minLon,'NumPoints'=>$_numPoints),false);
    }
    /**
     * Get DevX value
     * @return int|null
     */
    public function getDevX()
    {
        return $this->DevX;
    }
    /**
     * Set DevX value
     * @param int $_devX the DevX
     * @return int
     */
    public function setDevX($_devX)
    {
        return ($this->DevX = $_devX);
    }
    /**
     * Get DevY value
     * @return int|null
     */
    public function getDevY()
    {
        return $this->DevY;
    }
    /**
     * Set DevY value
     * @param int $_devY the DevY
     * @return int
     */
    public function setDevY($_devY)
    {
        return ($this->DevY = $_devY);
    }
    /**
     * Get ID value
     * @return int|null
     */
    public function getID()
    {
        return $this->ID;
    }
    /**
     * Set ID value
     * @param int $_iD the ID
     * @return int
     */
    public function setID($_iD)
    {
        return ($this->ID = $_iD);
    }
    /**
     * Get Latitude value
     * @return int|null
     */
    public function getLatitude()
    {
        return $this->Latitude;
    }
    /**
     * Set Latitude value
     * @param int $_latitude the Latitude
     * @return int
     */
    public function setLatitude($_latitude)
    {
        return ($this->Latitude = $_latitude);
    }
    /**
     * Get Longitude value
     * @return int|null
     */
    public function getLongitude()
    {
        return $this->Longitude;
    }
    /**
     * Set Longitude value
     * @param int $_longitude the Longitude
     * @return int
     */
    public function setLongitude($_longitude)
    {
        return ($this->Longitude = $_longitude);
    }
    /**
     * Get MaxLat value
     * @return int|null
     */
    public function getMaxLat()
    {
        return $this->MaxLat;
    }
    /**
     * Set MaxLat value
     * @param int $_maxLat the MaxLat
     * @return int
     */
    public function setMaxLat($_maxLat)
    {
        return ($this->MaxLat = $_maxLat);
    }
    /**
     * Get MaxLon value
     * @return int|null
     */
    public function getMaxLon()
    {
        return $this->MaxLon;
    }
    /**
     * Set MaxLon value
     * @param int $_maxLon the MaxLon
     * @return int
     */
    public function setMaxLon($_maxLon)
    {
        return ($this->MaxLon = $_maxLon);
    }
    /**
     * Get MinLat value
     * @return int|null
     */
    public function getMinLat()
    {
        return $this->MinLat;
    }
    /**
     * Set MinLat value
     * @param int $_minLat the MinLat
     * @return int
     */
    public function setMinLat($_minLat)
    {
        return ($this->MinLat = $_minLat);
    }
    /**
     * Get MinLon value
     * @return int|null
     */
    public function getMinLon()
    {
        return $this->MinLon;
    }
    /**
     * Set MinLon value
     * @param int $_minLon the MinLon
     * @return int
     */
    public function setMinLon($_minLon)
    {
        return ($this->MinLon = $_minLon);
    }
    /**
     * Get NumPoints value
     * @return int|null
     */
    public function getNumPoints()
    {
        return $this->NumPoints;
    }
    /**
     * Set NumPoints value
     * @param int $_numPoints the NumPoints
     * @return int
     */
    public function setNumPoints($_numPoints)
    {
        return ($this->NumPoints = $_numPoints);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructMapGroupInfo
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
