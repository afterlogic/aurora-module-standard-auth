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
        'Password' => \Aurora\System\Casts\Encrypt::class
    ];

    protected $attributes = [
    ];

	public function getLogin()
	{
		return $this->Login;
	}

    public function getPassword()
    {
        return $this->Password;
    }

    public function setPassword($sPassword)
    {
        $this->Password = $sPassword;
    }

}