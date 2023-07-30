<?php
/**
 * File for class PCMWSStructAddGeofenceSet
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructAddGeofenceSet originally named AddGeofenceSet
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructAddGeofenceSet extends PCMWSWsdlClass
{
    /**
     * The ActiveState
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSEnumGeofenceState
     */
    public $ActiveState;
    /**
     * The BorderAlpha
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var unsignedByte
     */
    public $BorderAlpha;
    /**
     * The BorderColor
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var int
     */
    public $BorderColor;
    /**
     * The BorderWidth
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var unsignedByte
     */
    public $BorderWidth;
    /**
     * The EndTime
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $EndTime;
    /**
     * The FillAlpha
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var unsignedByte
     */
    public $FillAlpha;
    /**
     * The FillColor
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var int
     */
    public $FillColor;
    /**
     * The Name
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Name;
    /**
     * The StartTime
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $StartTime;
    /**
     * The Tag
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Tag;
    /**
     * The Visible
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var boolean
     */
    public $Visible;
    /**
     * Constructor method for AddGeofenceSet
     * @see parent::__construct()
     * @param PCMWSEnumGeofenceState $_activeState
     * @param unsignedByte $_borderAlpha
     * @param int $_borderColor
     * @param unsignedByte $_borderWidth
     * @param string $_endTime
     * @param unsignedByte $_fillAlpha
     * @param int $_fillColor
     * @param string $_name
     * @param string $_startTime
     * @param string $_tag
     * @param boolean $_visible
     * @return PCMWSStructAddGeofenceSet
     */
    public function __construct($_activeState = NULL,$_borderAlpha = NULL,$_borderColor = NULL,$_borderWidth = NULL,$_endTime = NULL,$_fillAlpha = NULL,$_fillColor = NULL,$_name = NULL,$_startTime = NULL,$_tag = NULL,$_visible = NULL)
    {
        parent::__construct(array('ActiveState'=>$_activeState,'BorderAlpha'=>$_borderAlpha,'BorderColor'=>$_borderColor,'BorderWidth'=>$_borderWidth,'EndTime'=>$_endTime,'FillAlpha'=>$_fillAlpha,'FillColor'=>$_fillColor,'Name'=>$_name,'StartTime'=>$_startTime,'Tag'=>$_tag,'Visible'=>$_visible),false);
    }
    /**
     * Get ActiveState value
     * @return PCMWSEnumGeofenceState|null
     */
    public function getActiveState()
    {
        return $this->ActiveState;
    }
    /**
     * Set ActiveState value
     * @uses PCMWSEnumGeofenceState::valueIsValid()
     * @param PCMWSEnumGeofenceState $_activeState the ActiveState
     * @return PCMWSEnumGeofenceState
     */
    public function setActiveState($_activeState)
    {
        if(!PCMWSEnumGeofenceState::valueIsValid($_activeState))
        {
            return false;
        }
        return ($this->ActiveState = $_activeState);
    }
    /**
     * Get BorderAlpha value
     * @return unsignedByte|null
     */
    public function getBorderAlpha()
    {
        return $this->BorderAlpha;
    }
    /**
     * Set BorderAlpha value
     * @param unsignedByte $_borderAlpha the BorderAlpha
     * @return unsignedByte
     */
    public function setBorderAlpha($_borderAlpha)
    {
        return ($this->BorderAlpha = $_borderAlpha);
    }
    /**
     * Get BorderColor value
     * @return int|null
     */
    public function getBorderColor()
    {
        return $this->BorderColor;
    }
    /**
     * Set BorderColor value
     * @param int $_borderColor the BorderColor
     * @return int
     */
    public function setBorderColor($_borderColor)
    {
        return ($this->BorderColor = $_borderColor);
    }
    /**
     * Get BorderWidth value
     * @return unsignedByte|null
     */
    public function getBorderWidth()
    {
        return $this->BorderWidth;
    }
    /**
     * Set BorderWidth value
     * @param unsignedByte $_borderWidth the BorderWidth
     * @return unsignedByte
     */
    public function setBorderWidth($_borderWidth)
    {
        return ($this->BorderWidth = $_borderWidth);
    }
    /**
     * Get EndTime value
     * @return string|null
     */
    public function getEndTime()
    {
        return $this->EndTime;
    }
    /**
     * Set EndTime value
     * @param string $_endTime the EndTime
     * @return string
     */
    public function setEndTime($_endTime)
    {
        return ($this->EndTime = $_endTime);
    }
    /**
     * Get FillAlpha value
     * @return unsignedByte|null
     */
    public function getFillAlpha()
    {
        return $this->FillAlpha;
    }
    /**
     * Set FillAlpha value
     * @param unsignedByte $_fillAlpha the FillAlpha
     * @return unsignedByte
     */
    public function setFillAlpha($_fillAlpha)
    {
        return ($this->FillAlpha = $_fillAlpha);
    }
    /**
     * Get FillColor value
     * @return int|null
     */
    public function getFillColor()
    {
        return $this->FillColor;
    }
    /**
     * Set FillColor value
     * @param int $_fillColor the FillColor
     * @return int
     */
    public function setFillColor($_fillColor)
    {
        return ($this->FillColor = $_fillColor);
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
     * Get StartTime value
     * @return string|null
     */
    public function getStartTime()
    {
        return $this->StartTime;
    }
    /**
     * Set StartTime value
     * @param string $_startTime the StartTime
     * @return string
     */
    public function setStartTime($_startTime)
    {
        return ($this->StartTime = $_startTime);
    }
    /**
     * Get Tag value
     * @return string|null
     */
    public function getTag()
    {
        return $this->Tag;
    }
    /**
     * Set Tag value
     * @param string $_tag the Tag
     * @return string
     */
    public function setTag($_tag)
    {
        return ($this->Tag = $_tag);
    }
    /**
     * Get Visible value
     * @return boolean|null
     */
    public function getVisible()
    {
        return $this->Visible;
    }
    /**
     * Set Visible value
     * @param boolean $_visible the Visible
     * @return boolean
     */
    public function setVisible($_visible)
    {
        return ($this->Visible = $_visible);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructAddGeofenceSet
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
