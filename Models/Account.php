<?php

namespace Aurora\Modules\StandardAuth\Models;

use \Aurora\System\Classes\Model;

class Account extends Model
{
    protected $moduleName = 'StandardAuth';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
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
}