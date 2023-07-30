<?php
/**
 * File for class PCMWSStructPoint
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructPoint originally named Point
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructPoint extends PCMWSWsdlClass
{
    /**
     * The X
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $X;
    /**
     * The Y
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $Y;
    /**
     * Constructor method for Point
     * @see parent::__construct()
     * @param int $_x
     * @param int $_y
     * @return PCMWSStructPoint
     */
    public function __construct($_x = NULL,$_y = NULL)
    {
        parent::__construct(array('X'=>$_x,'Y'=>$_y),false);
    }
    /**
     * Get X value
     * @return int|null
     */
    public function getX()
    {
        return $this->X;
    }
    /**
     * Set X value
     * @param int $_x the X
     * @return int
     */
    public function setX($_x)
    {
        return ($this->X = $_x);
    }
    /**
     * Get Y value
     * @return int|null
     */
    public function getY()
    {
        return $this->Y;
    }
    /**
     * Set Y value
     * @param int $_y the Y
     * @return int
     */
    public function setY($_y)
    {
        return ($this->Y = $_y);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructPoint
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
