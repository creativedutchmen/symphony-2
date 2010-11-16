<?php

	// Email factory class.
	// Returns the right gateway class when initialised
	
	class Email{
	
		// Will use the gateway set in the preferences when no gateway is set here.
		// Setting this to a specific gateway should only be used if special features of that gateway are used.
		function __construct($gateway){
		}
		
	}