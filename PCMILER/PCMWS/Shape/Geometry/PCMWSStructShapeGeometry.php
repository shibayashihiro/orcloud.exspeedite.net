<?php
/**
 * File for class PCMWSStructShapeGeometry
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructShapeGeometry originally named ShapeGeometry
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructShapeGeometry extends PCMWSStructGeometry
{
    /**
     * The Type
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumShapeType
     */
    public $Type;
    /**
     * The Coordinates
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Coordinates;
    /**
     * The Fill
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $Fill;
    /**
     * The LineWidth
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $LineWidth;
    /**
     * The RadiusHorizontal
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var float
     */
    public $RadiusHorizontal;
    /**
     * The RadiusVertical
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var float
     */
    public $RadiusVertical;
    /**
     * Constructor method for ShapeGeometry
     * @see parent::__construct()
     * @param PCMWSEnumShapeType $_type
     * @param string $_coordinates
     * @param boolean $_fill
     * @param int $_lineWidth
     * @param float $_radiusHorizontal
     * @param float $_radiusVertical
     * @return PCMWSStructShapeGeometry
     */
    public function __construct($_type = NULL,$_coordinates = NULL,$_fill = NULL,$_lineWidth = NULL,$_radiusHorizontal = NULL,$_radiusVertical = NULL)
    {
        PCMWSWsdlClass::__construct(array('Type'=>$_type,'Coordinates'=>$_coordinates,'Fill'=>$_fill,'LineWidth'=>$_lineWidth,'RadiusHorizontal'=>$_radiusHorizontal,'RadiusVertical'=>$_radiusVertical),false);
    }
    /**
     * Get Type value
     * @return PCMWSEnumShapeType|null
     */
    public function getType()
    {
        return $this->Type;
    }
    /**
     * Set Type value
     * @uses PCMWSEnumShapeType::valueIsValid()
     * @param PCMWSEnumShapeType $_type the Type
     * @return PCMWSEnumShapeType
     */
    public function setType($_type)
    {
        if(!PCMWSEnumShapeType::valueIsValid($_type))
        {
            return false;
        }
        return ($this->Type = $_type);
    }
    /**
     * Get Coordinates value
     * @return string|null
     */
    public function getCoordinates()
    {
        return $this->Coordinates;
    }
    /**
     * Set Coordinates value
     * @param string $_coordinates the Coordinates
     * @return string
     */
    public function setCoordinates($_coordinates)
    {
        return ($this->Coordinates = $_coordinates);
    }
    /**
     * Get Fill value
     * @return boolean|null
     */
    public function getFill()
    {
        return $this->Fill;
    }
    /**
     * Set Fill value
     * @param boolean $_fill the Fill
     * @return boolean
     */
    public function setFill($_fill)
    {
        return ($this->Fill = $_fill);
    }
    /**
     * Get LineWidth value
     * @return int|null
     */
    public function getLineWidth()
    {
        return $this->LineWidth;
    }
    /**
     * Set LineWidth value
     * @param int $_lineWidth the LineWidth
     * @return int
     */
    public function setLineWidth($_lineWidth)
    {
        return ($this->LineWidth = $_lineWidth);
    }
    /**
     * Get RadiusHorizontal value
     * @return float|null
     */
    public function getRadiusHorizontal()
    {
        return $this->RadiusHorizontal;
    }
    /**
     * Set RadiusHorizontal value
     * @param float $_radiusHorizontal the RadiusHorizontal
     * @return float
     */
    public function setRadiusHorizontal($_radiusHorizontal)
    {
        return ($this->RadiusHorizontal = $_radiusHorizontal);
    }
    /**
     * Get RadiusVertical value
     * @return float|null
     */
    public function getRadiusVertical()
    {
        return $this->RadiusVertical;
    }
    /**
     * Set RadiusVertical value
     * @param float $_radiusVertical the RadiusVertical
     * @return float
     */
    public function setRadiusVertical($_radiusVertical)
    {
        return ($this->RadiusVertical = $_radiusVertical);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructShapeGeometry
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
