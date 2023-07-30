<?php
/**
 * File for class PCMWSStructAvoidFavorRequestBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructAvoidFavorRequestBody originally named AvoidFavorRequestBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructAvoidFavorRequestBody extends PCMWSWsdlClass
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
     * The Type
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumAFType
     */
    public $Type;
    /**
     * Constructor method for AvoidFavorRequestBody
     * @see parent::__construct()
     * @param string $_label
     * @param PCMWSEnumAFType $_type
     * @return PCMWSStructAvoidFavorRequestBody
     */
    public function __construct($_label = NULL,$_type = NULL)
    {
        parent::__construct(array('Label'=>$_label,'Type'=>$_type),false);
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
     * @return PCMWSStructAvoidFavorRequestBody
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
