<?php
/**
 * File for class PCMWSStructALKFont
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructALKFont originally named ALKFont
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructALKFont extends PCMWSWsdlClass
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
     * The Height
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var unsignedByte
     */
    public $Height;
    /**
     * The Weight
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var unsignedByte
     */
    public $Weight;
    /**
     * Constructor method for ALKFont
     * @see parent::__construct()
     * @param PCMWSStructRGB $_color
     * @param unsignedByte $_height
     * @param unsignedByte $_weight
     * @return PCMWSStructALKFont
     */
    public function __construct($_color = NULL,$_height = NULL,$_weight = NULL)
    {
        parent::__construct(array('Color'=>$_color,'Height'=>$_height,'Weight'=>$_weight),false);
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
     * Get Height value
     * @return unsignedByte|null
     */
    public function getHeight()
    {
        return $this->Height;
    }
    /**
     * Set Height value
     * @param unsignedByte $_height the Height
     * @return unsignedByte
     */
    public function setHeight($_height)
    {
        return ($this->Height = $_height);
    }
    /**
     * Get Weight value
     * @return unsignedByte|null
     */
    public function getWeight()
    {
        return $this->Weight;
    }
    /**
     * Set Weight value
     * @param unsignedByte $_weight the Weight
     * @return unsignedByte
     */
    public function setWeight($_weight)
    {
        return ($this->Weight = $_weight);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructALKFont
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
