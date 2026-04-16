# Troubleshooting

## Common Issues

### Permission Denied Errors (403 Forbidden)

**Symptom:** A user sees "403 Forbidden" when accessing a page.

**Cause:** The user's role does not have the required permission for that action.

**Solution:**
1. Check the user's assigned role in Admin Users
2. Navigate to Roles and verify the role has the necessary permissions
3. If using a custom role, ensure all required `module.action` permissions are checked
4. Clear the permission cache: `php artisan permission:cache-reset`

### Storage Link Not Working (Images/Files Not Loading)

**Symptom:** Product images or uploaded files show as broken.

**Cause:** The symbolic link from `public/storage` to `storage/app/public` is missing.

**Solution:**

```bash
php artisan storage:link
```

If the link already exists but is broken, remove it first:

```bash
rm public/storage
php artisan storage:link
```

### Queue Jobs Not Processing

**Symptom:** Emails not sending, exports not generating.

**Cause:** The queue worker is not running or the queue connection is misconfigured.

**Solution:**
1. Check your `.env` queue driver: `QUEUE_CONNECTION=redis` (or `database`)
2. Start the queue worker: `php artisan queue:work`
3. Check for failed jobs: `php artisan queue:failed`
4. Retry failed jobs: `php artisan queue:retry all`

### Memory Exhaustion on PDF/Excel Export

**Symptom:** "Allowed memory size exhausted" error when generating PDFs or Excel exports.

**Solution:**
1. Increase PHP memory limit in `php.ini`: `memory_limit = 512M`
2. For large reports, consider paginating the data before export
3. Use chunked queries in Excel exports with Maatwebsite/Excel's `FromQuery` concern

### Migration Errors

**Symptom:** `php artisan migrate` fails with foreign key or table exists errors.

**Solution:**
1. Check that migrations run in order (timestamps should be sequential)
2. For foreign key issues, ensure referenced tables are created before the referencing table
3. Fresh start (development only): `php artisan migrate:fresh --seed`

### Login Not Working

**Symptom:** Correct credentials but login redirects back to the login page.

**Possible Causes:**
1. Session driver misconfigured — try `SESSION_DRIVER=file` in `.env`
2. Cookie domain mismatch — check `SESSION_DOMAIN` in `.env`
3. CSRF token mismatch — ensure the form includes `@csrf`
4. Admin guard not configured — verify `config/auth.php` has the `admin` guard

### Slow Dashboard Loading

**Symptom:** Dashboard takes a long time to load.

**Solution:**
1. Add database indexes on frequently queried columns
2. Cache optimization: `php artisan config:cache && php artisan route:cache`
3. Enable query caching for dashboard widgets
4. Check for N+1 queries using Laravel Debugbar

### Activity Log Table Growing Large

**Symptom:** The `activity_log` table has millions of rows, slowing queries.

**Solution:**
1. Add a cleanup schedule: `php artisan activitylog:clean --days=90`
2. Schedule automatic cleanup in the Kernel:

```php
$schedule->command('activitylog:clean --days=90')->weekly();
```

## Getting Help

If your issue is not listed above:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Enable debug mode temporarily: `APP_DEBUG=true` (revert after debugging)
3. Check PHP error logs on the server
4. Search Laravel documentation at https://laravel.com/docs
