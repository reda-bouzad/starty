<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;




/**
 * App\Models\ModelReport
 *
 * @property int $id
 * @property int $model_id
 * @property int $report_id
 * @property int $user_id
 * @property string $model_type
 * @property-read \App\Models\Party|null $event
 * @property-read \App\Models\Report|null $report
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $reportable
 * @property-read \App\Models\User|null $user
 * @method static Builder|ModelReport newModelQuery()
 * @method static Builder|ModelReport newQuery()
 * @method static Builder|ModelReport query()
 * @method static Builder|ModelReport whereId($value)
 * @method static Builder|ModelReport whereModelId($value)
 * @method static Builder|ModelReport whereModelType($value)
 * @method static Builder|ModelReport whereReportId($value)
 * @method static Builder|ModelReport whereUserId($value)
 * @mixin \Eloquent
 */
class ModelReport extends Pivot {
     use HasFactory;
     public $timestamps = false;
     protected $guarded = [];

     public function reportable()
    {
        return $this->morphTo();
    }

    public function report(){
        return $this->belongsTo(Report::class,'report_id');
    }

    public function user(){
         return $this->belongsTo(User::class,'model_id');
    }
    public function event(){
         return $this->belongsTo(Party::class,'model_id');
    }
}
