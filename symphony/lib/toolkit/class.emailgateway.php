<?php

	/**
	 * @package toolkit
	 */
	 
	
	
	/**
	 * The standard exception to be thrown by all email gateways.
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
	 * The validation exception to be thrown by all email gateways.
	 * This exception is thrown if data does not pass validation.
	 */
	class EmailValidationException extends EmailGatewayException{
	}
	
	/**
	 * A base class for email gateways.
	 * All email-gateways should extend this class in order to work.
	 *
	 * @todo add validation to all set functions.
	 */
	Abstract Class EmailGateway{
		
		/**
		 * @property array $headers
		 * 	This are the default symphony headers.
		 * 	These headers should be merged with the default headers of each gateway.
		 */
		protected $headers = Array(
			'X-Mailer'		=> 'Symphony Email Module',
			'MIME-Version'	=> '1.0',
			'Content-Type'	=> 'text/plain; charset=UTF-8',
			'From'			=>	''
		);
		
		/**
		 * @property array $_headers
		 * 	This are the headers set by each gateway.
		 *	Every gateway should place gateway specific headers in this array (in its own class definition, ofcourse).
		 */
		protected $_headers = Array();
		protected $recipients = Array();
		protected $sender_name;
		protected $sender_email_address;
		protected $subject;
		protected $message;
		
		/**
		 * @return void
		 */
		public function __construct(){
			// Merge headers with dynamic headers.
			$this->headers = array_merge($this->headers, Array(
				'Message-ID'	=>	sprintf('<%s@%s>', md5(uniqid()) , HTTP_HOST),
				
			));
			
			// Merge headers with headers set by child.
			$this->headers = array_merge($this->headers, $this->_headers);
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
		 * 	The email-address emails will be sent from
		 * @param string $name
		 *	The name the emails will be sent from.
		 * @return void
		 */
		public function setFrom($email, $name){
			$this->setSenderEmailAddress($email);
			$this->setSenderName($name);
		}
		
		/**
		 * Sets the sender-email.
		 *
		 * @param string $email
		 * 	The email-address emails will be sent from
		 * @return void
		 */
		public function setSenderEmailAddress($email){
			if(preg_match('%[\r\n]%', $email)){
				throw new EmailValidationException('Sender Email Address can not contain cariage return or newlines.');
			}
			$this->sender_email_address = $email;
			// $this->appendHeader('From', $this->sender_name . ' <' . $this->sender_email_address . '>');
		}
		
		/**
		 * Sets the sender-name.
		 *
		 * @param string $name
		 * 	The name emails will be sent from
		 * @return void
		 */
		public function setSenderName($name){
			if(preg_match('%[\r\n]%', $name)){
				throw new EmailValidationException('Sender Name can not contain cariage return or newlines.');
			}
			$this->sender_name = $name;
			// $this->appendHeader('From', $this->sender_name . ' <' . $this->sender_email_address . '>');
		}
		
		/**
		 * Sets the recipients.
		 *
		 * @param string|array $email
		 * 	The email-address(es) to send the email to.
		 * @return void	
		 */
		public function setRecipients($email){
			//TODO: sanitizing and security checking
			if(!is_array($email)){
				$email = Array($email);
			}
			$this->recipients = $email;
			// $to_header = EmailHelper::arrayToList($email);
			// $this->appendHeader('To', $to_header);
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
			// $this->appendHeader('Subject', $this->subject);
		}
		
		/**
		 * Appends a header to the header list.
		 * Headers should be presented as a name/value pair.
		 *
		 * @param string $name
		 * 	The header name. Examples are From, X-Sender and Reply-to
		 * @param string $value
		 *	The header value.
		 * @return void
		 */
		public function appendHeader($name, $value){
			if(is_array($value)){
				throw new EmailGatewayException('Headers can only contain strings, arrays are not allowed');
			}
			$this->headers[$name] = $value;
		}
		/**
		 * Check to see if all required data is set.
		 * 
		 * @return bool
		 */
		public function validate(){

			// Make sure the Message, Recipient, Sender Name and Sender Email values are set
			if(strlen(trim($this->message)) <= 0){
				throw new EmailValidationException('Email message cannot be empty.');
			}

			elseif(strlen(trim($this->subject)) <= 0){
				throw new EmailValidationException('Email subject cannot be empty.');
			}

			elseif(strlen(trim($this->sender_name)) <= 0){
				throw new EmailValidationException('Sender name cannot be empty.');
			}

			elseif(strlen(trim($this->sender_email_address)) <= 0){
				throw new EmailValidationException('Sender email address cannot be empty.');
			}

			else{
				foreach($this->recipients as $recipient){
					if(strlen(trim($recipient)) <= 0){
						throw new EmailValidationException('Recipient email address cannot be empty.');
					}
				}
			}
			return true;
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
			if(method_exists(get_class($this), 'set'.$this->__toCamel($name, true))){	
				return $this->{'set'.$this->__toCamel($name, true)}($value);
			}
			else{
				throw new EmailGatewayException('The ' . get_class($this) . ' gateway does not support the use of '.$name);
			}
		}
		
		/**
		 * The preferences to add to the preferences pane in the admin area.
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