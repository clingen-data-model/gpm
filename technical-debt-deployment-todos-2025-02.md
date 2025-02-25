Several things are being updated towards the end of 2025-02 that will require special
care when deploying:

- Run `php artisan db:seed GroupTypeSeeder` to get curation targets included in the db
- Run `php artisan fixup:convert-descriptions-to-html` to convert legacy markdown descriptions
  to an HTML representation going forward.
