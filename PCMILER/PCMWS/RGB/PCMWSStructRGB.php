<?php
/**
 * File for class PCMWSStructRGB
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructRGB originally named RGB
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructRGB extends PCMWSWsdlClass
{
    /**
     * The Red
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var unsignedByte
     */
    public $Red;
    /**
     * The Green
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var unsignedByte
     */
    public $Green;
    /**
     * The Blue
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var unsignedByte
     */
    public $Blue;
    /**
     * Constructor method for RGB
     * @see parent::__construct()
     * @param unsignedByte $_red
     * @param unsignedByte $_green
     * @param unsignedByte $_blue
     * @return PCMWSStructRGB
     */
    public function __construct($_red = NULL,$_green = NULL,$_blue = NULL)
    {
        parent::__construct(array('Red'=>$_red,'Green'=>$_green,'Blue'=>$_blue),false);
    }
    /**
     * Get Red value
     * @return unsignedByte|null
     */
    public function getRed()
    {
        return $this->Red;
    }
    /**
     * Set Red value
     * @param unsignedByte $_red the Red
     * @return unsignedByte
     */
    public function setRed($_red)
    {
        return ($this->Red = $_red);
    }
    /**
     * Get Green value
     * @return unsignedByte|null
     */
    public function getGreen()
    {
        return $this->Green;
    }
    /**
     * Set Green value
     * @param unsignedByte $_green the Green
     * @return unsignedByte
     */
    public function setGreen($_green)
    {
        return ($this->Green = $_green);
    }
    /**
     * Get Blue value
     * @return unsignedByte|null
     */
    public function getBlue()
    {
        return $this->Blue;
    }
    /**
     * Set Blue value
     * @param unsignedByte $_blue the Blue
     * @return unsignedByte
     */
    public function setBlue($_blue)
    {
        return ($this->Blue = $_blue);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructRGB
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
