<?php
/**
 * File for class PCMWSStructALKPen
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructALKPen originally named ALKPen
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructALKPen extends PCMWSWsdlClass
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
     * The Widths
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var unsignedByte
     */
    public $Widths;
    /**
     * The Widths1
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var unsignedByte
     */
    public $Widths1;
    /**
     * The Widths2
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var unsignedByte
     */
    public $Widths2;
    /**
     * The Widths3
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var unsignedByte
     */
    public $Widths3;
    /**
     * Constructor method for ALKPen
     * @see parent::__construct()
     * @param PCMWSStructRGB $_color
     * @param unsignedByte $_widths
     * @param unsignedByte $_widths1
     * @param unsignedByte $_widths2
     * @param unsignedByte $_widths3
     * @return PCMWSStructALKPen
     */
    public function __construct($_color = NULL,$_widths = NULL,$_widths1 = NULL,$_widths2 = NULL,$_widths3 = NULL)
    {
        parent::__construct(array('Color'=>$_color,'Widths'=>$_widths,'Widths1'=>$_widths1,'Widths2'=>$_widths2,'Widths3'=>$_widths3),false);
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
     * Get Widths value
     * @return unsignedByte|null
     */
    public function getWidths()
    {
        return $this->Widths;
    }
    /**
     * Set Widths value
     * @param unsignedByte $_widths the Widths
     * @return unsignedByte
     */
    public function setWidths($_widths)
    {
        return ($this->Widths = $_widths);
    }
    /**
     * Get Widths1 value
     * @return unsignedByte|null
     */
    public function getWidths1()
    {
        return $this->Widths1;
    }
    /**
     * Set Widths1 value
     * @param unsignedByte $_widths1 the Widths1
     * @return unsignedByte
     */
    public function setWidths1($_widths1)
    {
        return ($this->Widths1 = $_widths1);
    }
    /**
     * Get Widths2 value
     * @return unsignedByte|null
     */
    public function getWidths2()
    {
        return $this->Widths2;
    }
    /**
     * Set Widths2 value
     * @param unsignedByte $_widths2 the Widths2
     * @return unsignedByte
     */
    public function setWidths2($_widths2)
    {
        return ($this->Widths2 = $_widths2);
    }
    /**
     * Get Widths3 value
     * @return unsignedByte|null
     */
    public function getWidths3()
    {
        return $this->Widths3;
    }
    /**
     * Set Widths3 value
     * @param unsignedByte $_widths3 the Widths3
     * @return unsignedByte
     */
    public function setWidths3($_widths3)
    {
        return ($this->Widths3 = $_widths3);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructALKPen
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
