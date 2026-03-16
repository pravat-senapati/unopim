<?php

namespace Webkul\DataTransfer\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\DataTransfer\Contracts\JobWarning;

class JobWarningRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return JobWarning::class;
    }

    /**
     * Store a warning for a job track.
     *
     * @param  int  $jobTrackId
     * @param  string  $reason
     * @param  array  $item
     * @return \Webkul\DataTransfer\Models\JobWarning
     */
    public function storeWarning(int $jobTrackId, string $reason, array $item = []): \Webkul\DataTransfer\Models\JobWarning
    {
        return $this->create([
            'job_track_id' => $jobTrackId,
            'reason'       => $reason,
            'item'         => $item,
        ]);
    }

    /**
     * Get warnings grouped by job track id.
     *
     * @param  int  $jobTrackId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getWarningsByJobTrackId(int $jobTrackId)
    {
        return $this->findWhere(['job_track_id' => $jobTrackId]);
    }
}
