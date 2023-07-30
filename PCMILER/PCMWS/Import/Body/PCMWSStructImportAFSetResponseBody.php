<?php
/**
 * File for class PCMWSStructImportAFSetResponseBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructImportAFSetResponseBody originally named ImportAFSetResponseBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructImportAFSetResponseBody extends PCMWSWsdlClass
{
    /**
     * The SetID
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $SetID;
    /**
     * Constructor method for ImportAFSetResponseBody
     * @see parent::__construct()
     * @param int $_setID
     * @return PCMWSStructImportAFSetResponseBody
     */
    public function __construct($_setID = NULL)
    {
        parent::__construct(array('SetID'=>$_setID),false);
    }
    /**
     * Get SetID value
     * @return int|null
     */
    public function getSetID()
    {
        return $this->SetID;
    }
    /**
     * Set SetID value
     * @param int $_setID the SetID
     * @return int
     */
    public function setSetID($_setID)
    {
        return ($this->SetID = $_setID);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructImportAFSetResponseBody
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
