<?php

	/**
	 * @package toolkit
	 */
	
	/**
	 * The Exception to be thrown by all email gateways.
	 */
	class EmailGatewayException extends Exception{
		
		/**
		 * Creates a new exception, and logs the error.
		 *
		 * @param string $message
		 * @param int $code
		 * @param Exception $previous
		 *	The previous exception, if nested. see http://www.php.net/manual/en/language.exceptions.extending.php
		 * @return void
		 */
		public function __construct($message, $code = 0, $previous = null){
			Symphony::$Log->pushToLog('Email Gateway Error: ' . $message, 'Email', true);
			parent::__construct();
		}
	}
	
	/**
	 * A base class for email gateways.
	 * All email-gateways should extend this class in order to work.
	 *
	 * @todo add validation to all set functions.
	 */
	Abstract Class EmailGateway{
		
		protected $headers = Array();
		protected $recipient;
		protected $sender_name;
		protected $sender_email_address;
		protected $subject;
		protected $message;
		
		/**
		 * @return void
		 */
		public function __construct(){
		}
		
		/**
		 * Sends the actual email.
		 * This function should be set on the email-gateway itself.
		 * See the default gateway for an example.
		 *
		 * @return void
		 */
		public function send(){
		}
		
		/**
		 * Sets the sender-email and sender-name.
		 *
		 * @param string $email
		 * 	The email-adress emails will be sent from
		 * @param string $name
		 *	The name the emails will be sent from.
		 * @return void
		 */
		public function setFrom($email, $name){
			$this->setSenderEmailAdress($email);
			$this->setSenderName($name);
		}
		
		/**
		 * Sets the sender-email.
		 *
		 * @param string $email
		 * 	The email-adress emails will be sent from
		 * @return void
		 */
		public function setSenderEmailAddress($email){
			//TODO: sanitizing and security checking
			$this->sender_email_address = $email;
		}
		
		/**
		 * Sets the sender-name.
		 *
		 * @param string $name
		 * 	The name emails will be sent from
		 * @return void
		 */
		public function setSenderName($name){
			//TODO: sanitizing and security checking
			$this->sender_name = $name;
		}
		
		/**
		 * Sets the recipient.
		 *
		 * @param string $email
		 * 	The email-adress to send the email to.
		 * @return void
		 * @todo accept array and string. Array should email the email to multiple recipients. 	
		 */
		public function setRecipient($email){
			//TODO: sanitizing and security checking
			$this->recipient = $email;
		}
		
		
		/**
		 * Sets the message.
		 *
		 * @param string $message
		 * 	The message to be sent. Can be html or text.
		 * @return void
		 */
		public function setMessage($message){
			//TODO: sanitizing and security checking
			$this->message = $message;
		}
		
		/**
		 * Sets the subject.
		 *
		 * @param string $subject
		 * 	The subject that the email will have.
		 * @return void
		 */
		public function setSubject($subject){
			//TODO: sanitizing and security checking;
			$this->subject = $subject;
		}
		
		/**
		 * Appends a header to the header list.
		 * New headers should be presented as a name/value pair.
		 *
		 * @param string $name
		 * 	The header name. Examples are From, X-Sender and Reply-to
		 * @param string $value
		 *	The header value.
		 * @param bool @replace
		 * 	If set to true, if a header is already set, it will be replaced.
		 * 	If set to false, if a header is already set, an exception will be thrown.
		 * @return void
		 */
		public function appendHeader($name, $value, $replace=true){
			if($replace === false && array_key_exists($name, $this->headers)){
				throw new EmailGatewayException("The header '{$name}' has already been set.");
			}
			$this->headers[$name] = $value;
		}
		
		/**
		 * Sets a property.
		 * Magic function, supplied by php.
		 * This function will try and find a method of this class, by camelcasing the name, and appending it with set.
		 * If the function can not be found, an exception will be thrown.
		 *
		 * @param string $name
		 * 	The property name.
		 * @param string $value
		 *	The property value;
		 * @return void|bool
		 */
		public function __set($name, $value){	
			if(method_exists(__CLASS__, 'set'.$this->__toCamel($name, true))){	
				return $this->{'set'.$this->__toCamel($name, true)}($value);
			}
			else{
				throw new EmailGatewayException('The ' . get_class($this) . ' gateway does not support the use of '.$name);
			}
		}
		
		/**
		 * The preferences to add to the preferences pane in the admin area.
		 * The from_email and from_name can be kept between different gateways.
		 * Reuse (by using the output for the preferences pane) is encouraged.
		 *
		 * @return XMLElement
		 */
		public function getPreferencesPane(){
			return new XMLElement('fieldset');
		}
		
		/**
		 * Internal function to turn underscored variables into camelcase, for use in methods.
		 * Because Symphony has a difference in naming between properties and methods (underscored vs camelcased)
		 * and the Email class uses the magic __set function to find property-setting-methods, this conversion is needed.
		 *
		 * @param string $string
		 * 	The string to convert
		 * @param bool $caseFirst
		 *	if this is true, the first character will be uppercased. Useful for method names (setName).
		 *	If set to false, the first character will be lowercased. This is default behaviour.
		 * @return string
		 */
		private function __toCamel($string, $caseFirst = false){
			$string = strtolower($string);
			$a = explode('_', $string);
			$a = array_map(ucfirst, $a);
			if(!$caseFirst){
				$a[0] = lcfirst($a[0]);
			}
			return implode('', $a);
		}
		
		/**
		 * The reverse of the __toCamel function.
		 *
		 * @param string $string
		 * 	The string to convert
		 * @return string
		 */
		private function __fromCamel($string){
			$string[0] = strtolower($string[0]);
			$func = create_function('$c', 'return "_" . strtolower($c[1]);');
			return preg_replace_callback('/([A-Z])/', $func, $str);
		}
		
	}