<?php
/**
 * File for class PCMWSStructRenderedMap
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructRenderedMap originally named RenderedMap
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructRenderedMap extends PCMWSWsdlClass
{
    /**
     * The Center
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructCoordinates
     */
    public $Center;
    /**
     * The CornerA
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructCoordinates
     */
    public $CornerA;
    /**
     * The CornerB
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructCoordinates
     */
    public $CornerB;
    /**
     * The Groups
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfMapGroupInfo
     */
    public $Groups;
    /**
     * The Height
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $Height;
    /**
     * The Layers
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfLayer
     */
    public $Layers;
    /**
     * The Points
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfMapPointInfo
     */
    public $Points;
    /**
     * The Region
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumMapRegion
     */
    public $Region;
    /**
     * The Width
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $Width;
    /**
     * Constructor method for RenderedMap
     * @see parent::__construct()
     * @param PCMWSStructCoordinates $_center
     * @param PCMWSStructCoordinates $_cornerA
     * @param PCMWSStructCoordinates $_cornerB
     * @param PCMWSStructArrayOfMapGroupInfo $_groups
     * @param int $_height
     * @param PCMWSStructArrayOfLayer $_layers
     * @param PCMWSStructArrayOfMapPointInfo $_points
     * @param PCMWSEnumMapRegion $_region
     * @param int $_width
     * @return PCMWSStructRenderedMap
     */
    public function __construct($_center = NULL,$_cornerA = NULL,$_cornerB = NULL,$_groups = NULL,$_height = NULL,$_layers = NULL,$_points = NULL,$_region = NULL,$_width = NULL)
    {
        parent::__construct(array('Center'=>$_center,'CornerA'=>$_cornerA,'CornerB'=>$_cornerB,'Groups'=>($_groups instanceof PCMWSStructArrayOfMapGroupInfo)?$_groups:new PCMWSStructArrayOfMapGroupInfo($_groups),'Height'=>$_height,'Layers'=>($_layers instanceof PCMWSStructArrayOfLayer)?$_layers:new PCMWSStructArrayOfLayer($_layers),'Points'=>($_points instanceof PCMWSStructArrayOfMapPointInfo)?$_points:new PCMWSStructArrayOfMapPointInfo($_points),'Region'=>$_region,'Width'=>$_width),false);
    }
    /**
     * Get Center value
     * @return PCMWSStructCoordinates|null
     */
    public function getCenter()
    {
        return $this->Center;
    }
    /**
     * Set Center value
     * @param PCMWSStructCoordinates $_center the Center
     * @return PCMWSStructCoordinates
     */
    public function setCenter($_center)
    {
        return ($this->Center = $_center);
    }
    /**
     * Get CornerA value
     * @return PCMWSStructCoordinates|null
     */
    public function getCornerA()
    {
        return $this->CornerA;
    }
    /**
     * Set CornerA value
     * @param PCMWSStructCoordinates $_cornerA the CornerA
     * @return PCMWSStructCoordinates
     */
    public function setCornerA($_cornerA)
    {
        return ($this->CornerA = $_cornerA);
    }
    /**
     * Get CornerB value
     * @return PCMWSStructCoordinates|null
     */
    public function getCornerB()
    {
        return $this->CornerB;
    }
    /**
     * Set CornerB value
     * @param PCMWSStructCoordinates $_cornerB the CornerB
     * @return PCMWSStructCoordinates
     */
    public function setCornerB($_cornerB)
    {
        return ($this->CornerB = $_cornerB);
    }
    /**
     * Get Groups value
     * @return PCMWSStructArrayOfMapGroupInfo|null
     */
    public function getGroups()
    {
        return $this->Groups;
    }
    /**
     * Set Groups value
     * @param PCMWSStructArrayOfMapGroupInfo $_groups the Groups
     * @return PCMWSStructArrayOfMapGroupInfo
     */
    public function setGroups($_groups)
    {
        return ($this->Groups = $_groups);
    }
    /**
     * Get Height value
     * @return int|null
     */
    public function getHeight()
    {
        return $this->Height;
    }
    /**
     * Set Height value
     * @param int $_height the Height
     * @return int
     */
    public function setHeight($_height)
    {
        return ($this->Height = $_height);
    }
    /**
     * Get Layers value
     * @return PCMWSStructArrayOfLayer|null
     */
    public function getLayers()
    {
        return $this->Layers;
    }
    /**
     * Set Layers value
     * @param PCMWSStructArrayOfLayer $_layers the Layers
     * @return PCMWSStructArrayOfLayer
     */
    public function setLayers($_layers)
    {
        return ($this->Layers = $_layers);
    }
    /**
     * Get Points value
     * @return PCMWSStructArrayOfMapPointInfo|null
     */
    public function getPoints()
    {
        return $this->Points;
    }
    /**
     * Set Points value
     * @param PCMWSStructArrayOfMapPointInfo $_points the Points
     * @return PCMWSStructArrayOfMapPointInfo
     */
    public function setPoints($_points)
    {
        return ($this->Points = $_points);
    }
    /**
     * Get Region value
     * @return PCMWSEnumMapRegion|null
     */
    public function getRegion()
    {
        return $this->Region;
    }
    /**
     * Set Region value
     * @uses PCMWSEnumMapRegion::valueIsValid()
     * @param PCMWSEnumMapRegion $_region the Region
     * @return PCMWSEnumMapRegion
     */
    public function setRegion($_region)
    {
        if(!PCMWSEnumMapRegion::valueIsValid($_region))
        {
            return false;
        }
        return ($this->Region = $_region);
    }
    /**
     * Get Width value
     * @return int|null
     */
    public function getWidth()
    {
        return $this->Width;
    }
    /**
     * Set Width value
     * @param int $_width the Width
     * @return int
     */
    public function setWidth($_width)
    {
        return ($this->Width = $_width);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructRenderedMap
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
