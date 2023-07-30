<?php
/**
 * File for class PCMWSStructCreateRouteSyncMsgRequestBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructCreateRouteSyncMsgRequestBody originally named CreateRouteSyncMsgRequestBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructCreateRouteSyncMsgRequestBody extends PCMWSWsdlClass
{
    /**
     * The OutOfRouteDistance
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var double
     */
    public $OutOfRouteDistance;
    /**
     * The Compliance
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumOutOfRouteCompliance
     */
    public $Compliance;
    /**
     * The IsFirstLegManaged
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var boolean
     */
    public $IsFirstLegManaged;
    /**
     * The ManagedRoute
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructExtendedRoute
     */
    public $ManagedRoute;
    /**
     * The CreateRouteSyncFromPoints
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $CreateRouteSyncFromPoints;
    /**
     * The MessageVersion
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumRouteSyncMessageVersion
     */
    public $MessageVersion;
    /**
     * Constructor method for CreateRouteSyncMsgRequestBody
     * @see parent::__construct()
     * @param double $_outOfRouteDistance
     * @param PCMWSEnumOutOfRouteCompliance $_compliance
     * @param boolean $_isFirstLegManaged
     * @param PCMWSStructExtendedRoute $_managedRoute
     * @param boolean $_createRouteSyncFromPoints
     * @param PCMWSEnumRouteSyncMessageVersion $_messageVersion
     * @return PCMWSStructCreateRouteSyncMsgRequestBody
     */
    public function __construct($_outOfRouteDistance = NULL,$_compliance = NULL,$_isFirstLegManaged = NULL,$_managedRoute = NULL,$_createRouteSyncFromPoints = NULL,$_messageVersion = NULL)
    {
        parent::__construct(array('OutOfRouteDistance'=>$_outOfRouteDistance,'Compliance'=>$_compliance,'IsFirstLegManaged'=>$_isFirstLegManaged,'ManagedRoute'=>$_managedRoute,'CreateRouteSyncFromPoints'=>$_createRouteSyncFromPoints,'MessageVersion'=>$_messageVersion),false);
    }
    /**
     * Get OutOfRouteDistance value
     * @return double|null
     */
    public function getOutOfRouteDistance()
    {
        return $this->OutOfRouteDistance;
    }
    /**
     * Set OutOfRouteDistance value
     * @param double $_outOfRouteDistance the OutOfRouteDistance
     * @return double
     */
    public function setOutOfRouteDistance($_outOfRouteDistance)
    {
        return ($this->OutOfRouteDistance = $_outOfRouteDistance);
    }
    /**
     * Get Compliance value
     * @return PCMWSEnumOutOfRouteCompliance|null
     */
    public function getCompliance()
    {
        return $this->Compliance;
    }
    /**
     * Set Compliance value
     * @uses PCMWSEnumOutOfRouteCompliance::valueIsValid()
     * @param PCMWSEnumOutOfRouteCompliance $_compliance the Compliance
     * @return PCMWSEnumOutOfRouteCompliance
     */
    public function setCompliance($_compliance)
    {
        if(!PCMWSEnumOutOfRouteCompliance::valueIsValid($_compliance))
        {
            return false;
        }
        return ($this->Compliance = $_compliance);
    }
    /**
     * Get IsFirstLegManaged value
     * @return boolean|null
     */
    public function getIsFirstLegManaged()
    {
        return $this->IsFirstLegManaged;
    }
    /**
     * Set IsFirstLegManaged value
     * @param boolean $_isFirstLegManaged the IsFirstLegManaged
     * @return boolean
     */
    public function setIsFirstLegManaged($_isFirstLegManaged)
    {
        return ($this->IsFirstLegManaged = $_isFirstLegManaged);
    }
    /**
     * Get ManagedRoute value
     * @return PCMWSStructExtendedRoute|null
     */
    public function getManagedRoute()
    {
        return $this->ManagedRoute;
    }
    /**
     * Set ManagedRoute value
     * @param PCMWSStructExtendedRoute $_managedRoute the ManagedRoute
     * @return PCMWSStructExtendedRoute
     */
    public function setManagedRoute($_managedRoute)
    {
        return ($this->ManagedRoute = $_managedRoute);
    }
    /**
     * Get CreateRouteSyncFromPoints value
     * @return boolean|null
     */
    public function getCreateRouteSyncFromPoints()
    {
        return $this->CreateRouteSyncFromPoints;
    }
    /**
     * Set CreateRouteSyncFromPoints value
     * @param boolean $_createRouteSyncFromPoints the CreateRouteSyncFromPoints
     * @return boolean
     */
    public function setCreateRouteSyncFromPoints($_createRouteSyncFromPoints)
    {
        return ($this->CreateRouteSyncFromPoints = $_createRouteSyncFromPoints);
    }
    /**
     * Get MessageVersion value
     * @return PCMWSEnumRouteSyncMessageVersion|null
     */
    public function getMessageVersion()
    {
        return $this->MessageVersion;
    }
    /**
     * Set MessageVersion value
     * @uses PCMWSEnumRouteSyncMessageVersion::valueIsValid()
     * @param PCMWSEnumRouteSyncMessageVersion $_messageVersion the MessageVersion
     * @return PCMWSEnumRouteSyncMessageVersion
     */
    public function setMessageVersion($_messageVersion)
    {
        if(!PCMWSEnumRouteSyncMessageVersion::valueIsValid($_messageVersion))
        {
            return false;
        }
        return ($this->MessageVersion = $_messageVersion);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructCreateRouteSyncMsgRequestBody
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
