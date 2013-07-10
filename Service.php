<?php

class Box_Mod_Sms_Service
{
    /**
     * Method to install module. In most cases you will provide your own
     * database table or tables to store extension related data.
     * 
     * If your extension is not very complicated then extension_meta 
     * database table might be enough.
     *
     * @return bool
     * @throws Box_Exception
     */
    public function install()
    {
        // execute sql script if needed
        $pdo = Box_Db::getPdo();
        $query="SELECT NOW()";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
		
        //throw new Box_Exception("Throw exception to terminate module installation process with a message", array(), 123);
        return true;
    }
    
    /**
     * Method to uninstall module.
     * 
     * @return bool
     * @throws Box_Exception
     */
    public function uninstall()
    {
        //throw new Box_Exception("Throw exception to terminate module uninstallation process with a message", array(), 124);
        return true;
    }
    
    /**
     * Method to update module. When you release new version to 
     * extensions.boxbilling.com then this method will be called
     * after new files are placed.
     * 
     * @param array $manifest - information about new module version
     * @return bool
     * @throws Box_Exception
     */
    public function update($manifest)
    {
        //throw new Box_Exception("Throw exception to terminate module update process with a message", array(), 125);
        return true;
    }
 
 
    public function toApiArray($row, $role = 'guest', $deep = true)
    {
        return $row;
    }
    
 
    public static function onEventClientLoginFailed(Box_Event $event)
    {
        //@note almost in all casesyou will need Admin API
        $api = $event->getApiAdmin();
        
        //sometimes you may need guest API
        //$api_guest = $event->getApiGuest();
        $params = $event->getParameters();
        
        //@note To debug parameters by throw an exception
        //throw new Exception(print_r($params, 1));
        
        // Use RedBean ORM in any place of BoxBilling where API call is not enough
        // First we neeed to find if we already have a counter for this IP
        // We will use extension_meta table to store this data.
        $values = array(
            'ext'        =>  'sms',
            'rel_type'   =>  'ip',
            'rel_id'     =>  $params['ip'],
            'meta_key'   =>  'counter',
        );
        $meta = R::findOne('extension_meta', 'extension = :ext AND rel_type = :rel_type AND rel_id = :rel_id AND meta_key = :meta_key', $values);
        if(!$meta) {
            $meta = R::dispense('extension_meta');
            //$count->client_id = null; // client id is not known in this situation
            $meta->extension = 'mod_autoticket';
            $meta->rel_type = 'ip';
            $meta->rel_id = $params['ip'];
            $meta->meta_key = 'counter';
            $meta->created_at = date('c');
        }
        $meta->meta_value = $meta->meta_value + 1;
        $meta->updated_at = date('c');
        R::store($meta);
        
        // Now we can perform task depending on how many times wrong details were entered
        
        // We can log event if it repeats for 2 time
        if($meta->meta_value > 2) {
            $api->activity_log(array('m'=>'Client failed to enter correct login details '.$meta->meta_value.' time(s)'));
        }
        
        // if client gets funky, we block him
        if($meta->meta_value > 30) {
            throw new Exception('You have failed to login too many times. Contact support.');
        }
    }
    
 
    public static function onAfterClientCalledExampleModule(Box_Event $event)
    {
        //error_log('Called event from example module');
        
        $api = $event->getApiAdmin();
        $params = $event->getParameters();
        
        $meta = R::dispense('extension_meta');
        $meta->extension = 'mod_autoticket';
        $meta->meta_key = 'event_params';
        $meta->meta_value = json_encode($params);
        $meta->created_at = date('c');
        $meta->updated_at = date('c');
        R::store($meta);
    }

    public static function onBeforeGuestPublicTicketOpen(Box_Event $event)
    {
        $data = $event->getParameters();
        $data['status'] = 'closed';
        $data['subject'] = 'Altered subject';
        $data['message'] = 'Altered text';
        $event->setReturnValue($data);
    }


    public static function onAfterClientOrderCreate(Box_Event $event)
    {
        $api    = $event->getApiAdmin();
        $params = $event->getParameters();
        
        $email = array();
        $email['to_client'] = $params['client_id'];
        $email['code']      = 'mod_example_email'; //@see bb-modules/mod_example/html_email/mod_example_email.phtml
        
        // these parameters are available in email template
        $email['order']     = $api->order_get(array('id'=>$params['id']));
        $email['other']     = 'any other value';
        
        $api->email_template_send($email);
    }
	
    public static function onAfterAdminCronRun(Box_Event $event) {
	
				$api    = $event->getApiAdmin();
	
								$pdo = Box_Db::getPdo();
								$query="SELECT `id`, `support_helpdesk_id`, `client_id`, `priority`, `subject`, `status`, `rel_type`, `rel_id`, `rel_task`, `rel_new_value`, `rel_status`, `created_at`, `updated_at` FROM `support_ticket` WHERE `status` = 'open';";
								$stmt = $pdo->prepare($query);
								$stmt->execute();
								$licze = $stmt->fetchAll();
								$ile = count($licze);
				
				 $params = array(
				 "ext" => "mod_sms",
				 );	
					
				 $result = $api->extension_config_get($params);
								
				$par = array(
				"nowych_wiad" => $ile,
				);				
				
				if($ile == 0) { } else {
				Box_Mod_Sms_Api_Admin::sendsms($result+$par);
				}
		
	}
}