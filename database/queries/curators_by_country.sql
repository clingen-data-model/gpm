/* Distinct people with chair group role by institution */
SELECT i.name, count(distinct p.id)  FROM group_members gm 
    JOIN model_has_roles mhr ON mhr.model_type = 'App\\Modules\\Group\\Models\\GroupMember' AND mhr.model_id = gm.id
    JOIN roles r ON mhr.role_id = r.id
    JOIN people p ON p.id = gm.person_id
    JOIN institutions i ON i.id = p.institution_id
WHERE r.name = 'chair'
GROUP BY c.name;