<?php

namespace App\Traits;

trait AccessTrait
{
    public static $admin = [
        "Dashboard" => [
            "View Dashboard", "View Dashboard Cards",
        ]
    ];

    public static $station = [
        "Module" => ['Station Management'],
        "Stations" => ['View Station', 'Edit Station', 'Add Statiom', 'Delete Station'],
    ];

    public static $stages = [
        "Module" => ['Stage Management'],
        "Stages" => ['View Stages', 'Edit Stages', 'Add Stages', 'Delete Stages', 'Activate Stage', 'Deactivate Stage'],
    ];
    public static $bodas = [
        "Module" => ['Boda Management'],
        "Boda" => ['View Bodas', 'Edit Bodas', 'Add Bodas', 'Delete Bodas', 'Activate Boda Riders', 'Deactivate Boda Riders'],
    ];

    public static $taxis = [
        "Module" => ['Taxi Management'],
        "Taxi"=>['View Taxis' , 'Edit Taxis', 'Add Taxis', 'Delete Taxis'],
    ];


    public static $reports = [
        "Module" => ['Report Management'],
        "Report" => ['View Report', 'Edit Report', 'Add Report', 'Delete Report'],
    ];



    public static $logs = [
        "Module" => ['Log Management'],
        "Logs" => ['View Logs', 'Edit Logs', 'Add Logs', 'Delete Logs'],
    ];

    //roles
    public static $roles = [
        "Module" => ['Role Management'],
        "Role" => ['View Roles', 'Edit Roles', 'Add Roles', 'Delete Roles'],
    ];

    

    public static $auditLogs = [
        "Module" => ['Audit Logs Management'],
        "Audit Logs" => ['View Audit Logs', 'Edit Audit Logs', 'Add Audit Logs', 'Delete Audit Logs'],

    ];

    public static $staff = [
        "Module" => ['Staff Management'],
        "Staff" => ['View Staff', 'Edit Staff', 'Add Staff', 'Delete Staff', 'Assign Roles'],
    ];







    public static function spreadArrayKeys($assocArray)
    {
        $result = [];
        foreach ($assocArray as $key => $value) {
            if (is_string($key)) {
                $result[] = $key;
            }
            if (is_array($value)) {

                $result = array_merge($result, static::spreadArrayKeys($value));
            } else {
                $result[] = $value;
            }
        }
        return  $result;
    }

    public static function getAllPermissions()
    {
        $roles = static::spreadArrayKeys(
            array_merge(
                static::$admin,
                static::$station,
                static::$stages,
                static::$bodas,
                static::$taxis,
                static::$staff,
                static::$logs,
                static::$roles,
                static::$auditLogs,

            )
        );
        return $roles;
    }
    public static function getAccessControl()
    {

        $access = [
            "Dashboard" => self::$admin,
            "Station" => self::$station,
            "Stages" => self::$stages,
            "Bodas" => self::$bodas,
            "Taxis" => self::$taxis,
            "Staff" => self::$staff,
            "Roles" => self::$roles,
            "Audit Logs" => self::$auditLogs,

            // "Accounting" => static::$accounting
        ];
        return $access;
    }


    /**
     * Check if the user has specific role permission.
     *
     * @param datatype $pageRole description of page role
     * @param datatype $actions description of actions
     * @return boolean
     */
    public static function userCan($pageRole, $permissions)
    {
        $permissions =  json_decode($permissions);
        return in_array($pageRole, $permissions);
    }

    public static function user_can($page_role)
    {
        $actions1 = $_SESSION['actions'];
        $actions = json_decode($actions1);
        // print_r($actions);
        return in_array($page_role, $actions);
    }
    public static function is_assoc(array $array)
    {
        // Keys of the array
        $keys = array_keys($array);
        // If the array keys of the keys match the keys, then the array must
        // not be associative (e.g. the keys array looked like {0:0, 1:1...}).
        return array_keys($keys) !== $keys;
    }
}
