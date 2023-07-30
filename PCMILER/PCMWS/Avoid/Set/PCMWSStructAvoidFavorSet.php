<?php
/**
 * File for class PCMWSStructAvoidFavorSet
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructAvoidFavorSet originally named AvoidFavorSet
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructAvoidFavorSet extends PCMWSWsdlClass
{
    /**
     * The Action
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumActionType
     */
    public $Action;
    /**
     * The AvoidFavor
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructAvoidFavor
     */
    public $AvoidFavor;
    /**
     * Constructor method for AvoidFavorSet
     * @see parent::__construct()
     * @param PCMWSEnumActionType $_action
     * @param PCMWSStructAvoidFavor $_avoidFavor
     * @return PCMWSStructAvoidFavorSet
     */
    public function __construct($_action = NULL,$_avoidFavor = NULL)
    {
        parent::__construct(array('Action'=>$_action,'AvoidFavor'=>$_avoidFavor),false);
    }
    /**
     * Get Action value
     * @return PCMWSEnumActionType|null
     */
    public function getAction()
    {
        return $this->Action;
    }
    /**
     * Set Action value
     * @uses PCMWSEnumActionType::valueIsValid()
     * @param PCMWSEnumActionType $_action the Action
     * @return PCMWSEnumActionType
     */
    public function setAction($_action)
    {
        if(!PCMWSEnumActionType::valueIsValid($_action))
        {
            return false;
        }
        return ($this->Action = $_action);
    }
    /**
     * Get AvoidFavor value
     * @return PCMWSStructAvoidFavor|null
     */
    public function getAvoidFavor()
    {
        return $this->AvoidFavor;
    }
    /**
     * Set AvoidFavor value
     * @param PCMWSStructAvoidFavor $_avoidFavor the AvoidFavor
     * @return PCMWSStructAvoidFavor
     */
    public function setAvoidFavor($_avoidFavor)
    {
        return ($this->AvoidFavor = $_avoidFavor);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructAvoidFavorSet
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
