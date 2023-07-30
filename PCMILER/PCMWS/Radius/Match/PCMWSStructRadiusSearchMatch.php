<?php
/**
 * File for class PCMWSStructRadiusSearchMatch
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructRadiusSearchMatch originally named RadiusSearchMatch
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructRadiusSearchMatch extends PCMWSWsdlClass
{
    /**
     * The DistanceFromCenter
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructDistance
     */
    public $DistanceFromCenter;
    /**
     * The POICategory
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $POICategory;
    /**
     * The POILocation
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructLocation
     */
    public $POILocation;
    /**
     * The Info
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfKeyValuePairOfstringstring
     */
    public $Info;
    /**
     * Constructor method for RadiusSearchMatch
     * @see parent::__construct()
     * @param PCMWSStructDistance $_distanceFromCenter
     * @param string $_pOICategory
     * @param PCMWSStructLocation $_pOILocation
     * @param PCMWSStructArrayOfKeyValuePairOfstringstring $_info
     * @return PCMWSStructRadiusSearchMatch
     */
    public function __construct($_distanceFromCenter = NULL,$_pOICategory = NULL,$_pOILocation = NULL,$_info = NULL)
    {
        parent::__construct(array('DistanceFromCenter'=>$_distanceFromCenter,'POICategory'=>$_pOICategory,'POILocation'=>$_pOILocation,'Info'=>($_info instanceof PCMWSStructArrayOfKeyValuePairOfstringstring)?$_info:new PCMWSStructArrayOfKeyValuePairOfstringstring($_info)),false);
    }
    /**
     * Get DistanceFromCenter value
     * @return PCMWSStructDistance|null
     */
    public function getDistanceFromCenter()
    {
        return $this->DistanceFromCenter;
    }
    /**
     * Set DistanceFromCenter value
     * @param PCMWSStructDistance $_distanceFromCenter the DistanceFromCenter
     * @return PCMWSStructDistance
     */
    public function setDistanceFromCenter($_distanceFromCenter)
    {
        return ($this->DistanceFromCenter = $_distanceFromCenter);
    }
    /**
     * Get POICategory value
     * @return string|null
     */
    public function getPOICategory()
    {
        return $this->POICategory;
    }
    /**
     * Set POICategory value
     * @param string $_pOICategory the POICategory
     * @return string
     */
    public function setPOICategory($_pOICategory)
    {
        return ($this->POICategory = $_pOICategory);
    }
    /**
     * Get POILocation value
     * @return PCMWSStructLocation|null
     */
    public function getPOILocation()
    {
        return $this->POILocation;
    }
    /**
     * Set POILocation value
     * @param PCMWSStructLocation $_pOILocation the POILocation
     * @return PCMWSStructLocation
     */
    public function setPOILocation($_pOILocation)
    {
        return ($this->POILocation = $_pOILocation);
    }
    /**
     * Get Info value
     * @return PCMWSStructArrayOfKeyValuePairOfstringstring|null
     */
    public function getInfo()
    {
        return $this->Info;
    }
    /**
     * Set Info value
     * @param PCMWSStructArrayOfKeyValuePairOfstringstring $_info the Info
     * @return PCMWSStructArrayOfKeyValuePairOfstringstring
     */
    public function setInfo($_info)
    {
        return ($this->Info = $_info);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructRadiusSearchMatch
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
