<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\StandardAuth\Classes;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @package Classes
 * @subpackage Users
 */
class Account extends \Aurora\System\Classes\AbstractAccount
{
	/**
	 * Creates a new instance of the object.
	 *
	 * @return void
	 */
	public function __construct($sModule)
	{
		$this->aStaticMap = array(
			'IsDisabled'	=> array('bool', false, true),
			'IdUser'		=> array('int', 0, true),
			'Login'			=> array('string', '', true),
			'Password'		=> array('encrypted', '', true),
			'LastModified'  => array('datetime', date('Y-m-d H:i:s'))
		);
		parent::__construct($sModule);
	}

	/**
	 * Checks if the user has only valid data.
	 *
	 * @return bool
	 */
	public function validate()
	{
		switch (true)
		{
			case false:
				throw new \Aurora\System\Exceptions\ValidationException(Errs::Validation_FieldIsEmpty, null, array(
					'{{ClassName}}' => 'Aurora\Modules\Core\Classes\User', '{{ClassField}}' => 'Error'));
		}

		return true;
	}

	public function getLogin()
	{
		return $this->Login;
	}
}
