<?php

	class EmailGatewayException extends Exception{
	}
	
	Abstract Class EmailGateway{
		
		//minimal properties of any email sent.
		protected $headers;
		protected $recipient;
		protected $sender_name;
		protected $sender_email_address;
		protected $subject;
		protected $message;
		
		public function __construct(){
		}
		
		public function send(){
		}
		
		public function setFrom($email, $name){
			$this->setSenderEmailAdress($email);
			$this->setSenderName($name);
		}
		
		public function setSenderEmailAdress($email){
			//TODO: sanitizing and security checking
			$this->sender_email_adress = $email;
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