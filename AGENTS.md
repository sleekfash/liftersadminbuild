# Project Architecture Rules

- Create production administrator credentials only through an explicit Laravel Artisan bootstrap command, not routine database seeding or an HTTP endpoint, so generated credentials are disclosed only during an authorized server-side setup.
- Keep branch review rules in the shared frontend workflow module and enforce equivalent rules inside locked Laravel transactions; presentation controls are not authorization.
- Load review details through the authenticated disbursement show endpoint and map persisted lifecycle histories into the drawer; never infer completed approvals from stage alone.