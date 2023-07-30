<?php
/**
 * File for class PCMWSStructPinCategory
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructPinCategory originally named PinCategory
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructPinCategory extends PCMWSWsdlClass
{
    /**
     * The ImageName
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $ImageName;
    /**
     * The ImageNameForIndividual
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $ImageNameForIndividual;
    /**
     * The Name
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Name;
    /**
     * The PinType
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumPinType
     */
    public $PinType;
    /**
     * The Style
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructALKStyle
     */
    public $Style;
    /**
     * The ZOrder
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $ZOrder;
    /**
     * Constructor method for PinCategory
     * @see parent::__construct()
     * @param string $_imageName
     * @param string $_imageNameForIndividual
     * @param string $_name
     * @param PCMWSEnumPinType $_pinType
     * @param PCMWSStructALKStyle $_style
     * @param int $_zOrder
     * @return PCMWSStructPinCategory
     */
    public function __construct($_imageName = NULL,$_imageNameForIndividual = NULL,$_name = NULL,$_pinType = NULL,$_style = NULL,$_zOrder = NULL)
    {
        parent::__construct(array('ImageName'=>$_imageName,'ImageNameForIndividual'=>$_imageNameForIndividual,'Name'=>$_name,'PinType'=>$_pinType,'Style'=>$_style,'ZOrder'=>$_zOrder),false);
    }
    /**
     * Get ImageName value
     * @return string|null
     */
    public function getImageName()
    {
        return $this->ImageName;
    }
    /**
     * Set ImageName value
     * @param string $_imageName the ImageName
     * @return string
     */
    public function setImageName($_imageName)
    {
        return ($this->ImageName = $_imageName);
    }
    /**
     * Get ImageNameForIndividual value
     * @return string|null
     */
    public function getImageNameForIndividual()
    {
        return $this->ImageNameForIndividual;
    }
    /**
     * Set ImageNameForIndividual value
     * @param string $_imageNameForIndividual the ImageNameForIndividual
     * @return string
     */
    public function setImageNameForIndividual($_imageNameForIndividual)
    {
        return ($this->ImageNameForIndividual = $_imageNameForIndividual);
    }
    /**
     * Get Name value
     * @return string|null
     */
    public function getName()
    {
        return $this->Name;
    }
    /**
     * Set Name value
     * @param string $_name the Name
     * @return string
     */
    public function setName($_name)
    {
        return ($this->Name = $_name);
    }
    /**
     * Get PinType value
     * @return PCMWSEnumPinType|null
     */
    public function getPinType()
    {
        return $this->PinType;
    }
    /**
     * Set PinType value
     * @uses PCMWSEnumPinType::valueIsValid()
     * @param PCMWSEnumPinType $_pinType the PinType
     * @return PCMWSEnumPinType
     */
    public function setPinType($_pinType)
    {
        if(!PCMWSEnumPinType::valueIsValid($_pinType))
        {
            return false;
        }
        return ($this->PinType = $_pinType);
    }
    /**
     * Get Style value
     * @return PCMWSStructALKStyle|null
     */
    public function getStyle()
    {
        return $this->Style;
    }
    /**
     * Set Style value
     * @param PCMWSStructALKStyle $_style the Style
     * @return PCMWSStructALKStyle
     */
    public function setStyle($_style)
    {
        return ($this->Style = $_style);
    }
    /**
     * Get ZOrder value
     * @return int|null
     */
    public function getZOrder()
    {
        return $this->ZOrder;
    }
    /**
     * Set ZOrder value
     * @param int $_zOrder the ZOrder
     * @return int
     */
    public function setZOrder($_zOrder)
    {
        return ($this->ZOrder = $_zOrder);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructPinCategory
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
