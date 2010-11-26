<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.emailgatewaymanager.php');

	class contentSystemPreferences extends AdministrationPage {
		public function __construct(&$parent){
			parent::__construct($parent);
			$this->setPageType('form');
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Preferences'))));
		}

		## Overload the parent 'view' function since we dont need the switchboard logic
		public function view() {
			$this->appendSubheading(__('Preferences'));

		    $bIsWritable = true;
			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));

		    if (!is_writable(CONFIG)) {
		        $this->pageAlert(__('The Symphony configuration file, <code>/manifest/config.php</code>, is not writable. You will not be able to save changes to preferences.'), Alert::ERROR);
		        $bIsWritable = false;

		    } else if ($formHasErrors) {
		    	$this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);

		    } else if (isset($this->_context[0]) && $this->_context[0] == 'success') {
		    	$this->pageAlert(__('Preferences saved.'), Alert::SUCCESS);
		    }

		    // Get available languages
		    $languages = Lang::getAvailableLanguages(new ExtensionManager(Administration::instance()));

			if(count($languages) > 1) {
			    // Create language selection
				$group = new XMLElement('fieldset');
				$group->setAttribute('class', 'settings');
				$group->appendChild(new XMLElement('legend', __('System Language')));
				$label = Widget::Label();

				// Get language names
				asort($languages);

				$options = array();
				foreach($languages as $code => $name) {
					$options[] = array($code, $code == Symphony::Configuration()->get('lang', 'symphony'), $name);
				}
				$select = Widget::Select('settings[symphony][lang]', $options);
				$label->appendChild($select);
				$group->appendChild($label);
				$group->appendChild(new XMLElement('p', __('Authors can set up a differing language in their profiles.'), array('class' => 'help')));
				// Append language selection
				$this->Form->appendChild($group);
			}

			//Get available EmailGateways
			$email_gateway_manager = new EmailGatewayManager($this);
			$email_gateways = $email_gateway_manager->listAll();
			if(count($email_gateways) >= 1){
				$group = new XMLElement('fieldset', NULL, array('class' => 'settings picker'));
				$group->appendChild(new XMLElement('legend', __('Email Gateway')));
				$label = Widget::Label();

				// Get gateway names
				ksort($email_gateways);

				$default_gateway = $email_gateway_manager->getDefaultGateway();
				$selected_is_installed = $email_gateway_manager->__find($default_gateway);

				$options = array();
				foreach($email_gateways as $handle => $details) {
					$options[] = array($handle, (($handle == $default_gateway) || (($selected_is_installed == false) && $handle == 'sendmail')), $details['name']);
				}
				$select = Widget::Select('settings[Email][default_gateway]', $options);
				$label->appendChild($select);
				$group->appendChild($label);
				$group->appendChild(new XMLElement('p', __('The Symphony core will use the selected gateway to send emails. More gateways can be installed using extensions, and any gateway may be used by custom events or extensions.'), array('class' => 'help')));
				// Append email gateway selection
				$this->Form->appendChild($group);
			}

			foreach($email_gateways as $gateway){
				$gateway_settings = $email_gateway_manager->create($gateway['handle'])->getPreferencesPane();

				if(is_a($gateway_settings, 'XMLElement')){
					$this->Form->appendChild($gateway_settings);
				}
			}

			###
			# Delegate: AddCustomPreferenceFieldsets
			# Description: Add Extension custom preferences. Use the $wrapper reference to append objects.
			$this->_Parent->ExtensionManager->notifyMembers('AddCustomPreferenceFieldsets', '/system/preferences/', array('wrapper' => &$this->Form));

			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');

			$attr = array('accesskey' => 's');
			if(!$bIsWritable) $attr['disabled'] = 'disabled';
			$div->appendChild(Widget::Input('action[save]', __('Save Changes'), 'submit', $attr));

			$this->Form->appendChild($div);
		}

		public function action() {
			##Do not proceed if the config file is read only
		    if (!is_writable(CONFIG)) redirect(URL . '/symphony/system/preferences/');

			###
			# Delegate: CustomActions
			# Description: This is where Extensions can hook on to custom actions they may need to provide.
			$this->_Parent->ExtensionManager->notifyMembers('CustomActions', '/system/preferences/');

			if (isset($_POST['action']['save'])) {
				$settings = $_POST['settings'];

				###
				# Delegate: Save
				# Description: Saving of system preferences.
				$this->_Parent->ExtensionManager->notifyMembers('Save', '/system/preferences/', array('settings' => &$settings, 'errors' => &$this->_errors));

				if (!is_array($this->_errors) || empty($this->_errors)) {

					if(is_array($settings) && !empty($settings)){
						foreach($settings as $set => $values) {
							foreach($values as $key => $val) {
								Symphony::Configuration()->set($key, $val, $set);
							}
						}
					}

					$this->_Parent->saveConfig();

					redirect(URL . '/symphony/system/preferences/success/');
				}
			}
		}
	}
