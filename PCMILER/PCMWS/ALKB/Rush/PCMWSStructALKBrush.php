<?php
/**
 * File for class PCMWSStructALKBrush
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructALKBrush originally named ALKBrush
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructALKBrush extends PCMWSWsdlClass
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
     * The Opacity
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var unsignedShort
     */
    public $Opacity;
    /**
     * Constructor method for ALKBrush
     * @see parent::__construct()
     * @param PCMWSStructRGB $_color
     * @param unsignedShort $_opacity
     * @return PCMWSStructALKBrush
     */
    public function __construct($_color = NULL,$_opacity = NULL)
    {
        parent::__construct(array('Color'=>$_color,'Opacity'=>$_opacity),false);
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
     * Get Opacity value
     * @return unsignedShort|null
     */
    public function getOpacity()
    {
        return $this->Opacity;
    }
    /**
     * Set Opacity value
     * @param unsignedShort $_opacity the Opacity
     * @return unsignedShort
     */
    public function setOpacity($_opacity)
    {
        return ($this->Opacity = $_opacity);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructALKBrush
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
