<?php
/**
 * File for class PCMWSStructMapPointInfo
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructMapPointInfo originally named MapPointInfo
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructMapPointInfo extends PCMWSWsdlClass
{
    /**
     * The ClassName
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $ClassName;
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
     * The GroupID
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $GroupID;
    /**
     * The ID
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var unsignedInt
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
     * Constructor method for MapPointInfo
     * @see parent::__construct()
     * @param string $_className
     * @param int $_devX
     * @param int $_devY
     * @param int $_groupID
     * @param unsignedInt $_iD
     * @param int $_latitude
     * @param int $_longitude
     * @return PCMWSStructMapPointInfo
     */
    public function __construct($_className = NULL,$_devX = NULL,$_devY = NULL,$_groupID = NULL,$_iD = NULL,$_latitude = NULL,$_longitude = NULL)
    {
        parent::__construct(array('ClassName'=>$_className,'DevX'=>$_devX,'DevY'=>$_devY,'GroupID'=>$_groupID,'ID'=>$_iD,'Latitude'=>$_latitude,'Longitude'=>$_longitude),false);
    }
    /**
     * Get ClassName value
     * @return string|null
     */
    public function getClassName()
    {
        return $this->ClassName;
    }
    /**
     * Set ClassName value
     * @param string $_className the ClassName
     * @return string
     */
    public function setClassName($_className)
    {
        return ($this->ClassName = $_className);
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
     * Get GroupID value
     * @return int|null
     */
    public function getGroupID()
    {
        return $this->GroupID;
    }
    /**
     * Set GroupID value
     * @param int $_groupID the GroupID
     * @return int
     */
    public function setGroupID($_groupID)
    {
        return ($this->GroupID = $_groupID);
    }
    /**
     * Get ID value
     * @return unsignedInt|null
     */
    public function getID()
    {
        return $this->ID;
    }
    /**
     * Set ID value
     * @param unsignedInt $_iD the ID
     * @return unsignedInt
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
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructMapPointInfo
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
