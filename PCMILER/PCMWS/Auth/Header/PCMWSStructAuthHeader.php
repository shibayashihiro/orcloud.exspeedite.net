<?php
/**
 * File for class PCMWSStructAuthHeader
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructAuthHeader originally named AuthHeader
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?wsdl=wsdl0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructAuthHeader extends PCMWSWsdlClass
{
    /**
     * The Authorization
     * @var string
     */
    public $Authorization;
    /**
     * The Date
     * @var string
     */
    public $Date;
    /**
     * Constructor method for AuthHeader
     * @see parent::__construct()
     * @param string $_authorization
     * @param string $_date
     * @return PCMWSStructAuthHeader
     */
    public function __construct($_authorization = NULL,$_date = NULL)
    {
        parent::__construct(array('Authorization'=>$_authorization,'Date'=>$_date),false);
    }
    /**
     * Get Authorization value
     * @return string|null
     */
    public function getAuthorization()
    {
        return $this->Authorization;
    }
    /**
     * Set Authorization value
     * @param string $_authorization the Authorization
     * @return string
     */
    public function setAuthorization($_authorization)
    {
        return ($this->Authorization = $_authorization);
    }
    /**
     * Get Date value
     * @return string|null
     */
    public function getDate()
    {
        return $this->Date;
    }
    /**
     * Set Date value
     * @param string $_date the Date
     * @return string
     */
    public function setDate($_date)
    {
        return ($this->Date = $_date);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructAuthHeader
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
