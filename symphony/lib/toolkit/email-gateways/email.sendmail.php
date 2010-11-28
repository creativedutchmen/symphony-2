<?php

	require_once(TOOLKIT . '/class.emailgateway.php');
	require_once(TOOLKIT . '/class.emailhelper.php');

	Class SendmailGateway extends EmailGateway{
	
		protected $_headers = Array(
		);
		
		public function about(){
			return array(
				'name' => 'Sendmail (default)',
			);
		}

		public function __construct(){
			parent::__construct();
			$this->setSenderEmailAddress(Symphony::Configuration()->get('default_from_address', 'email_sendmail') ? Symphony::Configuration()->get('default_from_address', 'email_sendmail') : 'noreply@' . HTTP_HOST);
			$this->setSenderName(Symphony::Configuration()->get('default_from_name', 'email_sendmail') ? Symphony::Configuration()->get('default_from_name', 'email_sendmail') : 'Symphony');
		}

		public function send(){

			$this->validate();

			$this->subject = @wordwrap(EmailHelper::qpEncodeHeader($this->subject, 'UTF-8'), 75, "\r\n ");
			// $this->appendHeader('Return-path', '001@imacoda.com'); // no way to set the return-path... will be overwritten
			$this->appendHeader('From', EmailHelper::qpEncodeHeader($this->sender_name, 'UTF-8') . ' <' . $this->sender_email_address . '>');

			foreach ($this->headers as $header => $value) {
				$headers[] = sprintf('%s: %s', $header, $value);
			}
			// die(print_r($headers));

			$to_array = array();
			foreach($this->recipients as $name => $address){
				$to_array[EmailHelper::qpEncodeHeader($name)] = $address;
			}
			$to_header = EmailHelper::arrayToList($to_array);
			
			$this->message = EmailHelper::qpEncodeBodyPart($this->message);
			$this->message = str_replace("\r\n", "\n", $this->message);

			$result = @mail($to_header, $this->subject, $this->message, @implode("\r\n", $headers) . "\r\n", "-f{$this->sender_email_address}");

			if($result !== true){
				throw new EmailGatewayException('Email failed to send. Please check input and make sure php is not running in safe mode.');
			}

		

			return true;
		}

		public function getPreferencesPane(){
			parent::getPreferencesPane();
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings selectable');
			$group->setAttribute('id', 'sendmail');
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
	}

