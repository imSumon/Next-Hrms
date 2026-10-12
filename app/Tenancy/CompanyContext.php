<?php

namespace App\Tenancy;

/**
 * Trusted company for the current request.
 *
 * Nothing in the product binds this. A submitted company id is not a context.
 */
final class CompanyContext
{
    public const SESSION_KEY = 'company_context_id';

    public function __construct(public readonly string $companyId) {}
}
