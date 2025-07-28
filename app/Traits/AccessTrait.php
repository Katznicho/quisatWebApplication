<?php

namespace App\Traits;

trait AccessTrait
{
    public static $admin = [
        "Dashboard" => [
            "View Dashboard", "View Dashboard Cards", "View Dashboard Charts", "View Dashboard Tables",
        ]
    ];

    public static $entities = [
        "Business" => ['View Business', 'Edit Business', 'Add Business', 'Delete Business'],
    ];

    public static $staff = [
        "Staff" => ['View Staff', 'Edit Staff', 'Add Staff', 'Delete Staff', 'Assign Roles'],
    ];

    public static $reports = [
        "Reports" => ['View Report', 'Edit Report', 'Add Report', 'Delete Report'],
    ];

    public static $logs = [
        "Logs" => ['View Logs'],
    ];

    public static $adminAccess = [
        "Admin Users" => ['View Admin Users', 'Edit Admin Users', 'Add Admin Users', 'Delete Admin Users', 'Assign Roles'],
        "Audit Logs" => ['View Audit Logs'],
        "System Settings" => ['View System Settings', 'Edit System Settings'],
    ];

    public static $businessAccess = [
        "Business" => ['View Business', 'Edit Business', 'Add Business', 'Delete Business'],
    ];

    public static $clientAccess = [
        "Clients" => ['View Clients', 'Edit Clients', 'Add Clients', 'Delete Clients'],
    ];

    public static $staffAccess = [
        "Staff" => ['View Staff', 'Edit Staff', 'Add Staff', 'Delete Staff', 'Assign Roles'],
    ];

    public static $reportAccess = [
        "Reports" => ['View Reports', 'Export Reports', 'Filter Reports'],
    ];

    public static $bulkUpload = [
        "Bulk Upload" => ['Bulk Validations Upload'],
    ];

    // ✅ Individual functional permissions (multi-entity compatible)
    public static $chat = [
        "Chat (Communications)" => ['View Chat', 'Add Chat', 'Edit Chat', 'Delete Chat'],
    ];

    public static $calendar = [
        "Calendar" => ['View Calendar', 'Add Calendar', 'Edit Calendar', 'Delete Calendar'],
    ];

    public static $classes = [
        "Classes" => ['View Classes', 'Add Classes', 'Edit Classes', 'Delete Classes'],
    ];

    public static $progressReport = [
        "Progress Report" => ['View Progress Report', 'Add Progress Report', 'Edit Progress Report', 'Delete Progress Report'],
    ];

    public static $reportCard = [
        "Report Card" => ['View Report Card', 'Add Report Card', 'Edit Report Card', 'Delete Report Card'],
    ];

    public static $staffTeachers = [
        "Staff (Teachers)" => ['View Staff (Teachers)', 'Add Staff (Teachers)', 'Edit Staff (Teachers)', 'Delete Staff (Teachers)'],
    ];

    public static $timeTable = [
        "Time Table" => ['View Time Table', 'Add Time Table', 'Edit Time Table', 'Delete Time Table'],
    ];

    public static $grades = [
        "Grades" => ['View Grades', 'Add Grades', 'Edit Grades', 'Delete Grades'],
    ];

    public static $attendance = [
        "Attendance" => ['View Attendance', 'Add Attendance', 'Edit Attendance', 'Delete Attendance'],
    ];

    public static $members = [
        "Members (Students, Parents)" => ['View Members', 'Add Members', 'Edit Members', 'Delete Members'],
    ];

    public static $assignments = [
        "Assignments" => ['View Assignments', 'Add Assignments', 'Edit Assignments', 'Delete Assignments'],
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
        return $result;
    }

    public static function getAllPermissions()
    {
        $roles = static::spreadArrayKeys(
            array_merge(
                static::$admin,
                static::$entities,
                static::$staff,
                static::$reports,
                static::$logs,
                static::$adminAccess,
                static::$businessAccess,
                static::$clientAccess,
                static::$staffAccess,
                static::$reportAccess,
                static::$bulkUpload,

                // ✅ Individually added multi-entity permissions
                static::$chat,
                static::$calendar,
                static::$classes,
                static::$progressReport,
                static::$reportCard,
                static::$staffTeachers,
                static::$timeTable,
                static::$grades,
                static::$attendance,
                static::$members,
                static::$assignments
            )
        );
        return $roles;
    }

    public static function getAccessControl(array $exclude = [])
    {
        $permissions = [
            "Dashboard" => self::$admin,
            "Entities" => self::$entities,
            "Staff" => self::$staff,
            "Reports" => self::$reports,
            "Logs" => self::$logs,
            "Admin" => self::$adminAccess,
            "Business" => self::$businessAccess,
            "Client" => self::$clientAccess,
            "Staff Access" => self::$staffAccess,
            "Report Access" => self::$reportAccess,
            "Bulk Upload" => self::$bulkUpload,

            // ✅ Grouped individually for flexibility
            "Chat (Communications)" => self::$chat,
            "Calendar" => self::$calendar,
            "Classes" => self::$classes,
            "Progress Report" => self::$progressReport,
            "Report Card" => self::$reportCard,
            "Staff (Teachers)" => self::$staffTeachers,
            "Time Table" => self::$timeTable,
            "Grades" => self::$grades,
            "Attendance" => self::$attendance,
            "Members (Students, Parents)" => self::$members,
            "Assignments" => self::$assignments,
        ];

        if (!empty($exclude)) {
            $permissions = collect($permissions)->reject(function ($_, $key) use ($exclude) {
                return in_array($key, $exclude);
            })->toArray();
        }

        return $permissions;
    }

    public static function userCan($pageRole, $permissions)
    {
        $permissions = json_decode($permissions);
        return in_array($pageRole, $permissions);
    }

    public static function user_can($page_role)
    {
        $actions1 = $_SESSION['actions'];
        $actions = json_decode($actions1);
        return in_array($page_role, $actions);
    }

    public static function is_assoc(array $array)
    {
        $keys = array_keys($array);
        return array_keys($keys) !== $keys;
    }
}
