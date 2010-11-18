<?php

	// Email factory class.
	// Returns the right gateway class when initialised
	
	include_once(TOOLKIT . '/class.emailgatewaymanager.php');
	
	Abstract class Email{
		
		private $gateway;
	
		// Will use the gateway set in the preferences when no gateway is set here.
		// Setting this to a specific gateway should only be used if special features of that gateway are used.
		function create($gateway = null){
			$email_gateway_manager = new EmailGatewayManager($this);
			if($gateway){
				$default_gateway = $email_gateway_manager->find($gateway);
				if($default_gateway){
					return $email_gateway_manager->create($default_gateway);
				}
				else{
					throw new EmailException('Can not find default gateway. Please check if the extension supplying the gateway is still installed, or change settings accordingly');
				}
			}
			else{
				return $email_gateway_manager->create($email_gateway_manager->getDefaultGateway());
			}
		}
	}