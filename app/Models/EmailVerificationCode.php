<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\EmailVerificationCode
 *
 * @property int $id
 * @property string $email
 * @property string $code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|EmailVerificationCode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EmailVerificationCode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EmailVerificationCode query()
 * @method static \Illuminate\Database\Eloquent\Builder|EmailVerificationCode whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailVerificationCode whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailVerificationCode whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailVerificationCode whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailVerificationCode whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class EmailVerificationCode extends Model
{
    use HasFactory;

    protected $guarded = [];
}
