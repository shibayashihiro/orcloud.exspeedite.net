<?php
/**
 * File for class PCMWSStructPin
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructPin originally named Pin
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructPin extends PCMWSWsdlClass
{
    /**
     * The ID
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var unsignedInt
     */
    public $ID;
    /**
     * The Point
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructCoordinates
     */
    public $Point;
    /**
     * The Image
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Image;
    /**
     * The Category
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Category;
    /**
     * The Label
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Label;
    /**
     * Constructor method for Pin
     * @see parent::__construct()
     * @param unsignedInt $_iD
     * @param PCMWSStructCoordinates $_point
     * @param string $_image
     * @param string $_category
     * @param string $_label
     * @return PCMWSStructPin
     */
    public function __construct($_iD = NULL,$_point = NULL,$_image = NULL,$_category = NULL,$_label = NULL)
    {
        parent::__construct(array('ID'=>$_iD,'Point'=>$_point,'Image'=>$_image,'Category'=>$_category,'Label'=>$_label),false);
    }
    /**
     * Get ID value
     * @return unsignedInt|null
     */
    public function getID()
    {
        return $this->ID;
    }
    /**
     * Set ID value
     * @param unsignedInt $_iD the ID
     * @return unsignedInt
     */
    public function setID($_iD)
    {
        return ($this->ID = $_iD);
    }
    /**
     * Get Point value
     * @return PCMWSStructCoordinates|null
     */
    public function getPoint()
    {
        return $this->Point;
    }
    /**
     * Set Point value
     * @param PCMWSStructCoordinates $_point the Point
     * @return PCMWSStructCoordinates
     */
    public function setPoint($_point)
    {
        return ($this->Point = $_point);
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
     * Get Category value
     * @return string|null
     */
    public function getCategory()
    {
        return $this->Category;
    }
    /**
     * Set Category value
     * @param string $_category the Category
     * @return string
     */
    public function setCategory($_category)
    {
        return ($this->Category = $_category);
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
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructPin
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
