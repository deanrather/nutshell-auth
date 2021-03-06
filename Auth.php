<?php
namespace application\plugin\auth
{
	use nutshell\Nutshell;
	use nutshell\behaviour\Singleton;
	use nutshell\core\plugin\Plugin;
	use application\plugin\auth\AuthException;
	use application\plugin\mvcQuery\MvcQuery;
	use application\plugin\appplugin\AppPlugin;
	
	/**
	 * @author Dean Rather
	 */
	class Auth extends AppPlugin implements Singleton
	{
		private $additionalPartSQL = '';
		private $debug = array();
		
		public function getDebug()
		{
			return $this->debug;
		}
		
		public function getAdditionalPartSQL()
		{
		    return $this->additionalPartSQL;
		}
		
		public function setAdditionalPartSQL($additionalPartSQL)
		{
		    $this->additionalPartSQL = $additionalPartSQL;
		    return $this;
		}
		
		public function init()
		{
			require_once(__DIR__._DS_.'AuthException.php');
		}
		
		public function generateSalt()
		{
			mt_srand(microtime(true)*100000 + memory_get_usage(true));
			return md5(uniqid(mt_rand(), true));
		}
		
		public function saltPassword($salt, $password)
		{
			return sha1($salt.$password);
		}
		
		public function login($username, $providedPassword)
		{
			// Get the model & table details
			$config = Nutshell::getInstance()->config;
			$modelName			= $config->plugin->Auth->model;
			$usernameColumnName	= $config->plugin->Auth->usernameColumn;
			$passwordColumnName	= $config->plugin->Auth->passwordColumn;
			$saltColumnName		= $config->plugin->Auth->saltColumn;
			$model = $this->plugin->MvcQuery->getModel($modelName);
			
			// Get the user row from the table
			$result = $model->read(array($usernameColumnName => $username), array(), $this->additionalPartSQL);
			
			// No user by that name
			if(!$result)
			{
				$this->debug = array('no user by that name', $usernameColumnName,  $username,  $this->additionalPartSQL);
				return false;
			}
			
			// does that user's salted password match this salted password?
			$user					= $result[0];
			$salt					= $user[$saltColumnName];
			$realPasswordSalted		= $user[$passwordColumnName];
			$providedPasswordSalted	= $this->saltPassword($salt, $providedPassword);
			
			if($realPasswordSalted != $providedPasswordSalted)
			{
				$this->debug = array('password doesnt match', $realPasswordSalted, $providedPasswordSalted);
				return false;
			}
			
			// Set the 'user' session variable
			$this->plugin->Session->userID = $user['id'];
			return true;
		}
		
		public function isLoggedIn()
		{
			return($this->getUserID() == true);
		}
		
		public function getUserID()
		{
			return $this->plugin->Session->userID;
		}
		
		public function logout()
		{
			$this->plugin->Session->userID = null;
			return true;
		}
	}
}
