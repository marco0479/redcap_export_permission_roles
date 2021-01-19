<?php

$pid = @$_POST["pid"];
$storeheader = @$_POST["storeheader"];

if ($pid === null) {
    die("Invalid request, pid missing");
}


$Proj = new Project($pid);
$datestamp = new DateTime();
global $module;
header("Content-type: text/csv");
header("Content-Disposition: attachment; filename=".$Proj->project["project_name"]."-userpermissions-".$datestamp->format("Y-m-d").".csv");
header("Pragma: no-cache");
header("Expires: 0");
/**
 * @var $module \uzgent\ExportUserRoleDag\ExportUserRoleDag
 */
$dags = $Proj->getGroups();
if ($storeheader == "on") {
    if($dags == null) 
    {
        echo "\"First Name\",\"Last Name\",\"Username\",\"User Email\",\"Role name\",\"Last login\"\n";
    } else {
        echo "\"First Name\",\"Last Name\",\"Username\",\"User Email\",\"Role name\",\"Data access group\",\"Last login\"\n";
    }
    
}
$userrights = getRightsAllUsers();
$roles =  UserRights::getRoles();
if ($dags == null) {
    foreach($userrights as $username => $userdetails)
    {
        echo "\"" . $userdetails['user_firstname'] . "\",\"" . $userdetails['user_lastname'] . "\",\"" . 
            $username . "\",\"" . $userdetails['user_email'] . "\",\"" . $roles[$userdetails["role_id"]]['role_name'] . "\",\"" . 
            $userdetails['user_lastlogin'] . "\"\n";
    }
}
else {
    foreach($userrights as $username => $userdetails)
    {
        echo "\"" . $userdetails['user_firstname'] . "\",\"" . $userdetails['user_lastname'] . "\",\"" . $username . "\",\"" . 
            $userdetails['user_email'] . "\",\"" . $roles[$userdetails["role_id"]]['role_name'] . "\",\"" . 
            $dags[$userdetails["group_id"]] . "\",\"" . $userdetails['user_lastlogin'] . "\"\n";
    }
}

/*
The same function in redcap (UserRights::getRightsAllUsers()) do not return the users email
*/
function getRightsAllUsers($enableDagLimiting=true){
    global $user_rights;
    // Pull all user/role info for this project
    $users = array();
    $group_sql = ($enableDagLimiting && $user_rights['group_id'] != "") ? "and u.group_id = '".$user_rights['group_id']."'" : "";
    $sql = "SELECT u.*, i.user_firstname, i.user_lastname, trim(concat(i.user_firstname, ' ', i.user_lastname)) as user_fullname,
            i.user_email, i.user_lastlogin
            FROM redcap_user_rights AS u 
            LEFT OUTER JOIN redcap_user_information AS i ON i.username = u.username
            WHERE u.project_id = " . PROJECT_ID . " $group_sql ORDER BY u.username";
    $q = db_query($sql);
    while ($row = db_fetch_assoc($q)) {
        // Set username so we can set as key and remove from array values
        $username = $row['username'];
        unset($row['username']);
        // Add to array
        $users[$username] = $row;
    }
    // Return array
    return $users;
}

