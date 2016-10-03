<?php
/**
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
 * 
 * @package Modules
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
	 * @return bool|array
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
	 * @return bool
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
	 * @api {post} ?/Api/ Login
	 * @apiName Login
	 * @apiGroup Standard Auth
	 * @apiDescription Broadcasts event Login to other modules, gets responses from them and returns AuthToken.
	 * 
	 * @apiParam {string=StandardAuth} Module Module name.
	 * @apiParam {string=Login} Method Method name.
	 * @apiParam {string} Parameters JSON.stringified object <br>
	 * {<br>
	 * &emsp; **Login** *string* Account login.<br>
	 * &emsp; **Password** *string* Account passwors.<br>
	 * &emsp; **SignMe** *bool* Indicates if it is necessary to remember user between sessions. *optional*<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'StandardAuth',
	 *	Method: 'Login',
	 *	Parameters: '{ Login: "login_value", Password: "password_value", SignMe: true }'
	 * }
	 * 
	 * @apiSuccess {string} Module Module name.
	 * @apiSuccess {string} Method Method name.
	 * @apiSuccess {mixed} Result Object in case of success, otherwise **false**.
	 * @apiSuccess {string} Result.AuthToken Auth token.
	 * @apiSuccess {int} [ErrorCode] Error code.
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'StandardAuth',
	 *	Method: 'Login',
	 *	Result: {AuthToken: 'token_value'}
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'StandardAuth',
	 *	Method: 'Login',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Broadcasts event Login to other modules, gets responses from them and returns AuthToken.
	 * 
	 * @param string $Login Account login.
	 * @param string $Password Account passwors.
	 * @param bool $SignMe Indicates if it is necessary to remember user between sessions.
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
		
		throw new \System\Exceptions\AuroraApiException(\System\Notifications::AuthError);
	}
	
	/**
	 * @api {post} ?/Api/ CreateUserAccount
	 * @apiName CreateUserAccount
	 * @apiGroup Standard Auth
	 * @apiDescription Creates basic account for specified user.
	 * 
	 * @apiParam {string=StandardAuth} Module Module name.
	 * @apiParam {string=CreateUserAccount} Method Method name.
	 * @apiParam {string} AuthToken Auth token.
	 * @apiParam {string} Parameters JSON.stringified object <br>
	 * {<br>
	 * &emsp; **UserId** *int* User identificator.<br>
	 * &emsp; **Login** *string* New account login.<br>
	 * &emsp; **Password** *string* Password New account password.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'StandardAuth',
	 *	Method: 'CreateUserAccount',
	 *	AuthToken: 'token_value',
	 *	Parameters: '{ UserId: 123, Login: "login_value", Password: "password_value" }'
	 * }
	 * 
	 * @apiSuccess {string} Module Module name.
	 * @apiSuccess {string} Method Method name.
	 * @apiSuccess {bool} Result Indicates if account was created successfully.
	 * @apiSuccess {int} [ErrorCode] Error code.
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'StandardAuth',
	 *	Method: 'CreateUserAccount',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'StandardAuth',
	 *	Method: 'CreateUserAccount',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Creates basic account for specified user.
	 * 
	 * @param int $UserId User identificator.
	 * @param string $Login New account login.
	 * @param string $Password New account password.
	 * @return bool
	 */
	public function CreateUserAccount($UserId, $Login, $Password)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::Anonymous);
		
		return $this->CreateAccount(0, $UserId, $Login, $Password);
	}
	
	/**
	 * @api {post} ?/Api/ CreateAuthenticatedUserAccount
	 * @apiName CreateAuthenticatedUserAccount
	 * @apiGroup Standard Auth
	 * @apiDescription Creates basic account for authenticated user.
	 * 
	 * @apiParam {string=StandardAuth} Module Module name.
	 * @apiParam {string=CreateAuthenticatedUserAccount} Method Method name.
	 * @apiParam {string} AuthToken Auth token.
	 * @apiParam {string} Parameters JSON.stringified object <br>
	 * {<br>
	 * &emsp; **Login** *string* New account login.<br>
	 * &emsp; **Password** *string* New account password.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'StandardAuth',
	 *	Method: 'CreateAuthenticatedUserAccount',
	 *	AuthToken: 'token_value',
	 *	Parameters: '{ Login: "login_value", Password: "password_value" }'
	 * }
	 * 
	 * @apiSuccess {string} Module Module name.
	 * @apiSuccess {string} Method Method name.
	 * @apiSuccess {bool} Result Indicates if account was created successfully.
	 * @apiSuccess {int} [ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'StandardAuth',
	 *	Method: 'CreateAuthenticatedUserAccount',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'StandardAuth',
	 *	Method: 'CreateAuthenticatedUserAccount',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Creates basic account for authenticated user.
	 * 
	 * @param string $Login New account login.
	 * @param string $Password New account password.
	 * @return bool
	 */
	public function CreateAuthenticatedUserAccount($Login, $Password)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$iUserId = \CApi::getAuthenticatedUserId();
		return $this->CreateAccount(0, $iUserId, $Login, $Password);
	}
	
	/**
	 * @api {post} ?/Api/ UpdateAccount
	 * @apiName UpdateAccount
	 * @apiGroup Standard Auth
	 * @apiDescription Updates existing basic account.
	 * 
	 * @apiParam {string=StandardAuth} Module Module name.
	 * @apiParam {string=UpdateAccount} Method Method name.
	 * @apiParam {string} AuthToken Auth token.
	 * @apiParam {string} Parameters JSON.stringified object <br>
	 * {<br>
	 * &emsp; **AccountId** *int* AccountId Identificator of account to update.<br>
	 * &emsp; **Login** *string* New value of account login. *optional*<br>
	 * &emsp; **Password** *string* New value of account password. *optional*<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'StandardAuth',
	 *	Method: 'UpdateAccount',
	 *	AuthToken: 'token_value',
	 *	Parameters: '{ AccountId: 123, Login: "login_value", Password: "password_value" }'
	 * }
	 * 
	 * @apiSuccess {string} Module Module name.
	 * @apiSuccess {string} Method Method name.
	 * @apiSuccess {mixed} Result Object in case of success, otherwise **false**.
	 * @apiSuccess {string} Result.iObjectId Identificator of updated account.
	 * @apiSuccess {int} [ErrorCode] Error code.
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'StandardAuth',
	 *	Method: 'UpdateAccount',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'StandardAuth',
	 *	Method: 'UpdateAccount',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Updates existing basic account.
	 * 
	 * @param int $AccountId Identificator of account to update.
	 * @param string $Login New value of account login.
	 * @param string $Password New value of account password.
	 * @return array|bool
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
	 * @api {post} ?/Api/ DeleteAccount
	 * @apiName DeleteAccount
	 * @apiGroup Standard Auth
	 * @apiDescription Deletes basic account.
	 * 
	 * @apiParam {string=StandardAuth} Module Module name.
	 * @apiParam {string=DeleteAccount} Method Method name.
	 * @apiParam {string} AuthToken Auth token.
	 * @apiParam {string} Parameters JSON.stringified object <br>
	 * {<br>
	 * &emsp; **AccountId** *int* Identificator of account to delete.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'StandardAuth',
	 *	Method: 'DeleteAccount',
	 *	AuthToken: 'token_value',
	 *	Parameters: '{ AccountId: 123 }'
	 * }
	 * 
	 * @apiSuccess {string} Module Module name.
	 * @apiSuccess {string} Method Method name.
	 * @apiSuccess {bool} Result Indicates if account was deleted successfully.
	 * @apiSuccess {int} [ErrorCode] Error code.
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'StandardAuth',
	 *	Method: 'DeleteAccount',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'StandardAuth',
	 *	Method: 'DeleteAccount',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Deletes basic account.
	 * 
	 * @param int $AccountId Identificator of account to delete.
	 * @return bool
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
	 * @api {post} ?/Api/ GetUserAccounts
	 * @apiName GetUserAccounts
	 * @apiGroup Standard Auth
	 * @apiDescription Obtains basic account for specified user.
	 * 
	 * @apiParam {string=StandardAuth} Module Module name.
	 * @apiParam {string=GetUserAccounts} Method Method name.
	 * @apiParam {string} AuthToken Auth token.
	 * @apiParam {string} Parameters JSON.stringified object <br>
	 * {<br>
	 * &emsp; **UserId** *int* User identifier.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'StandardAuth',
	 *	Method: 'GetUserAccounts',
	 *	AuthToken: 'token_value',
	 *	Parameters: '{ UserId: 123 }'
	 * }
	 * 
	 * @apiSuccess {string} Module Module name.
	 * @apiSuccess {string} Method Method name.
	 * @apiSuccess {mixed} Result List of account objects in case of success, otherwise **false**. Account object is like {id: 234, login: 'account_login'}.
	 * @apiSuccess {int} [ErrorCode] Error code.
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'StandardAuth',
	 *	Method: 'GetUserAccounts',
	 *	Result: [{id: 234, login: 'account_login234'}, {id: 235, login: 'account_login235'}]
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'StandardAuth',
	 *	Method: 'GetUserAccounts',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Obtains basic account for specified user.
	 * 
	 * @param int $UserId User identifier.
	 * @return array|bool
	 */
	public function GetUserAccounts($UserId)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$oUser = \CApi::getAuthenticatedUser();
		if ($oUser->Role === \EUserRole::NormalUser && $oUser->iId != $UserId)
		{
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::AccessDenied);
		}
		
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
