<?php
/**
 * File for class PCMWSStructLayer
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructLayer originally named Layer
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructLayer extends PCMWSWsdlClass
{
    /**
     * The Buffer
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var base64Binary
     */
    public $Buffer;
    /**
     * The Image
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Image;
    /**
     * The Size
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $Size;
    /**
     * The Type
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumLayerType
     */
    public $Type;
    /**
     * Constructor method for Layer
     * @see parent::__construct()
     * @param base64Binary $_buffer
     * @param string $_image
     * @param int $_size
     * @param PCMWSEnumLayerType $_type
     * @return PCMWSStructLayer
     */
    public function __construct($_buffer = NULL,$_image = NULL,$_size = NULL,$_type = NULL)
    {
        parent::__construct(array('Buffer'=>$_buffer,'Image'=>$_image,'Size'=>$_size,'Type'=>$_type),false);
    }
    /**
     * Get Buffer value
     * @return base64Binary|null
     */
    public function getBuffer()
    {
        return $this->Buffer;
    }
    /**
     * Set Buffer value
     * @param base64Binary $_buffer the Buffer
     * @return base64Binary
     */
    public function setBuffer($_buffer)
    {
        return ($this->Buffer = $_buffer);
    }
    /**
     * Get Image value
     * @return string|null
     */
    public function getImage()
    {
        return $this->Image;
    }
    /**
     * Set Image value
     * @param string $_image the Image
     * @return string
     */
    public function setImage($_image)
    {
        return ($this->Image = $_image);
    }
    /**
     * Get Size value
     * @return int|null
     */
    public function getSize()
    {
        return $this->Size;
    }
    /**
     * Set Size value
     * @param int $_size the Size
     * @return int
     */
    public function setSize($_size)
    {
        return ($this->Size = $_size);
    }
    /**
     * Get Type value
     * @return PCMWSEnumLayerType|null
     */
    public function getType()
    {
        return $this->Type;
    }
    /**
     * Set Type value
     * @uses PCMWSEnumLayerType::valueIsValid()
     * @param PCMWSEnumLayerType $_type the Type
     * @return PCMWSEnumLayerType
     */
    public function setType($_type)
    {
        if(!PCMWSEnumLayerType::valueIsValid($_type))
        {
            return false;
        }
        return ($this->Type = $_type);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructLayer
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
