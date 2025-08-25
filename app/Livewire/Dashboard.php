<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Business;
use App\Models\User;
use App\Models\Student;
use App\Models\ClassRoom;
use App\Models\Subject;
use App\Models\Exam;
use App\Models\ParentGuardian;
use App\Models\Attendance;
use Carbon\Carbon;

class Dashboard extends Component
{
    public $business;
    public $balance;
    public $lastUpdate;
    
    // Dashboard metrics
    public $totalBusinesses;
    public $totalUsers;
    public $activeBusinessClients;
    public $activeBusinessStaff;
    
    // School-specific metrics for regular businesses
    public $totalStudents;
    public $totalTeachers;
    public $totalClasses;
    public $totalSubjects;
    public $totalExams;
    public $totalParents;
    public $attendanceRate;
    public $averageGrade;
    
    // Percentage changes
    public $businessesChange = 12;
    public $usersChange = 8;
    public $clientsChange = 5;
    public $staffChange = 3;
    
    // School-specific percentage changes
    public $studentsChange = 0;
    public $teachersChange = 0;
    public $classesChange = 0;
    public $subjectsChange = 0;
    
    // Filter properties
    public $selectedCategory = '';
    public $selectedCountry = '';
    public $selectedDistrict = '';
    public $fromDate = '';
    public $toDate = '';
    
    // Filter options
    public $categories = [];
    public $countries = [];
    public $districts = [];
    
    // Chart data
    public $newUsersChartData = [];
    public $userDistributionChartData = [];
    
    // System Health data
    public $systemHealth = [];
    
    // User Role Distribution data
    public $userRoleDistributionData = [];

    public function mount()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                throw new \Exception('User not authenticated');
            }

            // Load the business relationship
            $this->business = $user->business;

            $this->balance = 0; // User model doesn't have balance property
            $this->lastUpdate = now()->format('H:i:s');
            
            // Load filter options
            $this->loadFilterOptions();
            
            // Load dashboard metrics
            $this->loadDashboardMetrics();
            
            // Load chart data
            $this->loadChartData();
            
        } catch (\Exception $e) {
            // Log the error
            Log::error('Dashboard mount error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Set default values to prevent component from breaking
            $this->totalBusinesses = 0;
            $this->totalUsers = 0;
            $this->activeBusinessClients = 0;
            $this->activeBusinessStaff = 0;
            $this->newUsersChartData = ['labels' => [], 'data' => []];
            $this->userDistributionChartData = ['labels' => [], 'data' => [], 'colors' => []];
            $this->userRoleDistributionData = ['labels' => [], 'data' => [], 'colors' => []];
            $this->systemHealth = [];
        }
    }
    
    public function loadChartData()
    {
        try {
            $this->generateNewUsersChartData();
            $this->generateUserDistributionChartData();
            $this->generateSystemHealthData();
            $this->generateUserRoleDistributionData();
        } catch (\Exception $e) {
            Log::error('Error loading chart data: ' . $e->getMessage());
            // Set default chart data
            $this->newUsersChartData = ['labels' => [], 'data' => []];
            $this->userDistributionChartData = ['labels' => [], 'data' => [], 'colors' => []];
            $this->userRoleDistributionData = ['labels' => [], 'data' => [], 'colors' => []];
            $this->systemHealth = [];
        }
    }
    
    public function generateNewUsersChartData()
    {
        // Generate monthly new users data for the current year
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $currentYear = Carbon::now()->year;
        
        $monthlyData = [];
        $user = Auth::user();
        $isAdmin = $user->business_id == 1;
        
        foreach ($months as $index => $month) {
            $startDate = Carbon::createFromDate($currentYear, $index + 1, 1)->startOfMonth();
            $endDate = Carbon::createFromDate($currentYear, $index + 1, 1)->endOfMonth();
            
            $query = User::whereBetween('created_at', [$startDate, $endDate]);
            
            if ($isAdmin) {
                // Apply filters if set for admin
                if ($this->selectedCountry) {
                    $query->whereHas('business', function($q) {
                        $q->where('country', $this->selectedCountry);
                    });
                }
                
                if ($this->selectedDistrict) {
                    $query->whereHas('business', function($q) {
                        $q->where('city', $this->selectedDistrict);
                    });
                }
                
                if ($this->selectedCategory) {
                    $query->whereHas('business.businessCategory', function($q) {
                        $q->where('name', 'like', '%' . $this->selectedCategory . '%');
                    });
                }
            } else {
                // For regular businesses, only show their own data
                $query->where('business_id', $user->business_id);
            }
            
            $count = $query->count();
            $monthlyData[] = $count;
        }
        
        $this->newUsersChartData = [
            'labels' => $months,
            'data' => $monthlyData
        ];
    }
    
    public function generateUserDistributionChartData()
    {
        // Get user distribution by business category
        $query = Business::with('businessCategory')->withCount('users');
        
        // Apply filters
        if ($this->selectedCountry) {
            $query->where('country', $this->selectedCountry);
        }
        
        if ($this->selectedDistrict) {
            $query->where('city', $this->selectedDistrict);
        }
        
        if ($this->selectedCategory) {
            $query->whereHas('businessCategory', function($q) {
                $q->where('name', 'like', '%' . $this->selectedCategory . '%');
            });
        }
        
        $distribution = $query->get()
            ->groupBy('businessCategory.name')
            ->map(function ($businesses) {
                return $businesses->sum('users_count');
            });
        
        // Prepare data for pie chart
        $labels = [];
        $data = [];
        $colors = ['#3B82F6', '#EF4444', '#8B5CF6', '#06B6D4']; // Blue, Red, Purple, Cyan
        
        foreach ($distribution as $category => $count) {
            $labels[] = $category;
            $data[] = $count;
        }
        
        $this->userDistributionChartData = [
            'labels' => $labels,
            'data' => $data,
            'colors' => array_slice($colors, 0, count($labels))
        ];
    }
    
    public function generateSystemHealthData()
    {
        // Simulate system health data
        $this->systemHealth = [
            'server' => [
                'status' => 'Online',
                'uptime' => '99.98%',
                'color' => 'green'
            ],
            'database' => [
                'status' => 'Operational',
                'color' => 'green'
            ],
            'storage' => [
                'status' => '78%',
                'warning' => 'Warning',
                'color' => 'orange'
            ],
            'api_services' => [
                'status' => 'All Operational',
                'color' => 'green'
            ]
        ];
    }
    
    public function generateUserRoleDistributionData()
    {
        // Get user role distribution
        $roleDistribution = User::with('role')
            ->get()
            ->groupBy('role.name')
            ->map(function ($users) {
                return $users->count();
            });
        
        // Prepare data for pie chart
        $labels = [];
        $data = [];
        $colors = ['#14B8A6', '#F97316', '#0F172A', '#EAB308']; // Teal, Orange, Dark Blue, Yellow
        
        // Map role names to display names
        $roleMapping = [
            // New role names from seeder
            'Parents - 1' => 'Parents',
            'Parents - 2' => 'Parents',
            'Parents - 3' => 'Parents',
            'Parents - 4' => 'Parents',
            'Parents - 5' => 'Parents',
            'Parents - 6' => 'Parents',
            'Parents - 7' => 'Parents',
            'Parents - 8' => 'Parents',
            'Teachers - 1' => 'Teachers',
            'Teachers - 2' => 'Teachers',
            'Teachers - 3' => 'Teachers',
            'Teachers - 4' => 'Teachers',
            'Teachers - 5' => 'Teachers',
            'Teachers - 6' => 'Teachers',
            'Teachers - 7' => 'Teachers',
            'Teachers - 8' => 'Teachers',
            'Business Admins - 1' => 'Business Admins',
            'Business Admins - 2' => 'Business Admins',
            'Business Admins - 3' => 'Business Admins',
            'Business Admins - 4' => 'Business Admins',
            'Business Admins - 5' => 'Business Admins',
            'Business Admins - 6' => 'Business Admins',
            'Business Admins - 7' => 'Business Admins',
            'Business Admins - 8' => 'Business Admins',
            'Other Users - 1' => 'Other Users',
            'Other Users - 2' => 'Other Users',
            'Other Users - 3' => 'Other Users',
            'Other Users - 4' => 'Other Users',
            'Other Users - 5' => 'Other Users',
            'Other Users - 6' => 'Other Users',
            'Other Users - 7' => 'Other Users',
            'Other Users - 8' => 'Other Users',
            // Old role names that exist in database
            'User' => 'Other Users',
            'User - 1' => 'Other Users',
            'User - 2' => 'Other Users',
            'User - 3' => 'Other Users',
            'User - 4' => 'Other Users',
            'User - 5' => 'Other Users',
            'User - 6' => 'Other Users',
            'User - 7' => 'Other Users',
            'User - 8' => 'Other Users',
            'Admin' => 'Business Admins',
            'CEO' => 'Business Admins',
            'Test' => 'Other Users',
            'Testing' => 'Other Users'
        ];
        
        // Group by mapped role names
        $groupedData = [];
        foreach ($roleDistribution as $roleName => $count) {
            $displayName = $roleMapping[$roleName] ?? 'Other Users';
            if (!isset($groupedData[$displayName])) {
                $groupedData[$displayName] = 0;
            }
            $groupedData[$displayName] += $count;
        }
        
        foreach ($groupedData as $displayName => $count) {
            $labels[] = $displayName;
            $data[] = $count;
        }
        
        // If no data, add some default values
        if (empty($data)) {
            $labels = ['Parents', 'Teachers', 'Business Admins', 'Other Users'];
            $data = [45, 30, 15, 10];
        }
        
        $this->userRoleDistributionData = [
            'labels' => $labels,
            'data' => $data,
            'colors' => array_slice($colors, 0, count($labels))
        ];
    }
    
    public function loadFilterOptions()
    {
        try {
            // Load categories from business categories
            $this->categories = \App\Models\BusinessCategory::pluck('name', 'name')->toArray();
            
            // Load countries from businesses
            $this->countries = Business::whereNotNull('country')
                ->distinct()
                ->pluck('country', 'country')
                ->toArray();
                
            // Load districts/cities from businesses
            $this->districts = Business::whereNotNull('city')
                ->distinct()
                ->pluck('city', 'city')
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error loading filter options: ' . $e->getMessage());
            $this->categories = [];
            $this->countries = [];
            $this->districts = [];
        }
    }
    
    public function loadDashboardMetrics()
    {
        try {
            $user = Auth::user();
            $isAdmin = $user->business_id == 1;
            
            if ($isAdmin) {
                // Admin dashboard - show system-wide metrics
                $this->loadAdminDashboardMetrics();
            } else {
                // Regular business dashboard - show business-specific metrics
                $this->loadBusinessDashboardMetrics();
            }
        } catch (\Exception $e) {
            Log::error('Error loading dashboard metrics: ' . $e->getMessage());
            $this->totalBusinesses = 0;
            $this->totalUsers = 0;
            $this->activeBusinessClients = 0;
            $this->activeBusinessStaff = 0;
        }
    }
    
    public function loadAdminDashboardMetrics()
    {
        // Build query based on filters
        $businessQuery = Business::query();
        $userQuery = User::query();
        
        // Apply filters
        if ($this->selectedCategory) {
            $businessQuery->whereHas('businessCategory', function($q) {
                $q->where('name', 'like', '%' . $this->selectedCategory . '%');
            });
        }
        
        if ($this->selectedCountry) {
            $businessQuery->where('country', $this->selectedCountry);
        }
        
        if ($this->selectedDistrict) {
            $businessQuery->where('city', $this->selectedDistrict);
        }
        
        if ($this->fromDate) {
            $fromDate = Carbon::createFromFormat('d/m/Y', $this->fromDate)->startOfDay();
            $businessQuery->where('created_at', '>=', $fromDate);
            $userQuery->where('created_at', '>=', $fromDate);
        }
        
        if ($this->toDate) {
            $toDate = Carbon::createFromFormat('d/m/Y', $this->toDate)->endOfDay();
            $businessQuery->where('created_at', '<=', $toDate);
            $userQuery->where('created_at', '<=', $toDate);
        }
        
        // Get total businesses
        $this->totalBusinesses = $businessQuery->count();
        
        // Get total users
        $this->totalUsers = $userQuery->count();
        
        // Get active business clients (users with active status)
        $this->activeBusinessClients = $userQuery->where('status', 'active')->count();
        
        // Get active business staff (users with active status and role)
        $this->activeBusinessStaff = $userQuery->where('status', 'active')
            ->whereNotNull('role_id')
            ->count();
            
        // Calculate percentage changes (simplified for demo)
        $this->calculatePercentageChanges();
    }
    
    public function loadBusinessDashboardMetrics()
    {
        $user = Auth::user();
        $businessId = $user->business_id;
        
        // Build query based on filters for this specific business
        $userQuery = User::where('business_id', $businessId);
        
        // Apply date filters if set
        if ($this->fromDate) {
            $fromDate = Carbon::createFromFormat('d/m/Y', $this->fromDate)->startOfDay();
            $userQuery->where('created_at', '>=', $fromDate);
        }
        
        if ($this->toDate) {
            $toDate = Carbon::createFromFormat('d/m/Y', $this->toDate)->endOfDay();
            $userQuery->where('created_at', '<=', $toDate);
        }
        
        // For regular businesses, show business-specific metrics
        $this->totalBusinesses = 1; // This business only
        $this->totalUsers = $userQuery->count();
        $this->activeBusinessClients = $userQuery->where('status', 'active')->count();
        $this->activeBusinessStaff = $userQuery->where('status', 'active')
            ->whereNotNull('role_id')
            ->count();
        
        // School-specific metrics
        $this->totalStudents = Student::where('business_id', $businessId)->count();
        $this->totalTeachers = User::where('business_id', $businessId)
            ->whereHas('role', function($q) {
                $q->where('name', 'like', '%teacher%');
            })->count();
        $this->totalClasses = ClassRoom::where('business_id', $businessId)->count();
        $this->totalSubjects = Subject::where('business_id', $businessId)->count();
        $this->totalExams = Exam::where('business_id', $businessId)->count();
        $this->totalParents = ParentGuardian::where('business_id', $businessId)->count();
        
        // Calculate attendance rate (simplified)
        $totalAttendanceRecords = Attendance::where('business_id', $businessId)->count();
        $presentRecords = Attendance::where('business_id', $businessId)
            ->where('status', 'present')->count();
        $this->attendanceRate = $totalAttendanceRecords > 0 ? round(($presentRecords / $totalAttendanceRecords) * 100, 1) : 0;
        
        // Average grade (simplified - you might want to implement this based on your grade system)
        $this->averageGrade = 85.5; // Placeholder
        
        // Calculate percentage changes for this business
        $this->calculateBusinessPercentageChanges();
    }
    
    public function calculatePercentageChanges()
    {
        // This is a simplified calculation for demo purposes
        // In a real application, you would compare with previous month's data
        
        $lastMonth = Carbon::now()->subMonth();
        
        // Calculate businesses change
        $lastMonthBusinesses = Business::where('created_at', '<', $lastMonth)->count();
        if ($lastMonthBusinesses > 0) {
            $this->businessesChange = round((($this->totalBusinesses - $lastMonthBusinesses) / $lastMonthBusinesses) * 100);
        }
        
        // Calculate users change
        $lastMonthUsers = User::where('created_at', '<', $lastMonth)->count();
        if ($lastMonthUsers > 0) {
            $this->usersChange = round((($this->totalUsers - $lastMonthUsers) / $lastMonthUsers) * 100);
        }
        
        // Calculate clients change
        $lastMonthClients = User::where('status', 'active')->where('created_at', '<', $lastMonth)->count();
        if ($lastMonthClients > 0) {
            $this->clientsChange = round((($this->activeBusinessClients - $lastMonthClients) / $lastMonthClients) * 100);
        }
        
        // Calculate staff change
        $lastMonthStaff = User::where('status', 'active')->whereNotNull('role_id')->where('created_at', '<', $lastMonth)->count();
        if ($lastMonthStaff > 0) {
            $this->staffChange = round((($this->activeBusinessStaff - $lastMonthStaff) / $lastMonthStaff) * 100);
        }
    }
    
    public function calculateBusinessPercentageChanges()
    {
        // Calculate percentage changes for a specific business
        $user = Auth::user();
        $businessId = $user->business_id;
        $lastMonth = Carbon::now()->subMonth();
        
        // Calculate users change for this business
        $lastMonthUsers = User::where('business_id', $businessId)->where('created_at', '<', $lastMonth)->count();
        if ($lastMonthUsers > 0) {
            $this->usersChange = round((($this->totalUsers - $lastMonthUsers) / $lastMonthUsers) * 100);
        }
        
        // Calculate clients change for this business
        $lastMonthClients = User::where('business_id', $businessId)->where('status', 'active')->where('created_at', '<', $lastMonth)->count();
        if ($lastMonthClients > 0) {
            $this->clientsChange = round((($this->activeBusinessClients - $lastMonthClients) / $lastMonthClients) * 100);
        }
        
        // Calculate staff change for this business
        $lastMonthStaff = User::where('business_id', $businessId)->where('status', 'active')->whereNotNull('role_id')->where('created_at', '<', $lastMonth)->count();
        if ($lastMonthStaff > 0) {
            $this->staffChange = round((($this->activeBusinessStaff - $lastMonthStaff) / $lastMonthStaff) * 100);
        }
        
        // Calculate school-specific changes
        $lastMonthStudents = Student::where('business_id', $businessId)->where('created_at', '<', $lastMonth)->count();
        if ($lastMonthStudents > 0) {
            $this->studentsChange = round((($this->totalStudents - $lastMonthStudents) / $lastMonthStudents) * 100);
        }
        
        $lastMonthTeachers = User::where('business_id', $businessId)
            ->whereHas('role', function($q) {
                $q->where('name', 'like', '%teacher%');
            })->where('created_at', '<', $lastMonth)->count();
        if ($lastMonthTeachers > 0) {
            $this->teachersChange = round((($this->totalTeachers - $lastMonthTeachers) / $lastMonthTeachers) * 100);
        }
        
        $lastMonthClasses = ClassRoom::where('business_id', $businessId)->where('created_at', '<', $lastMonth)->count();
        if ($lastMonthClasses > 0) {
            $this->classesChange = round((($this->totalClasses - $lastMonthClasses) / $lastMonthClasses) * 100);
        }
        
        $lastMonthSubjects = Subject::where('business_id', $businessId)->where('created_at', '<', $lastMonth)->count();
        if ($lastMonthSubjects > 0) {
            $this->subjectsChange = round((($this->totalSubjects - $lastMonthSubjects) / $lastMonthSubjects) * 100);
        }
        
        // For regular businesses, businesses change is always 0 since they only have 1 business
        $this->businessesChange = 0;
    }
    
    public function applyFilters()
    {
        // This method will be called when the Apply Filters button is clicked
        try {
            $this->loadDashboardMetrics();
            $this->loadChartData();
            $this->dispatch('chartsUpdated');
        } catch (\Exception $e) {
            // Handle any errors that might occur during filtering
            session()->flash('error', 'An error occurred while applying filters.');
        }
    }
    
    public function resetFilters()
    {
        $this->selectedCategory = '';
        $this->selectedCountry = '';
        $this->selectedDistrict = '';
        $this->fromDate = '';
        $this->toDate = '';
        
        try {
            $this->loadDashboardMetrics();
            $this->loadChartData();
            $this->dispatch('chartsUpdated');
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred while resetting filters.');
        }
    }

    public function render()
    {
        try {
            return view('livewire.dashboard');
        } catch (\Exception $e) {
            Log::error('Dashboard render error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Return a simple error view if the main view fails
            return view('livewire.dashboard-error', [
                'error' => 'Dashboard is temporarily unavailable. Please try refreshing the page.'
            ]);
        }
    }
}
