/* Distinct people with coordinator group role by country */
SELECT c.name, count(distinct p.id)  FROM group_members gm 
    JOIN model_has_roles mhr ON mhr.model_type = 'App\\Modules\\Group\\Models\\GroupMember' AND mhr.model_id = gm.id
    JOIN roles r ON mhr.role_id = r.id
    JOIN people p ON p.id = gm.person_id
    JOIN countries c ON c.id = p.country_id
WHERE r.name = 'biocurator'
GROUP BY c.name;