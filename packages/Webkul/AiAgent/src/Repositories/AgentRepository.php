<?php

namespace Webkul\AiAgent\Repositories;

use Webkul\AiAgent\Models\Agent;
use Webkul\Core\Eloquent\Repository;

class AgentRepository extends Repository
{
    /**
     * Specify Model class name.
     */
    public function model(): string
    {
        return Agent::class;
    }

    /**
     * Get active agents for dropdowns.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getActiveList()
    {
        return $this->model
            ->where('status', true)
            ->select('id', 'name as label')
            ->get();
    }
}
