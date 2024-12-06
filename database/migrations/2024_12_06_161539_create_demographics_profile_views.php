<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }
        DB::statement("
create or replace view v_profile_demographics as
    select p.id as person_id,
           first_name, last_name,
           p.created_at,
           p.updated_at,
           p.deleted_at,
           user_id,
           institutions.name as institution,
           legacy_credentials,
           c.name as country,
           case when birth_country_opt_out then null else birth_country.name end as birth_country,
           case when birth_country_opt_out then null else birth_country_other end as birth_country_other,
           birth_country_opt_out,
           case when reside_country_opt_out then null else reside_country.name end as reside_country,
           case when reside_country_opt_out then null else reside_country_other end as reside_country_other,
           reside_country_opt_out,
           case when reside_state_opt_out then null else reside_state end as reside_state,
           reside_state_opt_out,
           case when ethnicity_opt_out then null else ethnicities end as ethnicities,
           case when ethnicity_opt_out then null else ethnicity_other end as ethnicity_other,
           ethnicity_opt_out,
           case
               when birth_year_opt_out then null
               else case
                   when year(curdate())-birth_year < 18 then '<18'
                   when year(curdate())-birth_year < 25 then '18-24'
                   when year(curdate())-birth_year < 35 then '25-34'
                   when year(curdate())-birth_year < 45 then '35-44'
                   when year(curdate())-birth_year < 55 then '45-54'
                   when year(curdate())-birth_year < 65 then '55-64'
                   when year(curdate())-birth_year < 75 then '65-74'
                   when year(curdate())-birth_year < 85 then '75-84'
                   when year(curdate())-birth_year < 999 then '85+'
               end
           end as age_category,
           -- birth_year,
           birth_year_opt_out,
           case when identity_opt_out then null else identities end as identities,
           case when identity_opt_out then null else identity_other end as identity_other,
           identity_opt_out,
           case when gender_identities_opt_out then null else gender_identities end as gender_identities,
           case when gender_identities_opt_out then null else gender_identities_other end as gender_identities_other,
           gender_identities_opt_out,
           case when support_opt_out then null else support end as support,
           case when support_opt_out then null else support_other end as support_other,
           support_opt_out,
           case when disadvantaged_opt_out then null else disadvantaged end as disadvantaged,
           case when disadvantaged_opt_out then null else disadvantaged_other end as disadvantaged_other,
           disadvantaged_opt_out,
           case when occupations_opt_out then null else occupations end as occupations,
           case when occupations_opt_out then null else occupations_other end as occupations_other,
           occupations_opt_out,
           specialty,
           demographics_completed_date,
           demographics_version
from people p
left join institutions on p.institution_id = institutions.id
left join countries c on p.birth_country = c.id
left join countries birth_country on p.birth_country = birth_country.id
left join countries reside_country on p.reside_country = reside_country.id
;
        ");
        DB::statement("
create or replace view v_group_profile_demographics as
select
    gt.name as group_type,
    r.name as role,
    g.name,
    vpd.*
from `groups` g
join group_statuses gs on (g.group_status_id = gs.id)
join group_types gt on (g.group_type_id = gt.id)
join group_members gm on (g.id = gm.group_id)
join v_profile_demographics vpd on gm.person_id = vpd.person_id
join model_has_roles mhr on (gm.id = mhr.model_id and model_type = 'App\\\\Modules\\\\Group\\\\Models\\\\GroupMember')
join roles r on (mhr.role_id = r.id)
where
    g.deleted_at is null
    and gm.deleted_at is null
    and vpd.deleted_at is null
    and gs.name = 'active'
;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (app()->environment('testing')) {
            return;
        }
        DB::statement('drop view if exists v_profile_demographics;');
        DB::statement('drop view if exists v_group_profile_demographics;');
    }
};
