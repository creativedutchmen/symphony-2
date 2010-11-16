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
		
		public function setFrom(){
		}
		
		public function setRecipient(){
		}
		
		public function setMessage(){
		}
		
		public function setSubject(){
		}
		
		public function appendHeader(){
		}
		
		public function __set(){
		}
		
	}