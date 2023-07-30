<?php
/**
 * File for class PCMWSStructImportAFSetRequestBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructImportAFSetRequestBody originally named ImportAFSetRequestBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructImportAFSetRequestBody extends PCMWSStructCustomDataSetRequestBody
{
    /**
     * The FileBytes
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var base64Binary
     */
    public $FileBytes;
    /**
     * Constructor method for ImportAFSetRequestBody
     * @see parent::__construct()
     * @param base64Binary $_fileBytes
     * @return PCMWSStructImportAFSetRequestBody
     */
    public function __construct($_fileBytes = NULL)
    {
        PCMWSWsdlClass::__construct(array('FileBytes'=>$_fileBytes),false);
    }
    /**
     * Get FileBytes value
     * @return base64Binary|null
     */
    public function getFileBytes()
    {
        return $this->FileBytes;
    }
    /**
     * Set FileBytes value
     * @param base64Binary $_fileBytes the FileBytes
     * @return base64Binary
     */
    public function setFileBytes($_fileBytes)
    {
        return ($this->FileBytes = $_fileBytes);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructImportAFSetRequestBody
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
