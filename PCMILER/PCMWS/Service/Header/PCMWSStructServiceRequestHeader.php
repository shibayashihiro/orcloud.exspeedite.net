<?php
/**
 * File for class PCMWSStructServiceRequestHeader
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructServiceRequestHeader originally named ServiceRequestHeader
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructServiceRequestHeader extends PCMWSStructRequestHeader
{
    /**
     * The APIKey
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $APIKey;
    /**
     * The Account
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Account;
    /**
     * The AccountId
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var long
     */
    public $AccountId;
    /**
     * The FeatureLevel
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $FeatureLevel;
    /**
     * The IsAdmin
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $IsAdmin;
    /**
     * The UserId
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var long
     */
    public $UserId;
    /**
     * Constructor method for ServiceRequestHeader
     * @see parent::__construct()
     * @param string $_aPIKey
     * @param string $_account
     * @param long $_accountId
     * @param int $_featureLevel
     * @param boolean $_isAdmin
     * @param long $_userId
     * @return PCMWSStructServiceRequestHeader
     */
    public function __construct($_aPIKey = NULL,$_account = NULL,$_accountId = NULL,$_featureLevel = NULL,$_isAdmin = NULL,$_userId = NULL)
    {
        PCMWSWsdlClass::__construct(array('APIKey'=>$_aPIKey,'Account'=>$_account,'AccountId'=>$_accountId,'FeatureLevel'=>$_featureLevel,'IsAdmin'=>$_isAdmin,'UserId'=>$_userId),false);
    }
    /**
     * Get APIKey value
     * @return string|null
     */
    public function getAPIKey()
    {
        return $this->APIKey;
    }
    /**
     * Set APIKey value
     * @param string $_aPIKey the APIKey
     * @return string
     */
    public function setAPIKey($_aPIKey)
    {
        return ($this->APIKey = $_aPIKey);
    }
    /**
     * Get Account value
     * @return string|null
     */
    public function getAccount()
    {
        return $this->Account;
    }
    /**
     * Set Account value
     * @param string $_account the Account
     * @return string
     */
    public function setAccount($_account)
    {
        return ($this->Account = $_account);
    }
    /**
     * Get AccountId value
     * @return long|null
     */
    public function getAccountId()
    {
        return $this->AccountId;
    }
    /**
     * Set AccountId value
     * @param long $_accountId the AccountId
     * @return long
     */
    public function setAccountId($_accountId)
    {
        return ($this->AccountId = $_accountId);
    }
    /**
     * Get FeatureLevel value
     * @return int|null
     */
    public function getFeatureLevel()
    {
        return $this->FeatureLevel;
    }
    /**
     * Set FeatureLevel value
     * @param int $_featureLevel the FeatureLevel
     * @return int
     */
    public function setFeatureLevel($_featureLevel)
    {
        return ($this->FeatureLevel = $_featureLevel);
    }
    /**
     * Get IsAdmin value
     * @return boolean|null
     */
    public function getIsAdmin()
    {
        return $this->IsAdmin;
    }
    /**
     * Set IsAdmin value
     * @param boolean $_isAdmin the IsAdmin
     * @return boolean
     */
    public function setIsAdmin($_isAdmin)
    {
        return ($this->IsAdmin = $_isAdmin);
    }
    /**
     * Get UserId value
     * @return long|null
     */
    public function getUserId()
    {
        return $this->UserId;
    }
    /**
     * Set UserId value
     * @param long $_userId the UserId
     * @return long
     */
    public function setUserId($_userId)
    {
        return ($this->UserId = $_userId);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructServiceRequestHeader
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
