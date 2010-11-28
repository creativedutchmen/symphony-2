<?php

	require_once(TOOLKIT . '/class.emailgateway.php');
	require_once(TOOLKIT . '/class.emailhelper.php');
	require_once(TOOLKIT . '/class.smtp.php');
	
	/**
	 * One of the two core email gateways.
	 * Provides simple SMTP functionalities.
	 * Supports AUTH LOGIN, SSL and TLS.
	 *
	 * @author Huib Keemink, Michael Eichelsdörfer
	 * @todo document, test
	 */
	
	Class SMTPGateway extends EmailGateway{
		
		protected $_headers = Array(
			'Content-Transfer-Encoding' => 'quoted-printable',
		);
		
		protected $_SMTP;
		
		protected $_host;
		protected $_port;
		protected $_protocol = 'tcp';
		protected $_secure = false;		
		protected $_auth = false;		
		protected $_user;
		protected $_pass;
		
		public function about(){
			return array(
				'name' => 'SMTP (Core)',
			);
		}
		
		public function __construct(){
			parent::__construct();
			$this->setSenderEmailAddress(Symphony::Configuration()->get('default_from_address', 'email_smtp') ? Symphony::Configuration()->get('default_from_address', 'email_smtp') : 'noreply@' . HTTP_HOST);
			$this->setSenderName(Symphony::Configuration()->get('default_from_name', 'email_smtp') ? Symphony::Configuration()->get('default_from_name', 'email_smtp') : 'Symphony');
			$this->setHost(Symphony::Configuration()->get('host', 'email_smtp'));
			$this->setPort(Symphony::Configuration()->get('port', 'email_smtp'));
			if(Symphony::Configuration()->get('auth', 'email_smtp') == 1){
				$this->setAuth(true);
				$this->setUser(Symphony::Configuration()->get('username', 'email_smtp'));
				$this->setPass(Symphony::Configuration()->get('password', 'email_smtp'));
			}
			if(Symphony::Configuration()->get('tls', 'email_smtp') == 1){
				$this->_protocol = 'tcp';
				$this->_secure = 'tls';
			}
		}
		
		public function send(){
		
			$this->validate();
			
			$settings = array();
			if($this->_auth == true){
				$settings['username'] = $this->_user;
				$settings['password'] = $this->_pass;
			}
			if($this->_secure == 'tls'){
				$settings['secure'] = 'tls';
			}
			try{
				$this->_SMTP = new SMTP($this->_host, $this->_port, $settings);
				foreach($this->headers as $header=>$value){
					$this->_SMTP->setHeader($header, EmailHelper::qpEncodeHeader($value, 'UTF-8'));
				}
				$this->message = EmailHelper::qpEncodeBodyPart($this->message, 'UTF-8');
				$this->_SMTP->sendMail($this->sender_email_address, $this->recipients, $this->subject, $this->message);
			}
			catch(SMTPException $e){
				throw new EmailGatewayException($e->getMessage());
			}
			return true;
		}
		
		public function setHost($host = null){
			if($host === null){
				$host = '127.0.0.1';
			}
			if(substr($host, 0, 6) == 'ssl://'){
				$this->_protocol = 'ssl';
				$host = substr($host, 6);
			}
			$this->_host = $host;
		}
		
		public function setPort($port = null){
			if($port === null){
				if($this->_protocol == 'ssl'){
					$port = 465;
				}
				$port = 25;
			}
			$this->_port = $port;
		}
		
		public function setUser($user = null){
			$this->_user = $user;
		}
		
		public function setPass($pass = null){
			$this->_pass = $pass;
		}
		
		public function setAuth($auth = false){
			$this->_auth = $auth;
		}
		
		public function getPreferencesPane(){
			parent::getPreferencesPane();
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings selectable');
			$group->setAttribute('id', 'smtp');
			$group->appendChild(new XMLElement('legend', __('Email: Core SMTP')));

			$div = new XMLElement('div');
			$div->setAttribute('class', 'group');

			$label = Widget::Label('Default: From Name');
			$label->appendChild(Widget::Input('settings[email_smtp][default_from_name]', $this->sender_name));
			$div->appendChild($label);

			$label = Widget::Label('Default: From Email Address');
			$label->appendChild(Widget::Input('settings[email_smtp][default_from_address]', $this->sender_email_address));
			$div->appendChild($label);

			$group->appendChild($div);

			$group->appendChild(new XMLElement('p', __('The core will use these default settings to send email. The settings can be overwritten if necessary.'), array('class' => 'help')));
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'group');

			$label = Widget::Label(__('Host'));
			$label->appendChild(Widget::Input('settings[email_smtp][host]', ($this->_protocol == 'ssl' ? 'ssl://' : '') . $this->_host));
			$div->appendChild($label);

			$label = Widget::Label(__('Port'));
			$label->appendChild(Widget::Input('settings[email_smtp][port]', $this->_port));
			$div->appendChild($label);
			$group->appendChild($div);
			
			$label = Widget::Label();
			// To fix the issue with checkboxes that do not send a value when unchecked.
			$group->appendChild(Widget::Input('settings[email_smtp][tls]', '0', 'hidden'));
			$input = Widget::Input('settings[email_smtp][tls]', '1', 'checkbox');
			if($this->_secure == 'tls') $input->setAttribute('checked', 'checked');
			$label->setValue($input->generate() . ' Use TLS');
			$group->appendChild($label);

			$group->appendChild(new XMLElement('p', 'TLS (Transport Layer Security) should be used if the server supports it. Please check the manual of your email provider for details.', array('class' => 'help')));			
			
			$label = Widget::Label();
			// To fix the issue with checkboxes that do not send a value when unchecked.
			$group->appendChild(Widget::Input('settings[email_smtp][auth]', '0', 'hidden'));
			$input = Widget::Input('settings[email_smtp][auth]', '1', 'checkbox');
			if($this->_auth == true) $input->setAttribute('checked', 'checked');
			$label->setValue($input->generate() . ' Requires authentication');
			$group->appendChild($label);			
			
			$group->appendChild(new XMLElement('p', 'Some SMTP connections require authentication. If that is the case, enter the username/password combination below.', array('class' => 'help')));

			$div = new XMLElement('div');
			$div->setAttribute('class', 'group');

			$label = Widget::Label(__('Username'));
			$label->appendChild(Widget::Input('settings[email_smtp][username]', $this->_user));
			$div->appendChild($label);

			$label = Widget::Label(__('Password'));
			$label->appendChild(Widget::Input('settings[email_smtp][password]', $this->_pass, 'password'));
			$div->appendChild($label);
			$group->appendChild($div);
			
			return $group;
		}
	}