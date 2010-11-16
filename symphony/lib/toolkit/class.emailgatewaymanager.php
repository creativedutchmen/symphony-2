<?php


    Class EmailGatewayManager extends Manager{
		
		function setDefaultGateway($name){
			if($this->__find($name)){
				Symphony::Configuration()->set('default_gateway', $name, 'Email');
				$this->_Parent->saveConfig();
			}
			else{
				throw new EmailGatewayException('This gateway can not be found. Can not save as default.');
			}
		}
		
		function getDefaultGateway(){
			$gateway = Symphony::Configuration()->get('default_gateway', 'Email');
			if($gateway){
				return $gateway;
			}
			else{
				throw new EmailGatewayManagerException('The default gateway has not been set.');
			}
		}
	    
	    function __find($name){
		 
		    if(is_file(EMAILGATEWAYS . "/email.$name.php")) return EMAILGATEWAYS;
			else{	  
				    
				$extensionManager = new ExtensionManager($this->_Parent);
				$extensions = $extensionManager->listInstalledHandles();
				
				if(is_array($extensions) && !empty($extensions)){
					foreach($extensions as $e){
						if(is_file(EXTENSIONS . "/$e/email-gateways/email.$name.php")) return EXTENSIONS . "/$e/email-gateways";	
					}	
				}		    
	    	}
	    		    
		    return false;
	    }
	            
        function __getClassName($name){
	        return $name . 'Gateway';
        }
        
        function __getClassPath($name){
	        return $this->__find($name);
        }
        
        function __getDriverPath($name){	        
	        return $this->__getClassPath($name) . "/email.$name.php";
        }          

		function __getHandleFromFilename($filename){
			return preg_replace(array('/^email./i', '/.php$/i'), '', $filename);
		}
        
        function listAll(){
	        
			$result = array();
			$people = array();
			
	        $structure = General::listStructure(EMAILGATEWAYS, '/email.[\\w-]+.php/', false, 'ASC', EMAILGATEWAYS);
	        
	        if(is_array($structure['filelist']) && !empty($structure['filelist'])){		        
	        	foreach($structure['filelist'] as $f){
		        	$f = str_replace(array('email.', '.php'), '', $f);					        	
					$result[$f] = $this->about($f);
				}
			}
			
			$extensionManager = new ExtensionManager($this->_Parent);
			$extensions = $extensionManager->listInstalledHandles();
			
			if(is_array($extensions) && !empty($extensions)){
				foreach($extensions as $e){										
					
					if(!is_dir(EXTENSIONS . "/$e/email-gateways")) continue;
					
					$tmp = General::listStructure(EXTENSIONS . "/$e/email-gateways", '/email.[\\w-]+.php/', false, 'ASC', EXTENSIONS . "/$e/email-gateways");
						
			        if(is_array($tmp['filelist']) && !empty($tmp['filelist'])){
			        	foreach($tmp['filelist'] as $f){
							$f = preg_replace(array('/^email./i', '/.php$/i'), '', $f);
							$result[$f] = $this->about($f);
						}
					}
				}	
			}
			
			ksort($result);
			return $result;	        
        }

        function &create($name){
	        
	        $classname = $this->__getClassName($name);	        
	        $path = $this->__getDriverPath($name);

	        if(!is_file($path)){
		        trigger_error(__('Could not find Email Gateway <code>%s</code>. If the Email Gateway was provided by an Extensions, ensure that it is installed, and enabled.', array($name)), E_USER_ERROR);
		        return false;
	        }
	        
			if(!@class_exists($classname))									
				require_once($path);

			return new $classname($this->_Parent);	
	        
        }       
        
    }
    
