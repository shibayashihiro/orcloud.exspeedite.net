<?php

// $Id:$
// Open SSL certificates

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );
require_once( "sts_setting_class.php" );

class sts_certificate extends sts_table {
	
	public function __construct( $database, $debug = false ) {		
		$this->debug = $debug;
		$this->database = $database;

		if( $this->debug ) echo "<p>Create sts_certificate</p>";
	}

	// Allow re-use of objects - singleton function
	public static function getInstance( $database, $debug = false ) {
		static $instance = null;
		$myclass = get_class ();
		if( $debug ) echo "<p>Get instance of $myclass</p>";
		if (null === $instance) {
			$instance = new $myclass( $database, $debug );
		}
		return $instance;
    }

	function __destruct() {
		if( $this->debug ) echo "<p>Destroy sts_certificate</p>";
	}

	public function create( $certname='certificate', $passphrase=null,
		$createpem=true, $overwrite=true ){
		$out = false;
		
		if( !empty( $certname ) ){
			$out = new stdClass;
			$days = 365;
						
			$config=array(
				//'config'            =>  $conf,
				'digest_alg'        =>  'AES-128-CBC',
				'private_key_bits'  =>  1024,
				'private_key_type'  =>  OPENSSL_KEYTYPE_RSA,
				'encrypt_key'       =>  false
			);
			
			$dn=array(
				"countryName"               => "US",
				"stateOrProvinceName"       => "Minnesota",
				"organizationName"          => "exspeedite.com",
				"organizationalUnitName"    => "Exspeedite",
				"commonName"                => $certname,
				"emailAddress"              => "admin@exspeedite.com"
			);
			
			$privkey = openssl_pkey_new( $config );
			
			openssl_pkey_export($privkey, $pk);

			$csr = openssl_csr_new( $dn, $privkey, $config );
			$cert = openssl_csr_sign( $csr, null, $privkey, $days, $config, 0 );
			
			openssl_x509_export( $cert, $out->pub );
			openssl_pkey_export( $privkey, $out->priv, $passphrase );
			openssl_csr_export( $csr, $out->csr );

			echo "<pre>out\n";
			var_dump($out); // , openssl_x509_parse($out->pub)
			echo "</pre>";
		}

		return $out;
	}

}

?>