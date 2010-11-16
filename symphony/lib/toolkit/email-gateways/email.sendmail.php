<?php

	Class SendmailGateway extends EmailGateway{
		
		protected $headers;
		protected $recipient;
		protected $sender_name;
		protected $sender_email_address;
		protected $subject;
		protected $message;
		
		public function __construct(){
			$this->headers = array();
			$this->recipient = $this->sender_name  = $this->sender_email_address = $this->subject = $this->message = NULL;
		}
		
		public function setFrom($sender_email_adress, $sender_name){
			if (eregi("(\r|\n)", $sender_name) || (eregi("(\r|\n)", $sender_email_address)){
				throw new EmailGatewayException("The sender name and/or email address contain invalid data. It cannot include new line or carriage return characters.");
			}
			else{
				$this->sender_email_adress = $sender_email_adress;
				$this->sender_name = $sender_name;
			}
		}
		
		public function setRecipient($recipient_email_adress, $recipient_name = NULL){
			
		}
		
		public function send(){
			
			$this->validate();
			
			$this->subject = self::encodeHeader($this->subject, 'UTF-8');
			$this->sender_name = self::encodeHeader($this->sender_name, 'UTF-8');
			
			// Huib: Not really a fan of this approach.
			// I would prefer the default settings to be set in the header definition of this class,
			// then permit the setHeader function to override those settings.
			
			$default_headers = array(
				'Return-Path'	=> $this->sender_email_address,
				'From'			=> "{$this->sender_name} <{$this->sender_email_address}>",
		 		'Reply-To'		=> $this->sender_email_address,
				'Message-ID'	=> sprintf('<%s@%s>', md5(uniqid(time())), $_SERVER['SERVER_NAME']),
				'Return-Path'	=> "<{$this->sender_email_address}>",
				'Importance'	=> 'normal',
				'Priority'		=> 'normal',
				'X-Sender'		=> 'Symphony Email Module <noreply@symphony-cms.com>',
				'X-Mailer'		=> 'Symphony Email Module',
				'X-Priority'	=> '3',
				'MIME-Version'	=> '1.0',
				'Content-Type'	=> 'text/plain; charset=UTF-8',
			);
		
			foreach($default_headers as $key => $value){
				try{
					$this->appendHeader($key, $value, false);
				}
				catch(Exception $e){
					//Its okay to discard errors. They mean the header was already set.
				}
			}
		
			foreach ($this->headers as $header => $value) {
				$headers[] = sprintf('%s: %s', $header, $value);
			}

			$result = mail($this->recipient, $this->subject, @wordwrap($this->message, 70), @implode(self::CRLF, $headers) . self::CRLF, "-f{$this->sender_email_address}");
			
			if($result !== true){
				throw new EmailGatewayException('Email failed to send. Please check input.');
			}
			
			return true;
		}
		
		public function appendHeader($name, $value, $replace=true){
			if($replace === false && array_key_exists($name, $this->headers)){
				throw new EmailGatewayException("The header '{$name}' has already been set.");
			}
			$this->headers[$name] = $value;
		}


		/***

		Method: encodeHeader
		Description: Encodes (parts of) an email header if necessary, according to RFC2047 if mbstring is available;
		Added by: Michael Eichelsdoerfer

		***/
		public static function encodeHeader($input, $charset='ISO-8859-1')
		{
		    if(preg_match_all('/(\s?\w*[\x80-\xFF]+\w*\s?)/', $input, $matches))
		    {
		        if(function_exists('mb_internal_encoding'))
		        {
		            mb_internal_encoding($charset);
		            $input = mb_encode_mimeheader($input, $charset, 'Q');
		        }
		        else
		        {
		            foreach ($matches[1] as $value)
		            {
		                $replacement = preg_replace('/([\x20\x80-\xFF])/e', '"=" . strtoupper(dechex(ord("\1")))', $value);
		                $input = str_replace($value, '=?' . $charset . '?Q?' . $replacement . '?=', $input);
		            }
		        }
		    }
		    return $input;
		}
		
		// Huib: Why should this be public?
		public function validate(){
			// Huib: Added this check to the place the 
			if (eregi("(\r|\n)", $this->sender_name) || eregi("(\r|\n)", $this->sender_email_address)){
				throw new EmailGatewayException("The sender name and/or email address contain invalid data. It cannot include new line or carriage return characters.");
			}
			
			// Make sure the Message, Recipient, Sender Name and Sender Email values are set
			if(strlen(trim($this->message)) <= 0){
				throw new EmailGatewayException('Email message cannot be empty.');
			}
			
			elseif(strlen(trim($this->subject)) <= 0){
				throw new EmailGatewayException('Email subject cannot be empty.');
			}
			
			elseif(strlen(trim($this->sender_name)) <= 0){
				throw new EmailGatewayException('Sender name cannot be empty.');
			}
			
			elseif(strlen(trim($this->sender_email_address)) <= 0){
				throw new EmailGatewayException('Sender email address cannot be empty.');
			}
			
			elseif(strlen(trim($this->recipient)) <= 0){
				throw new EmailGatewayException('Recipient email address cannot be empty.');
			}
			
			return true;
		}
		
		public function __set($name, $value){
			if(method_exists(__class__, 'set'.ucfirst(strtolower($name))){
				return $this->$name($value);
			}
			else{
				throw new EmailGatewayException('No method has been specified to set '.$name);
			}
		}
	}
	
