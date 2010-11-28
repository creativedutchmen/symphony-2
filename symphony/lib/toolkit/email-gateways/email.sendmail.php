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

			$this->subject = EmailHelper::qpEncodeHeader($this->subject, 'UTF-8');
			$this->sender_name = EmailHelper::qpEncodeHeader($this->sender_name, 'UTF-8');

			foreach ($this->headers as $header => $value) {
				if(!is_array($value)){
					$value = Array($value);
				}
				foreach($value as $val){
					$headers[] = sprintf('%s: %s', $header, $val);
				}
			}

			$this->message = EmailHelper::qpEncodeBodyPart($this->message);
			$this->message = str_replace("\r\n", "\n", $this->message);
			
			foreach($this->recipients as $to){
				$result = @mail($to, $this->subject, $this->message, @implode("\r\n", $headers) . "\r\n", "-f{$this->sender_email_address}");
				if($result !== true){
					throw new EmailGatewayException('Email failed to send. Please check input and make sure php is not running in safe mode.');
				}
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

