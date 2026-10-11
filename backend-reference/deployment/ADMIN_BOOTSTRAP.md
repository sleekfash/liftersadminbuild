# Initial Administrator Setup

The initial administrator is created explicitly on the Laravel host. Routine database seeding does not create administrator credentials, and there is no public setup endpoint.

## Before setup

- Deploy the Laravel backend and install its Composer dependencies.
- Configure the production database, HTTPS `APP_URL`, and `APP_DEBUG=false` in the server-side environment file.
- Back up the production database before applying migrations.
- Use a private SSH terminal on the deployment host. Do not run the bootstrap through a browser, CI log, or a command that records terminal output.

## Create the first branch and administrator

From the deployed Laravel project directory, run these commands separately:

```sh
php artisan migrate --force
php artisan admin:bootstrap
```

The bootstrap command creates `admin@lifterscenter.com` as an active `SUPER_ADMIN`, creates the `HQ` / `Head Office` branch only if it does not already exist, and leaves the administrator unassigned to a branch. Existing branch records are not modified. It refuses to change anything if that email already exists; it never resets an existing password.

The temporary password is generated on the server and printed once to that private terminal. Save it in a password manager, sign in promptly, and change it. The application does not retain a recoverable copy. Do not paste the command output into support chats, tickets, or deployment logs.

## Connect the app and verify sign-in

After the API is deployed and reachable over HTTPS, set these public build-time variables for the frontend and rebuild/redeploy it:

```text
VITE_API_MODE=live
VITE_API_BASE_URL=https://your-laravel-api.example
```

Use the API origin only: the app adds `/api/v1` itself. Configure the Laravel host to allow the frontend origin through CORS. Confirm `https://your-laravel-api.example/api/v1/health` reports a healthy database, then sign in at the app's `/login` page using the temporary credential. The Administration page should be available to the new `SUPER_ADMIN`.

The Laravel feature test `tests/Feature/AdminBootstrapCommandTest.php` checks that the bootstrap creates the HQ branch, that the generated credential can sign in and returns the super-admin role, and that rerunning the command does not overwrite an existing account or branch.