<?php

namespace App\Repositories\Fees;

use App\Models\Fee;
use App\Repositories\Saas\SaaSRepository;

class FeesRepository extends SaaSRepository implements FeesInterface {
    public function __construct(Fee $model) {
        parent::__construct($model);
    }
     public function getByIds(array $ids)
    {
        return $this->defaultModel()
            ->whereIn('id', $ids)
            ->get();
    }
    public function getFeesIdsBySessionYear($sessionYearId)
{
    return $this->defaultModel()
        ->where('session_year_id', $sessionYearId)
        ->pluck('id')
        ->toArray();
}
}
