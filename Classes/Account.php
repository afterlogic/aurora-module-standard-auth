<?php
/**
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0 or AfterLogic Software License
 *
 * This code is licensed under AGPLv3 license or AfterLogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\StandardAuth\Classes;

/**
 *
 * @package Classes
 * @subpackage Users
 */
class Account extends \Aurora\System\EAV\Entity
{
	/**
	 * Creates a new instance of the object.
	 * 
	 * @return void
	 */
	public function __construct($sModule)
	{
		$this->aStaticMap = array(
			'IsDisabled'	=> array('bool', false),
			'IdUser'		=> array('int', 0),
			'Login'			=> array('string', ''),
			'Password'		=> array('encrypted', ''),
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
					'{{ClassName}}' => '\Aurora\Modules\Core\Clases\CUser', '{{ClassField}}' => 'Error'));
		}

		return true;
	}
}
