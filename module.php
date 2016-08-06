<?php

class BasicAuthModule extends AApiModule
{
	public $oApiAccountsManager = null;
	
	/**
	 * @return array
	 */
	public function init()
	{
		$this->incClass('account');
		
		$this->oApiAccountsManager = $this->GetManager('accounts');
		$this->setNonAuthorizedMethods(array('Login'));
		
		$this->subscribeEvent('Login', array($this, 'checkAuth'));
		$this->subscribeEvent('CheckAccountExists', array($this, 'checkAccountExists'));
		$this->subscribeEvent('Core::AfterDeleteUser', array($this, 'onAfterDeleteUser'));
	}
	
	/**
	 * Returns login of authenticated user basic account.
	 * 
	 * @return string|false
	 */
    public function GetUserAccountLogin()
	{
		$iUserId = \CApi::getAuthenticatedUserId();
		$aAccounts = $this->oApiAccountsManager->getUserAccounts($iUserId);
		if (is_array($aAccounts) && count($aAccounts) > 0)
		{
			return $aAccounts[0]->Login;
		}
		
		return false;
	}
	
	/**
	 * Obtains settings of the Simple Chat Module.
	 * 
	 * @return array
	 */
	public function GetAppData()
	{
		return array(
			'AllowChangeLanguage' => false, //AppData.App.AllowLanguageOnLogin
			'AllowRegistration' => false, //AppData.App.AllowRegistration
			'AllowResetPassword' => false, //AppData.App.AllowPasswordReset
			'CustomLoginUrl' => '', //AppData.App.CustomLoginUrl
			'CustomLogoUrl' => '', //AppData.LoginStyleImage
			'DemoLogin' => '', //AppData.App.DemoWebMailLogin
			'DemoPassword' => '', //AppData.App.DemoWebMailPassword
			'InfoText' => '', //AppData.App.LoginDescription
			'LoginAtDomain' => '', //AppData.App.LoginAtDomainValue
			'LoginFormType' => 0, //AppData.App.LoginFormType 0 - email, 3 - login, 4 - both
			'LoginSignMeType' => 0, //AppData.App.LoginSignMeType 0 - off, 1 - on, 2 - don't use
			'RegistrationDomains' => array(), //AppData.App.RegistrationDomains
			'RegistrationQuestions' => array(), //AppData.App.RegistrationQuestions
			'UseFlagsLanguagesView' => false, //AppData.App.FlagsLangSelect
		);
	}
	
//	public function IsAuth()
//	{
//		$mResult = false;
//		$oAccount = $this->getDefaultAccountFromParam(false);
//		if ($oAccount) {
//			
//			$sClientTimeZone = trim($this->getParamValue('ClientTimeZone', ''));
//			if ('' !== $sClientTimeZone) {
//				
//				$oAccount->User->ClientTimeZone = $sClientTimeZone;
//				$oApiUsers = \CApi::GetCoreManager('users');
//				if ($oApiUsers) {
//					
//					$oApiUsers->updateAccount($oAccount);
//				}
//			}
//
//			$mResult = array();
//			$mResult['Extensions'] = array();
//
//			// extensions
//			if ($oAccount->isExtensionEnabled(\CAccount::IgnoreSubscribeStatus) &&
//				!$oAccount->isExtensionEnabled(\CAccount::DisableManageSubscribe)) {
//				
//				$oAccount->enableExtension(\CAccount::DisableManageSubscribe);
//			}
//
//			$aExtensions = $oAccount->getExtensionList();
//			foreach ($aExtensions as $sExtensionName) {
//				
//				if ($oAccount->isExtensionEnabled($sExtensionName)) {
//					
//					$mResult['Extensions'][] = $sExtensionName;
//				}
//			}
//		}
//
//		return $mResult;
//	}	
	
	/**
	 * @return array
	 */
	/*public function Login()
	{
		setcookie('aft-cache-ctrl', '', time() - 3600);
		$sEmail = trim((string) $this->getParamValue('Email', ''));
		$sIncLogin = (string) $this->getParamValue('IncLogin', '');
		$sIncPassword = (string) $this->getParamValue('IncPassword', '');
		$sLanguage = (string) $this->getParamValue('Language', '');

		$bSignMe = '1' === (string) $this->getParamValue('SignMe', '0');

		$oApiIntegrator = \CApi::GetCoreManager('integrator');
		try
		{
			\CApi::Plugin()->RunHook(
					'webmail-login-custom-data', 
					array($this->getParamValue('CustomRequestData', null))
			);
		}
		catch (\Exception $oException)
		{
			\CApi::LogEvent(\EEvents::LoginFailed, $sEmail);
			throw $oException;
		}

		$sAtDomain = trim(\CApi::GetSettingsConf('WebMail/LoginAtDomainValue'));
		if ((\ELoginFormType::Email === (int) \CApi::GetSettingsConf('WebMail/LoginFormType') || 
				\ELoginFormType::Both === (int) \CApi::GetSettingsConf('WebMail/LoginFormType')) && 
				0 === strlen($sAtDomain) && 0 < strlen($sEmail) && !\MailSo\Base\Validator::EmailString($sEmail))
		{
			throw new \System\Exceptions\ClientException(\System\Notifications::AuthError);
		}

		if (\ELoginFormType::Login === (int) \CApi::GetSettingsConf('WebMail/LoginFormType') && 0 < strlen($sAtDomain))
		{
			$sEmail = \api_Utils::GetAccountNameFromEmail($sIncLogin).'@'.$sAtDomain;
			$sIncLogin = $sEmail;
		}

		if (0 === strlen($sIncPassword) || 0 === strlen($sEmail.$sIncLogin)) {
			
			throw new \System\Exceptions\ClientException(\System\Notifications::InvalidInputParameter);
		}

		try
		{
			if (0 === strlen($sLanguage)) {
				
				$sLanguage = $oApiIntegrator->getLoginLanguage();
			}

			$oAccount = $oApiIntegrator->loginToAccount(
					$sEmail, 
					$sIncPassword, 
					$sIncLogin, 
					$sLanguage
			);
		}
		catch (\Exception $oException)
		{
			$iErrorCode = \System\Notifications::UnknownError;
			if ($oException instanceof \CApiManagerException)
			{
				switch ($oException->getCode())
				{
					case \Errs::WebMailManager_AccountDisabled:
					case \Errs::WebMailManager_AccountWebmailDisabled:
						$iErrorCode = \System\Notifications::AuthError;
						break;
					case \Errs::UserManager_AccountAuthenticationFailed:
					case \Errs::WebMailManager_AccountAuthentication:
					case \Errs::WebMailManager_NewUserRegistrationDisabled:
					case \Errs::WebMailManager_AccountCreateOnLogin:
					case \Errs::Mail_AccountAuthentication:
					case \Errs::Mail_AccountLoginFailed:
						$iErrorCode = \System\Notifications::AuthError;
						break;
					case \Errs::UserManager_AccountConnectToMailServerFailed:
					case \Errs::WebMailManager_AccountConnectToMailServerFailed:
					case \Errs::Mail_AccountConnectToMailServerFailed:
						$iErrorCode = \System\Notifications::MailServerError;
						break;
					case \Errs::UserManager_LicenseKeyInvalid:
					case \Errs::UserManager_AccountCreateUserLimitReached:
					case \Errs::UserManager_LicenseKeyIsOutdated:
					case \Errs::TenantsManager_AccountCreateUserLimitReached:
						$iErrorCode = \System\Notifications::LicenseProblem;
						break;
					case \Errs::Db_ExceptionError:
						$iErrorCode = \System\Notifications::DataBaseError;
						break;
				}
			}

			\CApi::LogEvent(\EEvents::LoginFailed, $sEmail);
			throw new \System\Exceptions\ClientException($iErrorCode, $oException,
				$oException instanceof \CApiBaseException ? $oException->GetPreviousMessage() :
				($oException ? $oException->getMessage() : ''));
		}

		if ($oAccount instanceof \CAccount)
		{
			$sAuthToken = '';
			$bSetAccountAsLoggedIn = true;
			\CApi::Plugin()->RunHook(
					'api-integrator-set-account-as-logged-in', 
					array(&$bSetAccountAsLoggedIn)
			);

			if ($bSetAccountAsLoggedIn) {
				
				\CApi::LogEvent(\EEvents::LoginSuccess, $oAccount);
				$sAuthToken = $oApiIntegrator->setAccountAsLoggedIn($oAccount, $bSignMe);
			}
			
			return array(
				'AuthToken' => $sAuthToken
			);
		}

		\CApi::LogEvent(\EEvents::LoginFailed, $oAccount);
		throw new \System\Exceptions\ClientException(\System\Notifications::AuthError);
	}*/
	
	public function Login($Login, $Password, $SignMe = 0)
	{
		$mResult = false;

		$this->broadcastEvent('Login', array(
			array (
				'Login' => $Login,
				'Password' => $Password,
				'SignMe' => $SignMe
			),
			&$mResult
		));

		if (is_array($mResult))
		{
			$mResult['time'] = $SignMe ? time() + 60 * 60 * 24 * 30 : 0;
			$sAuthToken = \CApi::UserSession()->Set($mResult);
			
			return array(
				'AuthToken' => $sAuthToken
			);
		}
		
//		\CApi::LogEvent(\EEvents::LoginFailed, $oAccount);
		throw new \System\Exceptions\ClientException(\System\Notifications::AuthError);
	}
	
	public function checkAuth($aParams, &$mResult)
	{
		$sLogin = $aParams['Login'];
		$sPassword = $aParams['Password'];
		$bSignMe = $aParams['SignMe'];
		
		$oAccount = $this->oApiAccountsManager->getAccountByCredentials($sLogin, $sPassword);

		if ($oAccount)
		{
			$mResult = array(
				'token' => 'auth',
				'sign-me' => $bSignMe,
				'id' => $oAccount->IdUser
			);
		}
	}
	
	/**
	 * Creates basic account for specified user. Also uses from web API.
	 * 
	 * @param int $UserId User identificator.
	 * @param string $Login New account login.
	 * @param string $Password New account password.
	 * 
	 * @return boolean
	 */
	public function CreateUserAccount($UserId, $Login, $Password)
	{
		return $this->CreateAccount(0, $UserId, $Login, $Password);
	}
	
	/**
	 * Creates basic account for authenticated user. Also uses from web API.
	 * 
	 * @param string $Login New account login.
	 * @param string $Password New account password.
	 * 
	 * @return boolean
	 */
	public function CreateAuthenticatedUserAccount($Login, $Password)
	{
		$iUserId = \CApi::getAuthenticatedUserId();
		return $this->CreateAccount(0, $iUserId, $Login, $Password);
	}
	
	/**
	 * Checks if module has account with specified login.
	 * 
	 * @param string $sLogin Login for checking.
	 * 
	 * @throws \System\Exceptions\ClientException
	 */
	public function checkAccountExists($sLogin)
	{
		$oAccount = \CAccount::createInstance();
		$oAccount->Login = $sLogin;
		if ($this->oApiAccountsManager->isExists($oAccount))
		{
			throw new \System\Exceptions\ClientException(\System\Notifications::AccountExists);
		}
	}
			
	/**
	 * 
	 * @return boolean
	 */
	public function CreateAccount($iTenantId = 0, $iUserId = 0, $sLogin = '', $sPassword = '')
	{
		$this->broadcastEvent('CheckAccountExists', array($sLogin));
		
		$oEventResult = null;
		$this->broadcastEvent('CreateAccount', array(
			array(
				'TenantId' => $iTenantId,
				'UserId' => $iUserId,
				'login' => $sLogin,
				'password' => $sPassword
			),
			'result' => &$oEventResult
		));
		
		//	if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		if ($oEventResult instanceOf \CUser)
		{
			$oAccount = \CAccount::createInstance();
			
			$oAccount->IdUser = $oEventResult->iId;
			$oAccount->Login = $sLogin;
			$oAccount->Password = $sPassword;

			if ($this->oApiAccountsManager->isExists($oAccount))
			{
				throw new \System\Exceptions\ClientException(\System\Notifications::AccountExists);
			}
			
			$this->oApiAccountsManager->createAccount($oAccount);
			return $oAccount ? array(
				'iObjectId' => $oAccount->iId
			) : false;
		}
		else
		{
			throw new \System\Exceptions\ClientException(\System\Notifications::NonUserPassed);
		}

		return false;
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public function SaveAccount($oAccount)
	{
//		$oAccount = $this->getDefaultAccountFromParam();
		
//		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		
		if ($oAccount instanceof \CAccount)
		{
			$this->oApiAccountsManager->createAccount($oAccount);
			
			return $oAccount ? array(
				'iObjectId' => $oAccount->iId
			) : false;
		}
		else
		{
			throw new \System\Exceptions\ClientException(\System\Notifications::UserNotAllowed);
		}

		return false;
	}
	
	/**
	 * Updates existing basic account. Also uses from web API.
	 * 
	 * @param int $AccountId Identificator of account to update.
	 * @param string $Login New value of account login.
	 * @param string $Password New value of account password.
	 * 
	 * @return array|boolean
	 * 
	 * @throws \System\Exceptions\ClientException
	 */
	public function UpdateAccount($AccountId = 0, $Login = '', $Password = '')
	{
		if ($AccountId > 0)
		{
			$oAccount = $this->oApiAccountsManager->getAccountById($AccountId);
			
			if ($oAccount)
			{
				if ($Login)
				{
					$oAccount->Login = $Login;
				}
				if ($Password)
				{
					$oAccount->Password = $Password;
				}
				$this->oApiAccountsManager->updateAccount($oAccount);
			}
			
			return $oAccount ? array(
				'iObjectId' => $oAccount->iId
			) : false;
		}
		else
		{
			throw new \System\Exceptions\ClientException(\System\Notifications::UserNotAllowed);
		}

		return false;
	}

	/**
	 * Deletes basic account. Also uses via web API.
	 * 
	 * @param int $AccountId
	 * @return boolean
	 * 
	 * @throws \System\Exceptions\ClientException
	 */
	public function DeleteAccount($AccountId = 0)
	{
		$bResult = false;

		if ($AccountId > 0)
		{
			$oAccount = $this->oApiAccountsManager->getAccountById($AccountId);
			
			if ($oAccount)
			{
				$bResult = $this->oApiAccountsManager->deleteAccount($oAccount);
			}
			
			return $bResult;
		}
		else
		{
			throw new \System\Exceptions\ClientException(\System\Notifications::UserNotAllowed);
		}
	}
	
	/**
	 * Obtains basic account for specified user. Also uses via web API.
	 * 
	 * @param int $UserId User identifier.
	 * 
	 * @return array|boolean
	 */
	public function GetUserAccounts($UserId)
	{
		$aAccounts = array();
		$mResult = $this->oApiAccountsManager->getUserAccounts($UserId);
		if (is_array($mResult))
		{
			foreach($mResult as $oItem)
			{
				$aAccounts[] = array(
					'id' => $oItem->iId,
					'login' => $oItem->Login
				);
			}
		}
		return $aAccounts;
	}
	
	/**
	 * Deletes all basic accounts which are owened by the specified user.
	 * 
	 * @param int $iUserId User Identificator.
	 */
	public function onAfterDeleteUser($iUserId)
	{
		$mResult = $this->oApiAccountsManager->getUserAccounts($iUserId);
		
		if (is_array($mResult))
		{
			foreach($mResult as $oItem)
			{
				$this->DeleteAccount($oItem->iId);
			}
		}
	}
}
