<?php
/**
 * File for class PCMWSStructALKShadow
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructALKShadow originally named ALKShadow
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructALKShadow extends PCMWSWsdlClass
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
     * @var unsignedByte
     */
    public $Opacity;
    /**
     * The OffsetX
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var byte
     */
    public $OffsetX;
    /**
     * The OffsetY
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var byte
     */
    public $OffsetY;
    /**
     * Constructor method for ALKShadow
     * @see parent::__construct()
     * @param PCMWSStructRGB $_color
     * @param unsignedByte $_opacity
     * @param byte $_offsetX
     * @param byte $_offsetY
     * @return PCMWSStructALKShadow
     */
    public function __construct($_color = NULL,$_opacity = NULL,$_offsetX = NULL,$_offsetY = NULL)
    {
        parent::__construct(array('Color'=>$_color,'Opacity'=>$_opacity,'OffsetX'=>$_offsetX,'OffsetY'=>$_offsetY),false);
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
     * @return unsignedByte|null
     */
    public function getOpacity()
    {
        return $this->Opacity;
    }
    /**
     * Set Opacity value
     * @param unsignedByte $_opacity the Opacity
     * @return unsignedByte
     */
    public function setOpacity($_opacity)
    {
        return ($this->Opacity = $_opacity);
    }
    /**
     * Get OffsetX value
     * @return byte|null
     */
    public function getOffsetX()
    {
        return $this->OffsetX;
    }
    /**
     * Set OffsetX value
     * @param byte $_offsetX the OffsetX
     * @return byte
     */
    public function setOffsetX($_offsetX)
    {
        return ($this->OffsetX = $_offsetX);
    }
    /**
     * Get OffsetY value
     * @return byte|null
     */
    public function getOffsetY()
    {
        return $this->OffsetY;
    }
    /**
     * Set OffsetY value
     * @param byte $_offsetY the OffsetY
     * @return byte
     */
    public function setOffsetY($_offsetY)
    {
        return ($this->OffsetY = $_offsetY);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructALKShadow
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
