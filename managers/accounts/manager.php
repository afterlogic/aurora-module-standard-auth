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

/**
 * CApiAccountsManager class summary
 * 
 * @package Accounts
 */
class CApiStandardAuthAccountsManager extends \Aurora\System\AbstractManager
{
	/**
	 * @var CApiEavManager
	 */
	public $oEavManager = null;
	
	/**
	 * @param \Aurora\System\GlobalManager &$oManager
	 */
	public function __construct(\Aurora\System\GlobalManager &$oManager, $sForcedStorage = '', \Aurora\System\AbstractModule $oModule = null)
	{
		parent::__construct('accounts', $oManager, $oModule);
		
		$this->oEavManager = \Aurora\System\Api::GetSystemManager('eav', 'db');
	}

	/**
	 * 
	 * @param int $iAccountId
	 * @return boolean
	 * @throws CApiBaseException
	 */
	public function getAccountById($iAccountId)
	{
		$oAccount = null;
		try
		{
			if (is_numeric($iAccountId))
			{
				$iAccountId = (int) $iAccountId;
				if (null === $oAccount)
				{
					$oAccount = $this->oEavManager->getEntity($iAccountId);
				}
			}
			else
			{
				throw new \CApiBaseException(Errs::Validation_InvalidParameters);
			}
		}
		catch (CApiBaseException $oException)
		{
			$oAccount = false;
			$this->setLastException($oException);
		}
		return $oAccount;
	}
	
	/**
	 * Retrieves information on particular WebMail Pro user. 
	 * 
	 * @todo not used
	 * 
	 * @param int $iUserId User identifier.
	 * 
	 * @return CUser | false
	 */
	public function getAccountByCredentials($sLogin, $sPassword)
	{
		$oAccount = null;
		try
		{
			$aResults = $this->oEavManager->getEntities(
				'CAccount', 
				array(
					'IsDisabled', 'Login', 'Password', 'IdUser'
				),
				0,
				0,
				array(
					'Login' => $sLogin,
					'Password' => $sPassword,
					'IsDisabled' => false
				)
			);
			
			if (is_array($aResults) && count($aResults) === 1)
			{
				$oAccount = $aResults[0];
			}
		}
		catch (CApiBaseException $oException)
		{
			$oAccount = false;
			$this->setLastException($oException);
		}
		return $oAccount;
	}

	/**
	 * Obtains list of information about accounts.
	 * @param int $iPage List page.
	 * @param int $iUsersPerPage Number of users on a single page.
	 * @param string $sOrderBy = 'email'. Field by which to sort.
	 * @param int $iOrderType = \ESortOrder::ASC. If **\ESortOrder::ASC** the sort order type is ascending.
	 * @param string $sSearchDesc = ''. If specified, the search goes on by substring in the name and email of default account.
	 * @return array | false
	 */
	public function getAccountList($iPage, $iUsersPerPage, $sOrderBy = 'Login', $iOrderType = \ESortOrder::ASC, $sSearchDesc = '')
	{
		$aResult = false;
		try
		{
			$aFilters =  array();
			
			if ($sSearchDesc !== '')
			{
				$aFilters['Login'] = '%'.$sSearchDesc.'%';
			}
				
			$aResults = $this->oEavManager->getEntities(
				'CAccount', 
				array(
					'IsDisabled', 'Login', 'Password', 'IdUser'
				),
				$iPage,
				$iUsersPerPage,
				$aFilters,
				$sOrderBy,
				$iOrderType
			);

			if (is_array($aResults))
			{
				foreach($aResults as $oItem)
				{
					$aResult[$oItem->EntityId] = array(
						$oItem->Login,
						$oItem->Password,
						$oItem->IdUser,
						$oItem->IsDisabled
					);
				}
			}
		}
		catch (CApiBaseException $oException)
		{
			$aResult = false;
			$this->setLastException($oException);
		}
		return $aResult;
	}

	/**
	 * @param CAccount $oAccount
	 *
	 * @return bool
	 */
	public function isExists(CAccount $oAccount)
	{
		$bResult = false;
		try
		{
			$aResults = $this->oEavManager->getEntities(
				'CAccount',
				array('Login'),
				0,
				0,
				array('Login' => $oAccount->Login)
			);

			if (is_array($aResults) && count($aResults) > 0)
			{
				$bResult = true;
			}
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $bResult;
	}
	
	/**
	 * @param CAccount $oAccount
	 *
	 * @return bool
	 */
	public function createAccount (CAccount &$oAccount)
	{
		$bResult = false;
		try
		{
			if ($oAccount->validate())
			{
				if (!$this->isExists($oAccount))
				{
					if (!$this->oEavManager->saveEntity($oAccount))
					{
						throw new \CApiManagerException(Errs::UsersManager_UserCreateFailed);
					}
				}
				else
				{
					throw new \CApiManagerException(Errs::UsersManager_UserAlreadyExists);
				}
			}

			$bResult = true;
		}
		catch (CApiBaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}

		return $bResult;
	}
	
	/**
	 * @param CAccount $oAccount
	 *
	 * @return bool
	 */
	public function updateAccount (CAccount &$oAccount)
	{
		$bResult = false;
		try
		{
			if ($oAccount->validate())
			{
//				if ($this->isExists($oAccount))
//				{
					if (!$this->oEavManager->saveEntity($oAccount))
					{
						throw new \CApiManagerException(Errs::UsersManager_UserCreateFailed);
					}
//				}
//				else
//				{
//					throw new \CApiManagerException(Errs::UsersManager_UserAlreadyExists);
//				}
			}

			$bResult = true;
		}
		catch (CApiBaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}

		return $bResult;
	}
	
	/**
	 * 
	 * @param CAccount $oAccount
	 * @return bool
	 */
	public function deleteAccount(CAccount $oAccount)
	{
		$bResult = false;
		try
		{
			$bResult = $this->oEavManager->deleteEntity($oAccount->EntityId);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}

		return $bResult;
	}
	
	/**
	 * Obtains basic accounts for specified user.
	 * 
	 * @param int $iUserId
	 * 
	 * @return array|boolean
	 */
	public function getUserAccounts($iUserId, $bWithPassword = false)
	{
		$mResult = false;
		try
		{
			$aFields = array(
				'Login'
			);
			if ($bWithPassword)
			{
				$aFields[] = 'Password';
			}
			$mResult = $this->oEavManager->getEntities(
				'CAccount',
				$aFields,
				0,
				0,
				array('IdUser' => $iUserId, 'IsDisabled' => false)
			);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $mResult;
	}
}
