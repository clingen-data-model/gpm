Updating groups and roles requires making changes that will end up in 3 places:
php config files, javascript config files, and the database. So this is a multi-step
process.

Note that *tasks 2-4 must be run within the app container*:
1. Add group information to `app/Modules/Group/groups.php` (following examples
   that are there)
2. run `php artisan config:cache` from within app container (without this, the
   seeder doesn't work)
3. run `php artisan db:seed GroupRoleAndPermissionsSeeder` to get things in the
   database (this would also have to be run in production once changes are committed)
4. run `php artisan config:export` so relevant json files are updated (generally
   run as part of `npm run build`, but have to do this in the php container
   which doesn't have npm installed)
5. run `npm run build` from within the resources directory to incorporate into
   webpack-ed js files
