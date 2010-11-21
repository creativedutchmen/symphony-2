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

			$this->subject = self::qpEncodeHeader($this->subject, 'UTF-8');
			$this->sender_name = self::qpEncodeHeader($this->sender_name, 'UTF-8');

			$default_headers = array(
				'Return-path'	=> "<{$this->sender_email_address}>",
				'Message-ID'	=> sprintf('<%s@%s>', md5(uniqid(time())), $_SERVER['SERVER_NAME']),
				'From'			=> "{$this->sender_name} <{$this->sender_email_address}>",
		 		'Reply-To'		=> $this->sender_email_address,
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

			$this->message = $this->qpEncodeBodyPart($this->message);
			$this->message = str_replace("\r\n", "\n", $this->message);

			$result = mail($this->recipient, $this->subject, $this->message, @implode("\r\n", $headers) . "\r\n", "-f{$this->sender_email_address}");

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

		Method: qpEncodeHeader
		Description: Encodes (parts of) an email header if necessary, according to RFC2047 if mbstring is available;
		Added by: Michael Eichelsdoerfer

		***/
		public static function qpEncodeHeader($input, $charset='ISO-8859-1')
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

		/**
		 * quoted-printable body encoding
		 *
		 * method to quoted-printable-encode an email message body according to RFC 2045;
		 * includes line wrapping according to RFC 822/2822 (using CRLF) as required by RFC 2045;
		 * for PHP >= 5.3 we might use the built-in quoted_printable_encode() function instead,
		 * but we should keep in mind that the (human-)readabilty of its output is much worse;
		 *
		 * http://php.net/manual/en/function.quoted-printable-encode.php
		 * http://php.net/manual/en/function.quoted-printable-decode.php
		 *
		 * @param string $input
		 * @param string $line_max; maximum line length
		 * @return string
		 */
		public static function qpEncodeBodyPart($input) {
			### PHP >= 5.3 is coming with a built-in function to handle message body encoding
			if(function_exists('quoted_printable_encode')){
				return quoted_printable_encode($input);
			}
			else{
				$hex = array('0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F');
				$lines = preg_split("/(?:\r\n|\r|\n)/", $input);
				$line_max = 76;
				$linebreak = "\r\n";
				$softlinebreak = "=\r\n";

				$line_max = $line_max - strlen($softlinebreak);

				$output = "";
				$cur_conv_line = "";
				$length = 0;
				$whitespace_pos = 0;
				$addtl_chars = 0;

				// iterate lines
				for ($j=0; $j < count($lines); $j++) {
					$line = $lines[$j];
					$linlen = strlen($line);

					// iterate chars
					for ($i = 0; $i < $linlen; $i++) {
						$c = substr($line, $i, 1);
						$dec = ord($c);
						$length++;

						// watch out for spaces
						if ($dec == 32) {
							// space occurring at end of line, need to encode
							if (($i == ($linlen - 1))) {
								$c = "=20";
								$length += 2;
							}
							$addtl_chars = 0;
							$whitespace_pos = $i;
						// characters to be encoded
						} elseif ( ($dec == 61) || ($dec < 32 ) || ($dec > 126) ) {
							$h2 = floor($dec/16);
							$h1 = floor($dec%16);
							$c = "=" . $hex["$h2"] . $hex["$h1"];
							$length += 2;
							$addtl_chars += 2;
							$enc_pos = $i;
						}

						// length for wordwrap exceeded, get a newline into the text
						if ($length >= $line_max) {
							$cur_conv_line .= $c;

							// read only up to the whitespace for the current line
							$whitesp_diff = $i - $whitespace_pos + $addtl_chars;
							$enc_diff = $i - $enc_pos + $addtl_chars;
							/*
							 * the text after the whitespace will have to
							 * be read again ( + any additional characters
							 * that came into existence as a result of the
							 * encoding process after the whitespace)
							 *
							 * Also, do not start at 0, if there was *no*
							 * whitespace in the whole line
							 */
							if (($i + $addtl_chars) > $whitesp_diff) {
								$output .= substr($cur_conv_line, 0,
								(strlen($cur_conv_line) - $whitesp_diff)) . $softlinebreak;
								$i = $i - $whitesp_diff + $addtl_chars;
							} else {
								$output .= $cur_conv_line . $softlinebreak;
							}

							$cur_conv_line = "";
							$length = 0;
							$whitespace_pos = 0;
						} else {
							// length for wordwrap not reached, continue reading
							$cur_conv_line .= $c;
						}
					}

					$length = 0;
					$whitespace_pos = 0;
					$output .= $cur_conv_line;
					$cur_conv_line = "";

					if ($j <= count($lines)-1) {
						$output .= $linebreak;
					}
				}

				return trim($output);				
			}
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

