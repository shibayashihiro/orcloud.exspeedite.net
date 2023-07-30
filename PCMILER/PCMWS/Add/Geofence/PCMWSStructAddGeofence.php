<?php
/**
 * File for class PCMWSStructAddGeofence
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructAddGeofence originally named AddGeofence
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructAddGeofence extends PCMWSWsdlClass
{
    /**
     * The EndTime
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $EndTime;
    /**
     * The Label
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Label;
    /**
     * The Name
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Name;
    /**
     * The Radius
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var double
     */
    public $Radius;
    /**
     * The SetId
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $SetId;
    /**
     * The ShapePoints
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfArrayOfdouble
     */
    public $ShapePoints;
    /**
     * The ShapeType
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumGeofenceShape
     */
    public $ShapeType;
    /**
     * The StartTime
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $StartTime;
    /**
     * Constructor method for AddGeofence
     * @see parent::__construct()
     * @param string $_endTime
     * @param string $_label
     * @param string $_name
     * @param double $_radius
     * @param int $_setId
     * @param PCMWSStructArrayOfArrayOfdouble $_shapePoints
     * @param PCMWSEnumGeofenceShape $_shapeType
     * @param string $_startTime
     * @return PCMWSStructAddGeofence
     */
    public function __construct($_endTime = NULL,$_label = NULL,$_name = NULL,$_radius = NULL,$_setId = NULL,$_shapePoints = NULL,$_shapeType = NULL,$_startTime = NULL)
    {
        parent::__construct(array('EndTime'=>$_endTime,'Label'=>$_label,'Name'=>$_name,'Radius'=>$_radius,'SetId'=>$_setId,'ShapePoints'=>($_shapePoints instanceof PCMWSStructArrayOfArrayOfdouble)?$_shapePoints:new PCMWSStructArrayOfArrayOfdouble($_shapePoints),'ShapeType'=>$_shapeType,'StartTime'=>$_startTime),false);
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
     * Get Label value
     * @return string|null
     */
    public function getLabel()
    {
        return $this->Label;
    }
    /**
     * Set Label value
     * @param string $_label the Label
     * @return string
     */
    public function setLabel($_label)
    {
        return ($this->Label = $_label);
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
     * Get Radius value
     * @return double|null
     */
    public function getRadius()
    {
        return $this->Radius;
    }
    /**
     * Set Radius value
     * @param double $_radius the Radius
     * @return double
     */
    public function setRadius($_radius)
    {
        return ($this->Radius = $_radius);
    }
    /**
     * Get SetId value
     * @return int|null
     */
    public function getSetId()
    {
        return $this->SetId;
    }
    /**
     * Set SetId value
     * @param int $_setId the SetId
     * @return int
     */
    public function setSetId($_setId)
    {
        return ($this->SetId = $_setId);
    }
    /**
     * Get ShapePoints value
     * @return PCMWSStructArrayOfArrayOfdouble|null
     */
    public function getShapePoints()
    {
        return $this->ShapePoints;
    }
    /**
     * Set ShapePoints value
     * @param PCMWSStructArrayOfArrayOfdouble $_shapePoints the ShapePoints
     * @return PCMWSStructArrayOfArrayOfdouble
     */
    public function setShapePoints($_shapePoints)
    {
        return ($this->ShapePoints = $_shapePoints);
    }
    /**
     * Get ShapeType value
     * @return PCMWSEnumGeofenceShape|null
     */
    public function getShapeType()
    {
        return $this->ShapeType;
    }
    /**
     * Set ShapeType value
     * @uses PCMWSEnumGeofenceShape::valueIsValid()
     * @param PCMWSEnumGeofenceShape $_shapeType the ShapeType
     * @return PCMWSEnumGeofenceShape
     */
    public function setShapeType($_shapeType)
    {
        if(!PCMWSEnumGeofenceShape::valueIsValid($_shapeType))
        {
            return false;
        }
        return ($this->ShapeType = $_shapeType);
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
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructAddGeofence
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
