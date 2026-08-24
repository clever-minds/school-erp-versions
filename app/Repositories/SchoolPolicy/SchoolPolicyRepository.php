<?php

namespace App\Repositories\SchoolPolicy;

use App\Models\SchoolPolicy;
use App\Repositories\SchoolPolicy\SchoolPolicyInterface;

class SchoolPolicyRepository implements SchoolPolicyInterface
{
    public function all()
    {
        return SchoolPolicy::where('school_id', auth()->user()->school_id)->get();
    }

    public function find($id)
    {
        return SchoolPolicy::where('school_id', auth()->user()->school_id)->findOrFail($id);
    }

    public function create(array $data)
    {
        $data['school_id'] = auth()->user()->school_id;
        return SchoolPolicy::create($data);
    }

    public function update($id, array $data)
    {
        $policy = $this->find($id);
        $policy->update($data);
        return $policy;
    }

    public function delete($id)
    {
        $policy = $this->find($id);
        return $policy->delete();
    }
}
