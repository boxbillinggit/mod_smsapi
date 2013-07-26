<?php
/**
 * Example module API
 * This api can be access only for admins
 */
class Box_Mod_Smsapi_Api_Admin extends Api_Abstract
{


	public function sendsms($data) {
		$soap = null;
		
		try {

			$soap = new SoapClient( 'https://ssl.smsapi.pl/webservices/v2/?wsdl' , array(
						'features'   => SOAP_SINGLE_ELEMENT_ARRAYS,
						'cache_wsdl' => WSDL_CACHE_NONE,
						'trace'      => true,
					)
			);
		
			$client = array( 'username' => $data['sms_username'], 'password' => md5($data['sms_password']) );
			$sms = array(
				'sender'    => "".$data['sms_nadawca']."",
				'recipient' => $data['sms_odbiorca'],
				'eco'       => 0,
				'date_send' => 0,
				'details'   => 1,
				'message'   => "".$data['sms_wiad'].$data['nowych_wiad']."",
				'params'    => array(),
				'idx'       => uniqid(),
			);
		
			$params = array(
				'client' => $client,
				'sms'    => $sms
			);
		
			$soap->send_sms($params);
		
			
			
		}
		catch(Exception $e) {
			print_r($e);
			}

	}
	
}