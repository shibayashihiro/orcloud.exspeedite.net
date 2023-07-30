<?php
/**
 * File for class PCMWSStructMapRequestBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructMapRequestBody originally named MapRequestBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructMapRequestBody extends PCMWSWsdlClass
{
    /**
     * The Viewport
     * Meta informations extracted from the WSDL
     * - nillable : true
     * @var PCMWSStructMapViewport
     */
    public $Viewport;
    /**
     * The Projection
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumProjectionType
     */
    public $Projection;
    /**
     * The Style
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumMapStyle
     */
    public $Style;
    /**
     * The ImageOption
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumMapImageOption
     */
    public $ImageOption;
    /**
     * The Width
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $Width;
    /**
     * The Height
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $Height;
    /**
     * The Drawers
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfDrawerType
     */
    public $Drawers;
    /**
     * The LegendDrawer
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfLegend
     */
    public $LegendDrawer;
    /**
     * The GeometryDrawer
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfGeometry
     */
    public $GeometryDrawer;
    /**
     * The PinDrawer
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructPinDrawer
     */
    public $PinDrawer;
    /**
     * The PinCategories
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfPinCategory
     */
    public $PinCategories;
    /**
     * The TrafficDrawer
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructTrafficDrawer
     */
    public $TrafficDrawer;
    /**
     * The MapLayering
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumMapLayering
     */
    public $MapLayering;
    /**
     * The Language
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Language;
    /**
     * The ImageSource
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSEnumBackgroundImageProvider
     */
    public $ImageSource;
    /**
     * Constructor method for MapRequestBody
     * @see parent::__construct()
     * @param PCMWSStructMapViewport $_viewport
     * @param PCMWSEnumProjectionType $_projection
     * @param PCMWSEnumMapStyle $_style
     * @param PCMWSEnumMapImageOption $_imageOption
     * @param int $_width
     * @param int $_height
     * @param PCMWSStructArrayOfDrawerType $_drawers
     * @param PCMWSStructArrayOfLegend $_legendDrawer
     * @param PCMWSStructArrayOfGeometry $_geometryDrawer
     * @param PCMWSStructPinDrawer $_pinDrawer
     * @param PCMWSStructArrayOfPinCategory $_pinCategories
     * @param PCMWSStructTrafficDrawer $_trafficDrawer
     * @param PCMWSEnumMapLayering $_mapLayering
     * @param string $_language
     * @param PCMWSEnumBackgroundImageProvider $_imageSource
     * @return PCMWSStructMapRequestBody
     */
    public function __construct($_viewport = NULL,$_projection = NULL,$_style = NULL,$_imageOption = NULL,$_width = NULL,$_height = NULL,$_drawers = NULL,$_legendDrawer = NULL,$_geometryDrawer = NULL,$_pinDrawer = NULL,$_pinCategories = NULL,$_trafficDrawer = NULL,$_mapLayering = NULL,$_language = NULL,$_imageSource = NULL)
    {
        parent::__construct(array('Viewport'=>$_viewport,'Projection'=>$_projection,'Style'=>$_style,'ImageOption'=>$_imageOption,'Width'=>$_width,'Height'=>$_height,'Drawers'=>($_drawers instanceof PCMWSStructArrayOfDrawerType)?$_drawers:new PCMWSStructArrayOfDrawerType($_drawers),'LegendDrawer'=>($_legendDrawer instanceof PCMWSStructArrayOfLegend)?$_legendDrawer:new PCMWSStructArrayOfLegend($_legendDrawer),'GeometryDrawer'=>($_geometryDrawer instanceof PCMWSStructArrayOfGeometry)?$_geometryDrawer:new PCMWSStructArrayOfGeometry($_geometryDrawer),'PinDrawer'=>$_pinDrawer,'PinCategories'=>($_pinCategories instanceof PCMWSStructArrayOfPinCategory)?$_pinCategories:new PCMWSStructArrayOfPinCategory($_pinCategories),'TrafficDrawer'=>$_trafficDrawer,'MapLayering'=>$_mapLayering,'Language'=>$_language,'ImageSource'=>$_imageSource),false);
    }
    /**
     * Get Viewport value
     * @return PCMWSStructMapViewport|null
     */
    public function getViewport()
    {
        return $this->Viewport;
    }
    /**
     * Set Viewport value
     * @param PCMWSStructMapViewport $_viewport the Viewport
     * @return PCMWSStructMapViewport
     */
    public function setViewport($_viewport)
    {
        return ($this->Viewport = $_viewport);
    }
    /**
     * Get Projection value
     * @return PCMWSEnumProjectionType|null
     */
    public function getProjection()
    {
        return $this->Projection;
    }
    /**
     * Set Projection value
     * @uses PCMWSEnumProjectionType::valueIsValid()
     * @param PCMWSEnumProjectionType $_projection the Projection
     * @return PCMWSEnumProjectionType
     */
    public function setProjection($_projection)
    {
        if(!PCMWSEnumProjectionType::valueIsValid($_projection))
        {
            return false;
        }
        return ($this->Projection = $_projection);
    }
    /**
     * Get Style value
     * @return PCMWSEnumMapStyle|null
     */
    public function getStyle()
    {
        return $this->Style;
    }
    /**
     * Set Style value
     * @uses PCMWSEnumMapStyle::valueIsValid()
     * @param PCMWSEnumMapStyle $_style the Style
     * @return PCMWSEnumMapStyle
     */
    public function setStyle($_style)
    {
        if(!PCMWSEnumMapStyle::valueIsValid($_style))
        {
            return false;
        }
        return ($this->Style = $_style);
    }
    /**
     * Get ImageOption value
     * @return PCMWSEnumMapImageOption|null
     */
    public function getImageOption()
    {
        return $this->ImageOption;
    }
    /**
     * Set ImageOption value
     * @uses PCMWSEnumMapImageOption::valueIsValid()
     * @param PCMWSEnumMapImageOption $_imageOption the ImageOption
     * @return PCMWSEnumMapImageOption
     */
    public function setImageOption($_imageOption)
    {
        if(!PCMWSEnumMapImageOption::valueIsValid($_imageOption))
        {
            return false;
        }
        return ($this->ImageOption = $_imageOption);
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
     * Get Drawers value
     * @return PCMWSStructArrayOfDrawerType|null
     */
    public function getDrawers()
    {
        return $this->Drawers;
    }
    /**
     * Set Drawers value
     * @param PCMWSStructArrayOfDrawerType $_drawers the Drawers
     * @return PCMWSStructArrayOfDrawerType
     */
    public function setDrawers($_drawers)
    {
        return ($this->Drawers = $_drawers);
    }
    /**
     * Get LegendDrawer value
     * @return PCMWSStructArrayOfLegend|null
     */
    public function getLegendDrawer()
    {
        return $this->LegendDrawer;
    }
    /**
     * Set LegendDrawer value
     * @param PCMWSStructArrayOfLegend $_legendDrawer the LegendDrawer
     * @return PCMWSStructArrayOfLegend
     */
    public function setLegendDrawer($_legendDrawer)
    {
        return ($this->LegendDrawer = $_legendDrawer);
    }
    /**
     * Get GeometryDrawer value
     * @return PCMWSStructArrayOfGeometry|null
     */
    public function getGeometryDrawer()
    {
        return $this->GeometryDrawer;
    }
    /**
     * Set GeometryDrawer value
     * @param PCMWSStructArrayOfGeometry $_geometryDrawer the GeometryDrawer
     * @return PCMWSStructArrayOfGeometry
     */
    public function setGeometryDrawer($_geometryDrawer)
    {
        return ($this->GeometryDrawer = $_geometryDrawer);
    }
    /**
     * Get PinDrawer value
     * @return PCMWSStructPinDrawer|null
     */
    public function getPinDrawer()
    {
        return $this->PinDrawer;
    }
    /**
     * Set PinDrawer value
     * @param PCMWSStructPinDrawer $_pinDrawer the PinDrawer
     * @return PCMWSStructPinDrawer
     */
    public function setPinDrawer($_pinDrawer)
    {
        return ($this->PinDrawer = $_pinDrawer);
    }
    /**
     * Get PinCategories value
     * @return PCMWSStructArrayOfPinCategory|null
     */
    public function getPinCategories()
    {
        return $this->PinCategories;
    }
    /**
     * Set PinCategories value
     * @param PCMWSStructArrayOfPinCategory $_pinCategories the PinCategories
     * @return PCMWSStructArrayOfPinCategory
     */
    public function setPinCategories($_pinCategories)
    {
        return ($this->PinCategories = $_pinCategories);
    }
    /**
     * Get TrafficDrawer value
     * @return PCMWSStructTrafficDrawer|null
     */
    public function getTrafficDrawer()
    {
        return $this->TrafficDrawer;
    }
    /**
     * Set TrafficDrawer value
     * @param PCMWSStructTrafficDrawer $_trafficDrawer the TrafficDrawer
     * @return PCMWSStructTrafficDrawer
     */
    public function setTrafficDrawer($_trafficDrawer)
    {
        return ($this->TrafficDrawer = $_trafficDrawer);
    }
    /**
     * Get MapLayering value
     * @return PCMWSEnumMapLayering|null
     */
    public function getMapLayering()
    {
        return $this->MapLayering;
    }
    /**
     * Set MapLayering value
     * @uses PCMWSEnumMapLayering::valueIsValid()
     * @param PCMWSEnumMapLayering $_mapLayering the MapLayering
     * @return PCMWSEnumMapLayering
     */
    public function setMapLayering($_mapLayering)
    {
        if(!PCMWSEnumMapLayering::valueIsValid($_mapLayering))
        {
            return false;
        }
        return ($this->MapLayering = $_mapLayering);
    }
    /**
     * Get Language value
     * @return string|null
     */
    public function getLanguage()
    {
        return $this->Language;
    }
    /**
     * Set Language value
     * @param string $_language the Language
     * @return string
     */
    public function setLanguage($_language)
    {
        return ($this->Language = $_language);
    }
    /**
     * Get ImageSource value
     * @return PCMWSEnumBackgroundImageProvider|null
     */
    public function getImageSource()
    {
        return $this->ImageSource;
    }
    /**
     * Set ImageSource value
     * @uses PCMWSEnumBackgroundImageProvider::valueIsValid()
     * @param PCMWSEnumBackgroundImageProvider $_imageSource the ImageSource
     * @return PCMWSEnumBackgroundImageProvider
     */
    public function setImageSource($_imageSource)
    {
        if(!PCMWSEnumBackgroundImageProvider::valueIsValid($_imageSource))
        {
            return false;
        }
        return ($this->ImageSource = $_imageSource);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructMapRequestBody
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
