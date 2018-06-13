<?php
SELECT `tx_relax5project_domain_model_project`.* 
FROM `tx_relax5project_domain_model_project` `tx_relax5project_domain_model_project` 
LEFT JOIN `fe_users` `fe_users` 
ON `tx_relax5project_domain_model_project`.`owner` = `fe_users`.`uid` 
LEFT JOIN `tx_relax5project_domain_model_state` `tx_relax5project_domain_model_state` 
ON `tx_relax5project_domain_model_project`.`current_state` = `tx_relax5project_domain_model_state`.`uid` 
LEFT JOIN `tx_relax5project_domain_model_statepool` `tx_relax5project_domain_model_statepool` 
ON `tx_relax5project_domain_model_state`.`state` = `tx_relax5project_domain_model_statepool`.`uid` 
JOIN ( 
SELECT project, LPAD(CEIL((UNIX_TIMESTAMP()-MAX(date))/604800), 2, '0') AS weeks 
FROM tx_relax5core_domain_model_appointment 
WHERE `_temp`.`weeks`="02" AND CONCAT(`fe_users`.`last_name`, ' ', `fe_users`.`first_name`)="Diesenreiter Wolfgang" 
AND (`tx_relax5project_domain_model_state`.`state` IN (2,4,5)) 
AND appointment_status = 3 
AND date < UNIX_TIMESTAMP() 
GROUP BY 1 
HAVING weeks < 53) AS _temp 
ON _temp.project = tx_relax5project_domain_model_project.uid 
WHERE `_temp`.`weeks`="02" 
AND CONCAT(`fe_users`.`last_name`, ' ', `fe_users`.`first_name`)="Diesenreiter Wolfgang" 
AND (`tx_relax5project_domain_model_state`.`state` IN (2,4,5)) 
AND (((`fe_users`.`uid` > '0') OR (`fe_users`.`uid` IS NULL)) 
AND ((`tx_relax5project_domain_model_statepool`.`uid` > '0') OR (`tx_relax5project_domain_model_statepool`.`uid` IS NULL))) 
AND (`tx_relax5project_domain_model_project`.`sys_language_uid` IN (0, -1)) 
AND (`tx_relax5project_domain_model_project`.`pid` = 55) 
AND ((`tx_relax5project_domain_model_project`.`deleted` = 0) 
AND (`tx_relax5project_domain_model_project`.`t3ver_state` <= 0) 
AND (`tx_relax5project_domain_model_project`.`pid` <> -1) 
AND (`tx_relax5project_domain_model_project`.`hidden` = 0) 
AND (`tx_relax5project_domain_model_project`.`starttime` <= 1521098820) 
AND ((`tx_relax5project_domain_model_project`.`endtime` = 0) OR (`tx_relax5project_domain_model_project`.`endtime` > 1521098820))) 
        
AND (((`fe_users`.`pid` = 55) AND ((`fe_users`.`deleted` = 0) 
    AND (`fe_users`.`disable` = 0) 
    AND (`fe_users`.`starttime` <= 1521098820) 
    AND ((`fe_users`.`endtime` = 0) OR (`fe_users`.`endtime` > 1521098820)))) OR (`fe_users`.`uid` IS NULL)) 
        
AND (((`tx_relax5project_domain_model_state`.`sys_language_uid` IN (0, -1)) 
        AND (`tx_relax5project_domain_model_state`.`pid` = 55) 
        AND ((`tx_relax5project_domain_model_state`.`deleted` = 0) 
            AND (`tx_relax5project_domain_model_state`.`t3ver_state` <= 0) 
            AND (`tx_relax5project_domain_model_state`.`pid` <> -1) 
            AND (`tx_relax5project_domain_model_state`.`hidden` = 0) 
            AND (`tx_relax5project_domain_model_state`.`starttime` <= 1521098820) 
            AND ((`tx_relax5project_domain_model_state`.`endtime` = 0) OR (`tx_relax5project_domain_model_state`.`endtime` > 1521098820)))) OR (`tx_relax5project_domain_model_state`.`uid` IS NULL)) 
        
        
        AND (
            ((`tx_relax5project_domain_model_statepool`.`sys_language_uid` IN (0, -1)) 
            AND (`tx_relax5project_domain_model_statepool`.`pid` = 55) 
            AND ((`tx_relax5project_domain_model_statepool`.`deleted` = 0) 
                AND (`tx_relax5project_domain_model_statepool`.`t3ver_state` <= 0) 
                    AND (`tx_relax5project_domain_model_statepool`.`pid` <> -1) 
                    AND (`tx_relax5project_domain_model_statepool`.`hidden` = 0) 
                    AND (`tx_relax5project_domain_model_statepool`.`starttime` <= 1521098820) 
                    AND ((`tx_relax5project_domain_model_statepool`.`endtime` = 0) OR (`tx_relax5project_domain_model_statepool`.`endtime` > 1521098820))
                    )) OR (`tx_relax5project_domain_model_statepool`.`uid` IS NULL)) 
        ORDER BY `tx_relax5project_domain_model_state`.`state` ASC
