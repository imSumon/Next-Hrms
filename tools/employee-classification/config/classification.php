<?php

declare(strict_types=1);

/**
 * Step 20J / 20K inputs. Company ULIDs are the seeded development rows from the
 * current control center. A seeded row is not a migration approval.
 */
return [
    'mode' => 'gates_closed',
    'target_database_write' => false,
    'signed_exceptions' => [],
    'company_ulids' => [
        24 => '01M4KG2364VXPYVZCM7HZTGPB1',
        20 => '01M4KG2384JEKYAE5SXC927QDF',
        10 => '01M4KG238KHQSQTQVFAA0YAYVG',
    ],
    'sources' => [
        [
            'relative_path' => 'legacy-data/companies/proedgea_newproedgehrm.sql',
            'bytes' => 10534148,
            'sha256' => 'DC5A92065E291F981689A06423615B555C52CDF73BDC1A976344A5531E9B9534',
            'compressed' => false,
            'header_database' => 'proedgea_newproedgehrm',
            'catalog_company_id' => 24,
            'alias' => 'proedge',
            'source_database' => 'proedgea_newproedgehrm',
            'source_system' => 'legacy_proedge',
        ],
        [
            'relative_path' => 'legacy-data/companies/proedgea_atlas.sql',
            'bytes' => 164919794,
            'sha256' => '239D2D131AF4337DDDB979468413CFEC5E2C2AC80FD7F8EF14278C2A6DB85D8D',
            'compressed' => false,
            'header_database' => 'proedgea_atlas',
            'catalog_company_id' => 10,
            'alias' => 'atlas',
            'source_database' => 'proedgea_atlas',
            'source_system' => 'legacy_atlas',
        ],
        [
            'relative_path' => 'legacy-data/companies/vtl/proedgea_vtlhrm.sql.gz',
            'bytes' => 105425407,
            'sha256' => 'DFCC698A971098C621AE5042E1557AD4A4A1B96150BBEBDF1D2398A677DBEF65',
            'compressed' => true,
            'header_database' => 'proedgea_vtlhrm',
            'catalog_company_id' => 20,
            'alias' => 'vtl',
            'source_database' => 'proedgea_vtlhrm',
            'source_system' => 'legacy_vtlhrm',
        ],
    ],
];
