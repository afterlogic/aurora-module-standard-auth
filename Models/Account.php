<?php

namespace Aurora\Modules\StandardAuth\Models;

use \Aurora\System\Classes\Model;

class Account extends Model
{

    protected $table = 'standard_auth_accounts';
    protected $moduleName = 'StandardAuth';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'Id',
        'IsDisabled',
        'IdUser',
        'Login',
        'Password',
		'LastModified',
        'Properties'
    ];

    /**
    * The attributes that should be hidden for arrays.
    *
    * @var array
    */
    protected $hidden = [
    ];

    protected $casts = [
        'Properties' => 'array',
    ];

    protected $attributes = [
    ];

	public function getLogin()
	{
		return $this->Login;
	}
    public function getPassword()
    {
        $sPassword = '';
        if (!$this->Password) // TODO: Legacy support
        {
            $sSalt = \Aurora\System\Api::$sSalt;
            \Aurora\System\Api::$sSalt = md5($sSalt);
            $sPassword = $this->Password;
            \Aurora\System\Api::$sSalt = $sSalt;
        }
        else
        {
            $sPassword = $this->Password;
        }

        $sPassword = \Aurora\System\Utils::DecryptValue($sPassword);
        if ($sPassword !== '' && strpos($sPassword, $this->Login . ':') === false)
        {
            $sPassword = substr($sPassword, strlen($this->Login));
        }
        else
        {
            $sPassword = substr($sPassword, strlen($this->Login) + 1);
        }
        return $sPassword;
    }

    public function setPassword($sPassword)
    {
        $this->Password = \Aurora\System\Utils::EncryptValue($this->Login . ':' . $sPassword);
    }

}