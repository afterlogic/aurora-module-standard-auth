<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\StandardAuth\Managers\Accounts;

use \Aurora\System\Enums\SortOrder;
use \Aurora\Modules\StandardAuth\Models\Account;
use Illuminate\Database\Eloquent\Collection;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 */
class Manager extends \Aurora\System\Managers\AbstractManager
{
	public function __construct(\Aurora\System\Module\AbstractModule $oModule = null)
	{
		parent::__construct($oModule);
	}

	/**
	 *
	 * @param int $iAccountId
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\BaseException
	 */
	public function getAccountById($iAccountId)
	{
		$oAccount = null;
		try
		{
			if (is_numeric($iAccountId))
			{
				$iAccountId = (int) $iAccountId;

				$oAccount = Account::find($iAccountId);
			}
			else
			{
				throw new \Aurora\System\Exceptions\BaseException(Errs::Validation_InvalidParameters);
			}
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
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
	 * @return User | false
	 */
	public function getAccountByCredentials($sLogin, $sPassword)
	{
		$oAccount = null;
		try
		{
			$account = Account::where('IsDisabled', false)->where('Login', $sLogin)->get();
			if(!$account->count() || $account->count() > 1){
				return null;
			}
			if($account->first()->getPassword() === $sPassword){
				$oAccount = $account->first();
			}
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
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
	 * @param int $iOrderType = \Aurora\System\Enums\SortOrder::ASC. If **\Aurora\System\Enums\SortOrder::ASC** the sort order type is ascending.
	 * @param string $sSearchDesc = ''. If specified, the search goes on by substring in the name and email of default account.
	 * @return array | false
	 */
	public function getAccountList($iPage, $iUsersPerPage, $sOrderBy = 'Login', $iOrderType = \Aurora\System\Enums\SortOrder::ASC, $sSearchDesc = '')
	{
		$aResult = false;
		try
		{
			$query = Account::query();
			if ($sSearchDesc !== '')
			{
				$query = $query->where('Login', 'LIKE', '%'.$sSearchDesc.'%');
			}
			if ($iPage > 0) {
				$query = $query->offset($iPage);
			}
			if ($iUsersPerPage > 0) {
				$query = $query->limit($iUsersPerPage);
			}
			$aResults = $query->orderBy($sOrderBy, $iOrderType === SortOrder::ASC ? 'asc' : 'desc')->get();

			if ($aResults)
			{
				foreach($aResults as $oItem)
				{
					$aResult[$oItem->Id] = [
						$oItem->Login,
						$oItem->Password,
						$oItem->IdUser,
						$oItem->IsDisabled
					];
				}
			}
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$aResult = false;
			$this->setLastException($oException);
		}
		return $aResult;
	}

	/**
	 * @param \Aurora\Modules\StandardAuth\Models\Account $oAccount
	 *
	 * @return bool
	 */
	public function isExists(\Aurora\Modules\StandardAuth\Models\Account $oAccount)
	{
		$bResult = false;
		try
		{
			$oAccount = Account::where('Login', $oAccount->Login)->first();

			if ($oAccount)
			{
				$bResult = true;
			}
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $bResult;
	}

	/**
	 * @param \Aurora\Modules\StandardAuth\Models\Account $oAccount
	 *
	 * @return bool
	 */
	public function createAccount (\Aurora\Modules\StandardAuth\Models\Account &$oAccount)
	{
		$bResult = false;
		try
		{
			if ($oAccount->validate())
			{
				if (!$this->isExists($oAccount))
				{
					if (!$oAccount->save())
					{
						throw new \Aurora\System\Exceptions\ManagerException(Errs::UsersManager_UserCreateFailed);
					}
				}
				else
				{
					throw new \Aurora\System\Exceptions\ManagerException(Errs::UsersManager_UserAlreadyExists);
				}
			}

			$bResult = true;
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}

		return $bResult;
	}

	/**
	 * @param \Aurora\Modules\StandardAuth\Models\Account $oAccount
	 *
	 * @return bool
	 */
	public function updateAccount (\Aurora\Modules\StandardAuth\Models\Account &$oAccount)
	{
		$bResult = false;
		try
		{
			if ($oAccount->validate())
			{
//				if ($this->isExists($oAccount))
//				{
					if (!$oAccount->save())
					{
						throw new \Aurora\System\Exceptions\ManagerException(Errs::UsersManager_UserCreateFailed);
					}
//				}
//				else
//				{
//					throw new \Aurora\System\Exceptions\ManagerException(Errs::UsersManager_UserAlreadyExists);
//				}
			}

			$bResult = true;
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}

		return $bResult;
	}

	/**
	 *
	 * @param \Aurora\Modules\StandardAuth\Models\Account $oAccount
	 * @return bool
	 */
	public function deleteAccount(\Aurora\Modules\StandardAuth\Models\Account $oAccount)
	{
		$bResult = false;
		try
		{
			$oAccount->delete();
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
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
			// $aFields = [
			// 	'Login'
			// ];
			// if ($bWithPassword)
			// {
			// 	$aFields[] = 'Password';
			// }
			$mResult = Account::where('IdUser', $iUserId)->where('IsDisabled', false)->get();
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $mResult;
	}

	public function getAccountUsedToAuthorize($sEmail)
	{
		$aFilters = [
			'Login' => $sEmail,
			'IsDisabled' => false
		];
        $mAccount = Account::where($aFilters)->first();

		return $mAccount;
	}
}
