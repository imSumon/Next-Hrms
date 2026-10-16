<?php

namespace App\Organization;

use App\Models\AuditEvent;
use App\Models\Designation;

class DesignationRegistry extends MasterRecordRegistry
{
    public function __construct()
    {
        parent::__construct(Designation::class, AuditEvent::SUBJECT_DESIGNATION);
    }
}
