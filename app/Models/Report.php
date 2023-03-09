<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


/**
 * App\Models\Report
 *
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $type
 * @method static Builder|Report newModelQuery()
 * @method static Builder|Report newQuery()
 * @method static Builder|Report query()
 * @method static Builder|Report whereCreatedAt($value)
 * @method static Builder|Report whereId($value)
 * @method static Builder|Report whereName($value)
 * @method static Builder|Report whereType($value)
 * @method static Builder|Report whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Report extends Model
{
    use HasFactory;
}
