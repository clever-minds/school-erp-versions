<?php

namespace App\Repositories\StudentPickup;

use App\Models\StudentPickupRequest;
use App\Repositories\Base\BaseRepository;

class StudentPickupRepository extends BaseRepository implements StudentPickupInterface
{
    public function __construct(StudentPickupRequest $model)
    {
        parent::__construct($model);
    }
}
