<?php
/**
 * File for class PCMWSServiceImport
 * @package PCMWS
 * @subpackage Services
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSServiceImport originally named Import
 * @package PCMWS
 * @subpackage Services
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSServiceImport extends PCMWSWsdlClass
{
    /**
     * Sets the AuthHeader SoapHeader param
     * @uses PCMWSWsdlClass::setSoapHeader()
     * @param PCMWSStructAuthHeader $_pCMWSStructAuthHeader
     * @param string $_nameSpace http://www.alk.com
     * @param bool $_mustUnderstand
     * @param string $_actor
     * @return bool true|false
     */
    public function setSoapHeaderAuthHeader(PCMWSStructAuthHeader $_pCMWSStructAuthHeader,$_nameSpace = 'http://www.alk.com',$_mustUnderstand = false,$_actor = null)
    {
        return $this->setSoapHeader($_nameSpace,'AuthHeader',$_pCMWSStructAuthHeader,$_mustUnderstand,$_actor);
    }
    /**
     * Method to call the operation originally named ImportAvoidFavorSet
     * Meta informations extracted from the WSDL
     * - SOAPHeaderNames : AuthHeader
     * - SOAPHeaderNamespaces : http://www.alk.com
     * - SOAPHeaderTypes : {@link PCMWSStructAuthHeader}
     * - SOAPHeaders : required
     * @uses PCMWSWsdlClass::getSoapClient()
     * @uses PCMWSWsdlClass::setResult()
     * @uses PCMWSWsdlClass::saveLastError()
     * @param PCMWSStructImportAvoidFavorSet $_pCMWSStructImportAvoidFavorSet
     * @return PCMWSStructImportAvoidFavorSetResponse
     */
    public function ImportAvoidFavorSet(PCMWSStructImportAvoidFavorSet $_pCMWSStructImportAvoidFavorSet)
    {
        try
        {
            return $this->setResult(self::getSoapClient()->ImportAvoidFavorSet($_pCMWSStructImportAvoidFavorSet));
        }
        catch(SoapFault $soapFault)
        {
            return !$this->saveLastError(__METHOD__,$soapFault);
        }
    }
    /**
     * Returns the result
     * @see PCMWSWsdlClass::getResult()
     * @return PCMWSStructImportAvoidFavorSetResponse
     */
    public function getResult()
    {
        return parent::getResult();
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
