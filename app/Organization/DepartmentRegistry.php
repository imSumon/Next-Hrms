<?php

namespace App\Organization;

use App\Models\AuditEvent;
use App\Models\Department;

class DepartmentRegistry extends MasterRecordRegistry
{
    public function __construct()
    {
        parent::__construct(Department::class, AuditEvent::SUBJECT_DEPARTMENT);
    }
}
