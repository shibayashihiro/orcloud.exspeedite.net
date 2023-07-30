<?php
/**
 * File for class PCMWSStructError
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructError originally named Error
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructError extends PCMWSWsdlClass
{
    /**
     * The Type
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumErrorType
     */
    public $Type;
    /**
     * The Code
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumPcmwsExceptionCode
     */
    public $Code;
    /**
     * The LegacyErrorCode
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $LegacyErrorCode;
    /**
     * The Description
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Description;
    /**
     * Constructor method for Error
     * @see parent::__construct()
     * @param PCMWSEnumErrorType $_type
     * @param PCMWSEnumPcmwsExceptionCode $_code
     * @param int $_legacyErrorCode
     * @param string $_description
     * @return PCMWSStructError
     */
    public function __construct($_type = NULL,$_code = NULL,$_legacyErrorCode = NULL,$_description = NULL)
    {
        parent::__construct(array('Type'=>$_type,'Code'=>$_code,'LegacyErrorCode'=>$_legacyErrorCode,'Description'=>$_description),false);
    }
    /**
     * Get Type value
     * @return PCMWSEnumErrorType|null
     */
    public function getType()
    {
        return $this->Type;
    }
    /**
     * Set Type value
     * @uses PCMWSEnumErrorType::valueIsValid()
     * @param PCMWSEnumErrorType $_type the Type
     * @return PCMWSEnumErrorType
     */
    public function setType($_type)
    {
        if(!PCMWSEnumErrorType::valueIsValid($_type))
        {
            return false;
        }
        return ($this->Type = $_type);
    }
    /**
     * Get Code value
     * @return PCMWSEnumPcmwsExceptionCode|null
     */
    public function getCode()
    {
        return $this->Code;
    }
    /**
     * Set Code value
     * @uses PCMWSEnumPcmwsExceptionCode::valueIsValid()
     * @param PCMWSEnumPcmwsExceptionCode $_code the Code
     * @return PCMWSEnumPcmwsExceptionCode
     */
    public function setCode($_code)
    {
        if(!PCMWSEnumPcmwsExceptionCode::valueIsValid($_code))
        {
            return false;
        }
        return ($this->Code = $_code);
    }
    /**
     * Get LegacyErrorCode value
     * @return int|null
     */
    public function getLegacyErrorCode()
    {
        return $this->LegacyErrorCode;
    }
    /**
     * Set LegacyErrorCode value
     * @param int $_legacyErrorCode the LegacyErrorCode
     * @return int
     */
    public function setLegacyErrorCode($_legacyErrorCode)
    {
        return ($this->LegacyErrorCode = $_legacyErrorCode);
    }
    /**
     * Get Description value
     * @return string|null
     */
    public function getDescription()
    {
        return $this->Description;
    }
    /**
     * Set Description value
     * @param string $_description the Description
     * @return string
     */
    public function setDescription($_description)
    {
        return ($this->Description = $_description);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructError
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
