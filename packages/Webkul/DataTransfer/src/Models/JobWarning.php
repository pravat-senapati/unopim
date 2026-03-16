<?php

namespace Webkul\DataTransfer\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\DataTransfer\Contracts\JobWarning as JobWarningContract;

class JobWarning extends Model implements JobWarningContract
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'job_warnings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'job_track_id',
        'reason',
        'item',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'item' => 'array',
    ];

    /**
     * Get the job track that owns the warning.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function jobTrack()
    {
        return $this->belongsTo(JobTrackProxy::modelClass());
    }
}
