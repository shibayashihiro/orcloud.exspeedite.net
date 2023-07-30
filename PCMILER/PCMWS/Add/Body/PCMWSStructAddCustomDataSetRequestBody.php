<?php
/**
 * File for class PCMWSStructAddCustomDataSetRequestBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructAddCustomDataSetRequestBody originally named AddCustomDataSetRequestBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructAddCustomDataSetRequestBody extends PCMWSWsdlClass
{
    /**
     * The SetName
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $SetName;
    /**
     * The SetTag
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $SetTag;
    /**
     * Constructor method for AddCustomDataSetRequestBody
     * @see parent::__construct()
     * @param string $_setName
     * @param string $_setTag
     * @return PCMWSStructAddCustomDataSetRequestBody
     */
    public function __construct($_setName = NULL,$_setTag = NULL)
    {
        parent::__construct(array('SetName'=>$_setName,'SetTag'=>$_setTag),false);
    }
    /**
     * Get SetName value
     * @return string|null
     */
    public function getSetName()
    {
        return $this->SetName;
    }
    /**
     * Set SetName value
     * @param string $_setName the SetName
     * @return string
     */
    public function setSetName($_setName)
    {
        return ($this->SetName = $_setName);
    }
    /**
     * Get SetTag value
     * @return string|null
     */
    public function getSetTag()
    {
        return $this->SetTag;
    }
    /**
     * Set SetTag value
     * @param string $_setTag the SetTag
     * @return string
     */
    public function setSetTag($_setTag)
    {
        return ($this->SetTag = $_setTag);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructAddCustomDataSetRequestBody
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
