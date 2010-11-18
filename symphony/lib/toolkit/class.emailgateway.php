<?php

	class EmailGatewayException extends Exception{
	}
	
	Abstract Class EmailGateway{
		
		//minimal properties of any email sent.
		protected $headers = Array();
		protected $recipient;
		protected $sender_name;
		protected $sender_email_address;
		protected $subject;
		protected $message;
		
		public function __construct(){
			$this->setSenderEmailAddress((Symphony::Configuration()->get('from_email', 'Email')) ? Symphony::Configuration()->get('from_email', 'Email') : 'noreply@' . HTTP_HOST);
			if(!Symphony::Configuration()->get('from_name', 'Email')){
				$author_manager = new AuthorManager();
				$author = $author_manager->fetch('user_type','ASC', 1);
				$this->setSenderName($author[0]->get('first_name') . ' ' . $author[0]->get('last_name'));
			}
			else{
				$this->setSenderName(Symphony::Configuration()->get('from_name', 'Email'));
			}
		}
		
		public function send(){
		}
		
		public function setFrom($email, $name){
			$this->setSenderEmailAdress($email);
			$this->setSenderName($name);
		}
		
		public function setSenderEmailAddress($email){
			//TODO: sanitizing and security checking
			$this->sender_email_address = $email;
		}
		
		public function setSenderName($name){
			//TODO: sanitizing and security checking
			$this->sender_name = $name;
		}
		
		public function setRecipient($email){
			//TODO: sanitizing and security checking
			$this->recipient = $email;
		}
		
		public function setMessage($message){
			//TODO: sanitizing and security checking
			$this->message = $message;
		}
		
		public function setSubject($subject){
			//TODO: sanitizing and security checking;
			$this->subject = $subject;
		}
		
		public function appendHeader($name, $value, $replace=true){
			if($replace === false && array_key_exists($name, $this->headers)){
				throw new EmailException("The header '{$name}' has already been set.");
			}
			$this->headers[$name] = $value;
		}
		
		public function __set($name, $value){	
			if(method_exists(__CLASS__, 'set'.$this->__toCamel($name, true))){	
				return $this->{'set'.$this->__toCamel($name, true)}($value);
			}
			else{
				throw new EmailGatewayException('The '.__CLASS__.' gateway does not support the use of '.$name);
			}
		}
		
		// The preferences to add to the preferences pane in the admin area.
		// Must return an XMLElement object.
		
		// The from_email and from_name can be kept between different gateways.
		// Reuse of this example is encouraged.
		public function getPreferencesPane(){
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', __('Email Gateway Settings')));		
	
			$label = Widget::Label('Send email from adress:');			
			$input = Widget::Input('settings[Email][from_email]', $this->sender_email_address);			
			$label->appendChild($input);
			$group->appendChild($label);	
			
			$label = Widget::Label('Send email from name:');			
			$input = Widget::Input('settings[Email][from_name]', $this->sender_name);			
			$label->appendChild($input);
			$group->appendChild($label);
		
			$group->appendChild(new XMLElement('p', __('All email gateways will use these settings to send email. Leave empty if you are not sure.'), array('class' => 'help')));
			return $group;
		}
		
		// Huib: to solve the differences in naming between methods and properties.
		private function __toCamel($string, $caseFirst = false){
			$string = strtolower($string);
			$a = explode('_', $string);
			$a = array_map(ucfirst, $a);
			if(!$caseFirst){
				$a[0] = lcfirst($a[0]);
			}
			return implode('', $a);
		}
		
		private function __fromCamel($string){
			$string[0] = strtolower($string[0]);
			$func = create_function('$c', 'return "_" . strtolower($c[1]);');
			return preg_replace_callback('/([A-Z])/', $func, $str);
		}
		
	}