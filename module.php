<?php
/**
 * @copyright Copyright (c) 2017, Afterlogic Corp.
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

namespace Aurora\Modules;

class StandardAuthModule extends \Aurora\System\Module\AbstractModule
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
		$this->subscribeEvent('Core::GetAccounts', array($this, 'onGetAccounts'));
	}
	
	/**
	 * Tries to log in with specified credentials via StandardAuth module. Writes to $mResult array with auth token data if logging in was successfull.
	 * @ignore
	 * @param array $aArgs Credentials for logging in.
	 * @param mixed $mResult Is passed by reference.
	 */
	public function onLogin($aArgs, &$mResult)
	{
		$oAccount = $this->oApiAccountsManager->getAccountByCredentials(
			$aArgs['Login'], 
			$aArgs['Password']
		);
		
		if ($oAccount)
		{
			$mResult = array(
				'token' => 'auth',
				'sign-me' => $aArgs['SignMe'],
				'id' => $oAccount->IdUser,
				'account' => $oAccount->EntityId
			);
		}
	}
	
	/**
	 * Creates account with specified credentials.
	 * @ignore
	 * @param array $aArgs New account credentials.
	 * @param type $mResult Is passed by reference.
	 */
	public function onRegister($aArgs, &$mResult)
	{
		$mResult = $this->CreateUserAccount(
			$aArgs['UserId'], 
			$aArgs['Login'], 
			$aArgs['Password']
		);
	}
	
	/**
	 * Checks if module has account with specified login.
	 * @ignore
	 * @param array $aArgs
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function onCheckAccountExists($aArgs)
	{
		$oAccount = \CAccount::createInstance($this->GetName());
		$oAccount->Login = $aArgs['Login'];
		if ($this->oApiAccountsManager->isExists($oAccount))
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::AccountExists);
		}
	}
	
	/**
	 * Deletes all basic accounts which are owened by the specified user.
	 * @ignore
	 * @param array $aArgs
	 * @param int $iUserId User identifier.
	 */
	public function onAfterDeleteUser($aArgs, &$iUserId)
	{
		$mResult = $this->oApiAccountsManager->getUserAccounts($iUserId);
		
		if (\is_array($mResult))
		{
			foreach($mResult as $oItem)
			{
				$this->DeleteAccount($oItem->EntityId);
			}
		}
	}
	
	/**
	 * 
	 * @param array $aArgs
	 * @param array $aResult
	 */
	public function onGetAccounts($aArgs, &$aResult)
	{
		$bWithPassword = $aArgs['WithPassword'];
		$aUserInfo = \Aurora\System\Api::getAuthenticatedUserInfo($aArgs['AuthToken']);
		if (isset($aUserInfo['userId']))
		{
			$mResult = $this->oApiAccountsManager->getUserAccounts($aUserInfo['userId'], $bWithPassword);
			if (\is_array($mResult))
			{
				foreach($mResult as $oItem)
				{
					$aItem = array(
						'Type' => $oItem->getName(),
						'Module' => $oItem->getModule(),
						'Id' => $oItem->EntityId,
						'UUID' => $oItem->UUID,
						'Login' => $oItem->Login
					);
					if ($bWithPassword)
					{
						$aItem['Password'] = $oItem->Password;
					}
					$aResult[] = $aItem;
				}
			}
		}
	}	
	/***** private functions *****/
	
	/***** public functions *****/
	/**
	 * Creates account with credentials.
	 * 
	 * @param int $iTenantId Tenant identifier.
	 * @param int $iUserId User identifier.
	 * @param string $sLogin New account login.
	 * @param string $sPassword New account password.
	 * @return bool|array
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function CreateAccount($iTenantId = 0, $iUserId = 0, $sLogin = '', $sPassword = '')
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::Anonymous);
		
		$aArgs = array(
				'Login' => $sLogin
		);
		$this->broadcastEvent(
			'CheckAccountExists', 
			$aArgs
		);
		
		$mResult = null;
		$aArgs = array(
			'TenantId' => $iTenantId,
			'UserId' => $iUserId,
			'login' => $sLogin,
			'password' => $sPassword
		);
		$this->broadcastEvent(
			'CreateAccount', 
			$aArgs,
			$mResult
		);
		
		if ($mResult instanceOf \CUser)
		{
			$oAccount = \CAccount::createInstance($this->GetName());
			
			$oAccount->IdUser = $mResult->EntityId;
			$oAccount->Login = $sLogin;
			$oAccount->Password = $sPassword;
			
			if ($this->oApiAccountsManager->isExists($oAccount))
			{
				throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::AccountExists);
			}
			
			$this->oApiAccountsManager->createAccount($oAccount);
			return $oAccount ? array(
				'EntityId' => $oAccount->EntityId
			) : false;
		}
		else
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::NonUserPassed);
		}
		
		return false;
	}
	
	/**
	 * Updates account.
	 * 
	 * @param \CAccount $oAccount
	 * @return bool
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function SaveAccount($oAccount)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::Anonymous);
		
//		$oAccount = $this->getDefaultAccountFromParam();
		
//		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		
		if ($oAccount instanceof \CAccount)
		{
			$this->oApiAccountsManager->createAccount($oAccount);
			
			return $oAccount ? array(
				'EntityId' => $oAccount->EntityId
			) : false;
		}
		else
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::UserNotAllowed);
		}
		
		return false;
	}
	/***** public functions *****/
	
	/***** public functions might be called with web API *****/
	/**
	 * @apiDefine StandardAuth Standard Auth Module
	 * This module provides API for authentication by login/password that relies on database.
	 */
	
	/**
	 * @api {post} ?/Api/ CreateUserAccount
	 * @apiName CreateUserAccount
	 * @apiGroup StandardAuth
	 * @apiDescription Creates basic account for specified user.
	 * 
	 * @apiParam {string=StandardAuth} Module Module name.
	 * @apiParam {string=CreateUserAccount} Method Method name.
	 * @apiParam {string} AuthToken Auth token.
	 * @apiParam {string} Parameters JSON.stringified object <br>
	 * {<br>
	 * &emsp; **UserId** *int* User identifier.<br>
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
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {bool} Result.Result Indicates if account was created successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code.
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
	 * @param int $UserId User identifier.
	 * @param string $Login New account login.
	 * @param string $Password New account password.
	 * @return bool
	 */
	public function CreateUserAccount($UserId, $Login, $Password)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::Anonymous);
		
		return $this->CreateAccount(0, $UserId, $Login, $Password);
	}
	
	/**
	 * @api {post} ?/Api/ CreateAuthenticatedUserAccount
	 * @apiName CreateAuthenticatedUserAccount
	 * @apiGroup StandardAuth
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
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {bool} Result.Result Indicates if account was created successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
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
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$iUserId = \Aurora\System\Api::getAuthenticatedUserId();
		return $this->CreateAccount(0, $iUserId, $Login, $Password);
	}
	
	/**
	 * @api {post} ?/Api/ UpdateAccount
	 * @apiName UpdateAccount
	 * @apiGroup StandardAuth
	 * @apiDescription Updates existing basic account.
	 * 
	 * @apiParam {string=StandardAuth} Module Module name.
	 * @apiParam {string=UpdateAccount} Method Method name.
	 * @apiParam {string} AuthToken Auth token.
	 * @apiParam {string} Parameters JSON.stringified object <br>
	 * {<br>
	 * &emsp; **AccountId** *int* AccountId Identifier of account to update.<br>
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
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result Object in case of success, otherwise **false**.
	 * @apiSuccess {string} Result.Result.EntityId Identifier of updated account.
	 * @apiSuccess {int} [Result.ErrorCode] Error code.
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
	 * @param int $AccountId Identifier of account to update.
	 * @param string $Login New value of account login.
	 * @param string $Password New value of account password.
	 * @return array|bool
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function UpdateAccount($AccountId = 0, $Login = '', $Password = '')
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
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
				'EntityId' => $oAccount->EntityId
			) : false;
		}
		else
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::UserNotAllowed);
		}
		
		return false;
	}
	
	/**
	 * @api {post} ?/Api/ DeleteAccount
	 * @apiName DeleteAccount
	 * @apiGroup StandardAuth
	 * @apiDescription Deletes basic account.
	 * 
	 * @apiParam {string=StandardAuth} Module Module name.
	 * @apiParam {string=DeleteAccount} Method Method name.
	 * @apiParam {string} AuthToken Auth token.
	 * @apiParam {string} Parameters JSON.stringified object <br>
	 * {<br>
	 * &emsp; **AccountId** *int* Identifier of account to delete.<br>
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
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {bool} Result.Result Indicates if account was deleted successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code.
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
	 * @param int $AccountId Identifier of account to delete.
	 * @return bool
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function DeleteAccount($AccountId = 0)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
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
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::UserNotAllowed);
		}
	}
	
	/**
	 * @api {post} ?/Api/ GetUserAccounts
	 * @apiName GetUserAccounts
	 * @apiGroup StandardAuth
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
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result List of account objects in case of success, otherwise **false**. Account object is like {id: 234, login: 'account_login'}.
	 * @apiSuccess {int} [Result.ErrorCode] Error code.
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
		\Aurora\System\Api::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$oUser = \Aurora\System\Api::getAuthenticatedUser();
		if ($oUser->Role === \EUserRole::NormalUser && $oUser->EntityId != $UserId)
		{
			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::AccessDenied);
		}
		
		$aAccounts = array();
		$mResult = $this->oApiAccountsManager->getUserAccounts($UserId);
		if (\is_array($mResult))
		{
			foreach($mResult as $oItem)
			{
				$aAccounts[] = array(
					'id' => $oItem->EntityId,
					'login' => $oItem->Login
				);
			}
		}
		return $aAccounts;
	}
	/***** public functions might be called with web API *****/
}
