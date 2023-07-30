<?php
/**
 * File for class PCMWSStructResponseHeader
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructResponseHeader originally named ResponseHeader
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructResponseHeader extends PCMWSWsdlClass
{
    /**
     * The Type
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Type;
    /**
     * The Success
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $Success;
    /**
     * The DataVersion
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $DataVersion;
    /**
     * The Errors
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfError
     */
    public $Errors;
    /**
     * Constructor method for ResponseHeader
     * @see parent::__construct()
     * @param string $_type
     * @param boolean $_success
     * @param string $_dataVersion
     * @param PCMWSStructArrayOfError $_errors
     * @return PCMWSStructResponseHeader
     */
    public function __construct($_type = NULL,$_success = NULL,$_dataVersion = NULL,$_errors = NULL)
    {
        parent::__construct(array('Type'=>$_type,'Success'=>$_success,'DataVersion'=>$_dataVersion,'Errors'=>($_errors instanceof PCMWSStructArrayOfError)?$_errors:new PCMWSStructArrayOfError($_errors)),false);
    }
    /**
     * Get Type value
     * @return string|null
     */
    public function getType()
    {
        return $this->Type;
    }
    /**
     * Set Type value
     * @param string $_type the Type
     * @return string
     */
    public function setType($_type)
    {
        return ($this->Type = $_type);
    }
    /**
     * Get Success value
     * @return boolean|null
     */
    public function getSuccess()
    {
        return $this->Success;
    }
    /**
     * Set Success value
     * @param boolean $_success the Success
     * @return boolean
     */
    public function setSuccess($_success)
    {
        return ($this->Success = $_success);
    }
    /**
     * Get DataVersion value
     * @return string|null
     */
    public function getDataVersion()
    {
        return $this->DataVersion;
    }
    /**
     * Set DataVersion value
     * @param string $_dataVersion the DataVersion
     * @return string
     */
    public function setDataVersion($_dataVersion)
    {
        return ($this->DataVersion = $_dataVersion);
    }
    /**
     * Get Errors value
     * @return PCMWSStructArrayOfError|null
     */
    public function getErrors()
    {
        return $this->Errors;
    }
    /**
     * Set Errors value
     * @param PCMWSStructArrayOfError $_errors the Errors
     * @return PCMWSStructArrayOfError
     */
    public function setErrors($_errors)
    {
        return ($this->Errors = $_errors);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructResponseHeader
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
