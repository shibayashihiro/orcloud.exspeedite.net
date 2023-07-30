<?php
/**
 * File for class PCMWSStructAvoidFavor
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructAvoidFavor originally named AvoidFavor
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructAvoidFavor extends PCMWSWsdlClass
{
    /**
     * The Label
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Label;
    /**
     * The Road
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Road;
    /**
     * The State
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $State;
    /**
     * The Type
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumAFType
     */
    public $Type;
    /**
     * Constructor method for AvoidFavor
     * @see parent::__construct()
     * @param string $_label
     * @param string $_road
     * @param string $_state
     * @param PCMWSEnumAFType $_type
     * @return PCMWSStructAvoidFavor
     */
    public function __construct($_label = NULL,$_road = NULL,$_state = NULL,$_type = NULL)
    {
        parent::__construct(array('Label'=>$_label,'Road'=>$_road,'State'=>$_state,'Type'=>$_type),false);
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
     * Get Road value
     * @return string|null
     */
    public function getRoad()
    {
        return $this->Road;
    }
    /**
     * Set Road value
     * @param string $_road the Road
     * @return string
     */
    public function setRoad($_road)
    {
        return ($this->Road = $_road);
    }
    /**
     * Get State value
     * @return string|null
     */
    public function getState()
    {
        return $this->State;
    }
    /**
     * Set State value
     * @param string $_state the State
     * @return string
     */
    public function setState($_state)
    {
        return ($this->State = $_state);
    }
    /**
     * Get Type value
     * @return PCMWSEnumAFType|null
     */
    public function getType()
    {
        return $this->Type;
    }
    /**
     * Set Type value
     * @uses PCMWSEnumAFType::valueIsValid()
     * @param PCMWSEnumAFType $_type the Type
     * @return PCMWSEnumAFType
     */
    public function setType($_type)
    {
        if(!PCMWSEnumAFType::valueIsValid($_type))
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
     * @return PCMWSStructAvoidFavor
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
