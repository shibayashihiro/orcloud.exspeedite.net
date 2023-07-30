<?php
/**
 * File for class PCMWSStructGetCustomPlaceSetRequestBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructGetCustomPlaceSetRequestBody originally named GetCustomPlaceSetRequestBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructGetCustomPlaceSetRequestBody extends PCMWSStructCustomDataSetRequestBody
{
    /**
     * The PlaceName
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $PlaceName;
    /**
     * The CategoryId
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var int
     */
    public $CategoryId;
    /**
     * The CategoryName
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $CategoryName;
    /**
     * The Corner1
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructCoordinates
     */
    public $Corner1;
    /**
     * The Corner2
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructCoordinates
     */
    public $Corner2;
    /**
     * The IncludePlaces
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $IncludePlaces;
    /**
     * Constructor method for GetCustomPlaceSetRequestBody
     * @see parent::__construct()
     * @param string $_placeName
     * @param int $_categoryId
     * @param string $_categoryName
     * @param PCMWSStructCoordinates $_corner1
     * @param PCMWSStructCoordinates $_corner2
     * @param boolean $_includePlaces
     * @return PCMWSStructGetCustomPlaceSetRequestBody
     */
    public function __construct($_placeName = NULL,$_categoryId = NULL,$_categoryName = NULL,$_corner1 = NULL,$_corner2 = NULL,$_includePlaces = NULL)
    {
        PCMWSWsdlClass::__construct(array('PlaceName'=>$_placeName,'CategoryId'=>$_categoryId,'CategoryName'=>$_categoryName,'Corner1'=>$_corner1,'Corner2'=>$_corner2,'IncludePlaces'=>$_includePlaces),false);
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
     * Get CategoryId value
     * @return int|null
     */
    public function getCategoryId()
    {
        return $this->CategoryId;
    }
    /**
     * Set CategoryId value
     * @param int $_categoryId the CategoryId
     * @return int
     */
    public function setCategoryId($_categoryId)
    {
        return ($this->CategoryId = $_categoryId);
    }
    /**
     * Get CategoryName value
     * @return string|null
     */
    public function getCategoryName()
    {
        return $this->CategoryName;
    }
    /**
     * Set CategoryName value
     * @param string $_categoryName the CategoryName
     * @return string
     */
    public function setCategoryName($_categoryName)
    {
        return ($this->CategoryName = $_categoryName);
    }
    /**
     * Get Corner1 value
     * @return PCMWSStructCoordinates|null
     */
    public function getCorner1()
    {
        return $this->Corner1;
    }
    /**
     * Set Corner1 value
     * @param PCMWSStructCoordinates $_corner1 the Corner1
     * @return PCMWSStructCoordinates
     */
    public function setCorner1($_corner1)
    {
        return ($this->Corner1 = $_corner1);
    }
    /**
     * Get Corner2 value
     * @return PCMWSStructCoordinates|null
     */
    public function getCorner2()
    {
        return $this->Corner2;
    }
    /**
     * Set Corner2 value
     * @param PCMWSStructCoordinates $_corner2 the Corner2
     * @return PCMWSStructCoordinates
     */
    public function setCorner2($_corner2)
    {
        return ($this->Corner2 = $_corner2);
    }
    /**
     * Get IncludePlaces value
     * @return boolean|null
     */
    public function getIncludePlaces()
    {
        return $this->IncludePlaces;
    }
    /**
     * Set IncludePlaces value
     * @param boolean $_includePlaces the IncludePlaces
     * @return boolean
     */
    public function setIncludePlaces($_includePlaces)
    {
        return ($this->IncludePlaces = $_includePlaces);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructGetCustomPlaceSetRequestBody
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
