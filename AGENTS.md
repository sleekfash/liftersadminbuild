# Project Architecture Rules

- Create production administrator credentials only through an explicit Laravel Artisan bootstrap command, not routine database seeding or an HTTP endpoint, so generated credentials are disclosed only during an authorized server-side setup.