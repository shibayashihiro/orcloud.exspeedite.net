<?php
/**
 * File for class PCMWSStructGeofenceSet
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructGeofenceSet originally named GeofenceSet
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructGeofenceSet extends PCMWSStructAddGeofenceSet
{
    /**
     * The Deleted
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $Deleted;
    /**
     * The Fences
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfGeofence
     */
    public $Fences;
    /**
     * The Id
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $Id;
    /**
     * Constructor method for GeofenceSet
     * @see parent::__construct()
     * @param boolean $_deleted
     * @param PCMWSStructArrayOfGeofence $_fences
     * @param int $_id
     * @return PCMWSStructGeofenceSet
     */
    public function __construct($_deleted = NULL,$_fences = NULL,$_id = NULL)
    {
        PCMWSWsdlClass::__construct(array('Deleted'=>$_deleted,'Fences'=>($_fences instanceof PCMWSStructArrayOfGeofence)?$_fences:new PCMWSStructArrayOfGeofence($_fences),'Id'=>$_id),false);
    }
    /**
     * Get Deleted value
     * @return boolean|null
     */
    public function getDeleted()
    {
        return $this->Deleted;
    }
    /**
     * Set Deleted value
     * @param boolean $_deleted the Deleted
     * @return boolean
     */
    public function setDeleted($_deleted)
    {
        return ($this->Deleted = $_deleted);
    }
    /**
     * Get Fences value
     * @return PCMWSStructArrayOfGeofence|null
     */
    public function getFences()
    {
        return $this->Fences;
    }
    /**
     * Set Fences value
     * @param PCMWSStructArrayOfGeofence $_fences the Fences
     * @return PCMWSStructArrayOfGeofence
     */
    public function setFences($_fences)
    {
        return ($this->Fences = $_fences);
    }
    /**
     * Get Id value
     * @return int|null
     */
    public function getId()
    {
        return $this->Id;
    }
    /**
     * Set Id value
     * @param int $_id the Id
     * @return int
     */
    public function setId($_id)
    {
        return ($this->Id = $_id);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructGeofenceSet
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
