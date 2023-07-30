<?php
/**
 * File for class PCMWSStructALKStyle
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructALKStyle originally named ALKStyle
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructALKStyle extends PCMWSWsdlClass
{
    /**
     * The Font
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructALKFont
     */
    public $Font;
    /**
     * The Pen
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructALKPen
     */
    public $Pen;
    /**
     * The Brush
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructALKBrush
     */
    public $Brush;
    /**
     * The ImageShadow
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructALKShadow
     */
    public $ImageShadow;
    /**
     * The IndividualImageName
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $IndividualImageName;
    /**
     * The GroupImageName
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $GroupImageName;
    /**
     * Constructor method for ALKStyle
     * @see parent::__construct()
     * @param PCMWSStructALKFont $_font
     * @param PCMWSStructALKPen $_pen
     * @param PCMWSStructALKBrush $_brush
     * @param PCMWSStructALKShadow $_imageShadow
     * @param string $_individualImageName
     * @param string $_groupImageName
     * @return PCMWSStructALKStyle
     */
    public function __construct($_font = NULL,$_pen = NULL,$_brush = NULL,$_imageShadow = NULL,$_individualImageName = NULL,$_groupImageName = NULL)
    {
        parent::__construct(array('Font'=>$_font,'Pen'=>$_pen,'Brush'=>$_brush,'ImageShadow'=>$_imageShadow,'IndividualImageName'=>$_individualImageName,'GroupImageName'=>$_groupImageName),false);
    }
    /**
     * Get Font value
     * @return PCMWSStructALKFont|null
     */
    public function getFont()
    {
        return $this->Font;
    }
    /**
     * Set Font value
     * @param PCMWSStructALKFont $_font the Font
     * @return PCMWSStructALKFont
     */
    public function setFont($_font)
    {
        return ($this->Font = $_font);
    }
    /**
     * Get Pen value
     * @return PCMWSStructALKPen|null
     */
    public function getPen()
    {
        return $this->Pen;
    }
    /**
     * Set Pen value
     * @param PCMWSStructALKPen $_pen the Pen
     * @return PCMWSStructALKPen
     */
    public function setPen($_pen)
    {
        return ($this->Pen = $_pen);
    }
    /**
     * Get Brush value
     * @return PCMWSStructALKBrush|null
     */
    public function getBrush()
    {
        return $this->Brush;
    }
    /**
     * Set Brush value
     * @param PCMWSStructALKBrush $_brush the Brush
     * @return PCMWSStructALKBrush
     */
    public function setBrush($_brush)
    {
        return ($this->Brush = $_brush);
    }
    /**
     * Get ImageShadow value
     * @return PCMWSStructALKShadow|null
     */
    public function getImageShadow()
    {
        return $this->ImageShadow;
    }
    /**
     * Set ImageShadow value
     * @param PCMWSStructALKShadow $_imageShadow the ImageShadow
     * @return PCMWSStructALKShadow
     */
    public function setImageShadow($_imageShadow)
    {
        return ($this->ImageShadow = $_imageShadow);
    }
    /**
     * Get IndividualImageName value
     * @return string|null
     */
    public function getIndividualImageName()
    {
        return $this->IndividualImageName;
    }
    /**
     * Set IndividualImageName value
     * @param string $_individualImageName the IndividualImageName
     * @return string
     */
    public function setIndividualImageName($_individualImageName)
    {
        return ($this->IndividualImageName = $_individualImageName);
    }
    /**
     * Get GroupImageName value
     * @return string|null
     */
    public function getGroupImageName()
    {
        return $this->GroupImageName;
    }
    /**
     * Set GroupImageName value
     * @param string $_groupImageName the GroupImageName
     * @return string
     */
    public function setGroupImageName($_groupImageName)
    {
        return ($this->GroupImageName = $_groupImageName);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructALKStyle
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
