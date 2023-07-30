<?php
/**
 * File for class PCMWSStructRadiusSearchRequestBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructRadiusSearchRequestBody originally named RadiusSearchRequestBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructRadiusSearchRequestBody extends PCMWSWsdlClass
{
    /**
     * The CenterPoint
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructLocation
     */
    public $CenterPoint;
    /**
     * The Radius
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructDistance
     */
    public $Radius;
    /**
     * The POICategories
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumPOIType
     */
    public $POICategories;
    /**
     * The NameFilter
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $NameFilter;
    /**
     * Constructor method for RadiusSearchRequestBody
     * @see parent::__construct()
     * @param PCMWSStructLocation $_centerPoint
     * @param PCMWSStructDistance $_radius
     * @param PCMWSEnumPOIType $_pOICategories
     * @param string $_nameFilter
     * @return PCMWSStructRadiusSearchRequestBody
     */
    public function __construct($_centerPoint = NULL,$_radius = NULL,$_pOICategories = NULL,$_nameFilter = NULL)
    {
        parent::__construct(array('CenterPoint'=>$_centerPoint,'Radius'=>$_radius,'POICategories'=>$_pOICategories,'NameFilter'=>$_nameFilter),false);
    }
    /**
     * Get CenterPoint value
     * @return PCMWSStructLocation|null
     */
    public function getCenterPoint()
    {
        return $this->CenterPoint;
    }
    /**
     * Set CenterPoint value
     * @param PCMWSStructLocation $_centerPoint the CenterPoint
     * @return PCMWSStructLocation
     */
    public function setCenterPoint($_centerPoint)
    {
        return ($this->CenterPoint = $_centerPoint);
    }
    /**
     * Get Radius value
     * @return PCMWSStructDistance|null
     */
    public function getRadius()
    {
        return $this->Radius;
    }
    /**
     * Set Radius value
     * @param PCMWSStructDistance $_radius the Radius
     * @return PCMWSStructDistance
     */
    public function setRadius($_radius)
    {
        return ($this->Radius = $_radius);
    }
    /**
     * Get POICategories value
     * @return PCMWSEnumPOIType|null
     */
    public function getPOICategories()
    {
        return $this->POICategories;
    }
    /**
     * Set POICategories value
     * @uses PCMWSEnumPOIType::valueIsValid()
     * @param PCMWSEnumPOIType $_pOICategories the POICategories
     * @return PCMWSEnumPOIType
     */
    public function setPOICategories($_pOICategories)
    {
        if(!PCMWSEnumPOIType::valueIsValid($_pOICategories))
        {
            return false;
        }
        return ($this->POICategories = $_pOICategories);
    }
    /**
     * Get NameFilter value
     * @return string|null
     */
    public function getNameFilter()
    {
        return $this->NameFilter;
    }
    /**
     * Set NameFilter value
     * @param string $_nameFilter the NameFilter
     * @return string
     */
    public function setNameFilter($_nameFilter)
    {
        return ($this->NameFilter = $_nameFilter);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructRadiusSearchRequestBody
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
