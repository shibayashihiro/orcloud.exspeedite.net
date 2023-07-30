<?php
/**
 * File for class PCMWSStructRouteLineOptions
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructRouteLineOptions originally named RouteLineOptions
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructRouteLineOptions extends PCMWSWsdlClass
{
    /**
     * The Color
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructRGB
     */
    public $Color;
    /**
     * The Width
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $Width;
    /**
     * Constructor method for RouteLineOptions
     * @see parent::__construct()
     * @param PCMWSStructRGB $_color
     * @param int $_width
     * @return PCMWSStructRouteLineOptions
     */
    public function __construct($_color = NULL,$_width = NULL)
    {
        parent::__construct(array('Color'=>$_color,'Width'=>$_width),false);
    }
    /**
     * Get Color value
     * @return PCMWSStructRGB|null
     */
    public function getColor()
    {
        return $this->Color;
    }
    /**
     * Set Color value
     * @param PCMWSStructRGB $_color the Color
     * @return PCMWSStructRGB
     */
    public function setColor($_color)
    {
        return ($this->Color = $_color);
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
     * @return PCMWSStructRouteLineOptions
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
