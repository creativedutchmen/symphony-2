<?php

	require_once(TOOLKIT . '/class.emailgateway.php');

	Class SendmailGateway extends EmailGateway{

		public function __construct(){
			$this->setSenderEmailAddress(Symphony::Configuration()->get('default_from_address', 'email_sendmail') ? Symphony::Configuration()->get('default_from_address', 'email_sendmail') : 'noreply@' . HTTP_HOST);
			$this->setSenderName(Symphony::Configuration()->get('default_from_name', 'email_sendmail') ? Symphony::Configuration()->get('default_from_name', 'email_sendmail') : 'Symphony');
		}

		public function about(){
			return array(
				'name' => 'Sendmail (default)',
			);
		}


		public function send(){

			$this->validate();

			$this->subject = self::encodeHeader($this->subject, 'UTF-8');
			$this->sender_name = self::encodeHeader($this->sender_name, 'UTF-8');

			$default_headers = array(
				'Return-Path'	=> $this->sender_email_address,
				'Message-ID'	=> sprintf('<%s@%s>', md5(uniqid(time())), $_SERVER['SERVER_NAME']),
				'From'			=> "{$this->sender_name} <{$this->sender_email_address}>",
		 		'Reply-To'		=> $this->sender_email_address,
				'Return-Path'	=> "<{$this->sender_email_address}>",
				'X-Mailer'		=> 'Symphony Email Module',
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

			$result = mail($this->recipient, $this->subject, @wordwrap($this->message, 70), @implode("\r\n", $headers) . "\r\n", "-f{$this->sender_email_address}");

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

		public function getPreferencesPane(){
			parent::getPreferencesPane();
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', __('Email: Sendmail')));

			$div = new XMLElement('div');
			$div->setAttribute('class', 'group');

			$label = Widget::Label('Default: From Name');
			$label->appendChild(Widget::Input('settings[email_sendmail][default_from_name]', $this->sender_name));
			$div->appendChild($label);

			$label = Widget::Label('Default: From Email Address');
			$label->appendChild(Widget::Input('settings[email_sendmail][default_from_address]', $this->sender_email_address));
			$div->appendChild($label);

			$group->appendChild($div);

			$group->appendChild(new XMLElement('p', __('The core will use these default settings to send email. The settings can be overwritten if necessary.'), array('class' => 'help')));
			return $group;
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
			// Huib: Added this check to the place the data is entered, instead of when it is used.
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
	}

