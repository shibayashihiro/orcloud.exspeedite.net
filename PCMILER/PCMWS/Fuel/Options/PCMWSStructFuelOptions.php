<?php
/**
 * File for class PCMWSStructFuelOptions
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructFuelOptions originally named FuelOptions
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructFuelOptions extends PCMWSWsdlClass
{
    /**
     * The UserID
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $UserID;
    /**
     * The Password
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Password;
    /**
     * The Account
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Account;
    /**
     * The FuelCap
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var double
     */
    public $FuelCap;
    /**
     * The Level
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var double
     */
    public $Level;
    /**
     * The MPG
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var double
     */
    public $MPG;
    /**
     * Constructor method for FuelOptions
     * @see parent::__construct()
     * @param string $_userID
     * @param string $_password
     * @param string $_account
     * @param double $_fuelCap
     * @param double $_level
     * @param double $_mPG
     * @return PCMWSStructFuelOptions
     */
    public function __construct($_userID = NULL,$_password = NULL,$_account = NULL,$_fuelCap = NULL,$_level = NULL,$_mPG = NULL)
    {
        parent::__construct(array('UserID'=>$_userID,'Password'=>$_password,'Account'=>$_account,'FuelCap'=>$_fuelCap,'Level'=>$_level,'MPG'=>$_mPG),false);
    }
    /**
     * Get UserID value
     * @return string|null
     */
    public function getUserID()
    {
        return $this->UserID;
    }
    /**
     * Set UserID value
     * @param string $_userID the UserID
     * @return string
     */
    public function setUserID($_userID)
    {
        return ($this->UserID = $_userID);
    }
    /**
     * Get Password value
     * @return string|null
     */
    public function getPassword()
    {
        return $this->Password;
    }
    /**
     * Set Password value
     * @param string $_password the Password
     * @return string
     */
    public function setPassword($_password)
    {
        return ($this->Password = $_password);
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
     * Get FuelCap value
     * @return double|null
     */
    public function getFuelCap()
    {
        return $this->FuelCap;
    }
    /**
     * Set FuelCap value
     * @param double $_fuelCap the FuelCap
     * @return double
     */
    public function setFuelCap($_fuelCap)
    {
        return ($this->FuelCap = $_fuelCap);
    }
    /**
     * Get Level value
     * @return double|null
     */
    public function getLevel()
    {
        return $this->Level;
    }
    /**
     * Set Level value
     * @param double $_level the Level
     * @return double
     */
    public function setLevel($_level)
    {
        return ($this->Level = $_level);
    }
    /**
     * Get MPG value
     * @return double|null
     */
    public function getMPG()
    {
        return $this->MPG;
    }
    /**
     * Set MPG value
     * @param double $_mPG the MPG
     * @return double
     */
    public function setMPG($_mPG)
    {
        return ($this->MPG = $_mPG);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructFuelOptions
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
