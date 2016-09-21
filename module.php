<?php
/*
 * @copyright Copyright (c) 2016, Afterlogic Corp.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 */

class StandardAuthModule extends AApiModule
{
	public $oApiAccountsManager = null;
	
	/***** private functions *****/
	/**
	 * Initializes module.
	 * 
	 * @ignore
	 */
	public function init()
	{
		$this->incClass('account');
		
		$this->oApiAccountsManager = $this->GetManager('accounts');
		
		$this->subscribeEvent('Login', array($this, 'onLogin'));
		$this->subscribeEvent('Register', array($this, 'onRegister'));
		$this->subscribeEvent('CheckAccountExists', array($this, 'onCheckAccountExists'));
		$this->subscribeEvent('Core::AfterDeleteUser', array($this, 'onAfterDeleteUser'));
	}
	
	/**
	 * Tries to log in with specified credentials via StandardAuth module. Writes to $mResult array with auth token data if logging in was successfull.
	 * 
	 * @ignore
	 * @param array $aParams Credentials for logging in.
	 * @param mixed $mResult Is passed by reference.
	 */
	public function onLogin($aParams, &$mResult)
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
	 * Creates account with specified credentials.
	 * 
	 * @ignore
	 * @param array $aParams New account credentials.
	 * @param type $mResult Is passed by reference.
	 */
	public function onRegister($aParams, &$mResult)
	{
		$sLogin = $aParams['Login'];
		$sPassword = $aParams['Password'];
		$iUserId = $aParams['UserId'];
		$mResult = $this->CreateUserAccount($iUserId, $sLogin, $sPassword);
	}
	
	/**
	 * Checks if module has account with specified login.
	 * 
	 * @ignore
	 * @param string $sLogin Login for checking.
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function onCheckAccountExists($sLogin)
	{
		$oAccount = \CAccount::createInstance();
		$oAccount->Login = $sLogin;
		if ($this->oApiAccountsManager->isExists($oAccount))
		{
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::AccountExists);
		}
	}
	
	/**
	 * Deletes all basic accounts which are owened by the specified user.
	 * 
	 * @ignore
	 * @param int $iUserId User identificator.
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
	/***** private functions *****/
	
	/***** public functions *****/
	/**
	 * Creates account with credentials.
	 * 
	 * @param int $iTenantId Tenant identificator.
	 * @param int $iUserId User identificator.
	 * @param string $sLogin New account login.
	 * @param string $sPassword New account password.
	 * @return boolean|array
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function CreateAccount($iTenantId = 0, $iUserId = 0, $sLogin = '', $sPassword = '')
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::Anonymous);
		
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
				throw new \System\Exceptions\AuroraApiException(\System\Notifications::AccountExists);
			}
			
			$this->oApiAccountsManager->createAccount($oAccount);
			return $oAccount ? array(
				'iObjectId' => $oAccount->iId
			) : false;
		}
		else
		{
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::NonUserPassed);
		}
		
		return false;
	}
	
	/**
	 * Updates account.
	 * 
	 * @param \CAccount $oAccount
	 * @return boolean
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function SaveAccount($oAccount)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::Anonymous);
		
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
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::UserNotAllowed);
		}
		
		return false;
	}
	/***** public functions *****/
	
	/***** public functions might be called with web API *****/
	/**
	 * Obtaines list of module settings for authenticated user.
	 * 
	 * @return array
	 */
	public function GetAppData()
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::Anonymous);
		
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
	
	/**
	 * Broadcasts event Login to other modules, gets responses from them and returns AuthToken.
	 * 
	 * @param string $Login Account login.
	 * @param string $Password Account passwors.
	 * @param boolean $SignMe Indicates if it is necessary to remember user between sessions.
	 * @return array
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function Login($Login, $Password, $SignMe = 0)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::Anonymous);
		
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
		throw new \System\Exceptions\AuroraApiException(\System\Notifications::AuthError);
	}
	
	/**
	 * Creates basic account for specified user.
	 * 
	 * @param int $UserId User identificator.
	 * @param string $Login New account login.
	 * @param string $Password New account password.
	 * @return boolean
	 */
	public function CreateUserAccount($UserId, $Login, $Password)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::Anonymous);
		
		return $this->CreateAccount(0, $UserId, $Login, $Password);
	}
	
	/**
	 * Creates basic account for authenticated user.
	 * 
	 * @param string $Login New account login.
	 * @param string $Password New account password.
	 * @return boolean
	 */
	public function CreateAuthenticatedUserAccount($Login, $Password)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$iUserId = \CApi::getAuthenticatedUserId();
		return $this->CreateAccount(0, $iUserId, $Login, $Password);
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
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function UpdateAccount($AccountId = 0, $Login = '', $Password = '')
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
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
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::UserNotAllowed);
		}
		
		return false;
	}
	
	/**
	 * Deletes basic account.
	 * 
	 * @param int $AccountId
	 * @return boolean
	 * 
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function DeleteAccount($AccountId = 0)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
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
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::UserNotAllowed);
		}
	}
	
	/**
	 * Obtains basic account for specified user.
	 * 
	 * @param int $UserId User identifier.
	 * 
	 * @return array|boolean
	 */
	public function GetUserAccounts($UserId)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
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
	/***** public functions might be called with web API *****/
}
