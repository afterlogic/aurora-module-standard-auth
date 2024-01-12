<?php

namespace Aurora\Modules\StandardAuth\Models;

use Aurora\System\Classes\Account as SystemAccount;

/**
 * Aurora\Modules\StandardAuth\Models\Account
 *
 * @property integer $Id
 * @property integer $IsDisabled
 * @property integer $IdUser
 * @property string $Login
 * @property string $Password
 * @property string $LastModified
 * @property array|null $Properties
 * @property \Illuminate\Support\Carbon|null $CreatedAt
 * @property \Illuminate\Support\Carbon|null $UpdatedAt
 * @property-read mixed $entity_id
 * @method static int count(string $columns = '*')
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\StandardAuth\Models\Account find(int|string $id, array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\StandardAuth\Models\Account findOrFail(int|string $id, mixed $id, Closure|array|string $columns = ['*'], Closure $callback = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\StandardAuth\Models\Account first(array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\StandardAuth\Models\Account firstWhere(Closure|string|array|\Illuminate\Database\Query\Expression $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Account newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Account newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Account query()
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\StandardAuth\Models\Account where(Closure|string|array|\Illuminate\Database\Query\Expression $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereIdUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\Modules\StandardAuth\Models\Account whereIn(string $column, mixed $values, string $boolean = 'and', bool $not = false)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereIsDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereLastModified($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereProperties($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Account extends SystemAccount
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
