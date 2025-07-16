<?php

namespace App\Constants;

class Constants {

    const ACCOUNT_TYPES = [
        'PERSONAL' => 'Personal',
        'GROUP' => 'Group',
        'FUNDRAISER' => 'Fundraiser',
    ];

    // Account Statuses
    const ACCOUNT_STATUSES = [
        'ACTIVE' => 'active',
        'INACTIVE' => 'inactive',
    ];

    // Duration Types
    const DURATION_TYPES = [
        'TAGRET SAVING' => 'Target Saving',
        'LONG_TERM' => 'Long Term',
    ];
    //['Admin', 'Member', 'Contributor', 'FundRaiser']

    const MEMBER_ROLES = [
        'ADMIN' => 'Admin',
        'MEMBER' => 'Member',
        'CONTRIBUTOR' => 'Contributor',
        'FUNDRAISER' => 'FundRaiser',
        "PARTNER" => "Partner",
    ];
    //['deposit', 'contribution', 'withdrawal']

    const TRANSACTION_TYPE  = [
        'DEPOSIT' => 'deposit',
        'CONTRIBUTION' => 'contribution',
        'WITHDRAWAL' => 'withdrawal',
    ];

    //['mobile_money', 'cash', 'wallet']

    const PAYMENT_METHOD = [
        'MOBILE_MONEY' => 'mobile_money',
        'CASH' => 'cash',
        'WALLET' => 'wallet',
    ];

    //fundraiser group status ['active',  'cancelled', 'completed']

    const FUNDRAISER_GROUP_STATUS = [
        'ACTIVE' => 'active',
        'CANCELLED' => 'cancelled',
        'COMPLETED' => 'completed',
    ];

    //Group status ['active',  'cancelled', 'completed']
    //['active', 'closed', 'cancelled', 'completed']

    const GROUP_STATUSES = [
        'ACTIVE' => 'active',
        'CLOSED' => 'closed',
        'CANCELLED' => 'cancelled',
        'COMPLETED' => 'completed',
    ];

    //GENDER
    const GENDERS = [
        'MALE' => 'male',
        'FEMALE' => 'female',
    ];

    //TRANSACTION STATUS
    const TRANSACTION_STATUSES = [
        'PENDING' => 'pending',
        'COMPLETED' => 'completed',
        'CANCELLED' => 'cancelled',
        'FAILED'=>'failed'
    ];

       //ssentezo status
       const PENDING = 'PENDING';
       const FAILED = 'FAILED';
       const SUCCEEDED = 'SUCCEEDED';


       const VIEWS = [
        'ACCOUNTS' => 'Accounts',
        'GROUPS' => 'Group',
        'FUNDRAISERS' => 'Fundraisers',
    ];

    const NEXT_OF_KIN_RELATIONSHIPS = [
        'FATHER' => 'Father',
        'MOTHER' => 'Mother',
        'BROTHER' => 'Brother',
        'SISTER' => 'Sister',
        'SON' => 'Son',
        'DAUGHTER' => 'Daughter',
        'UNCLE' => 'Uncle',
        'AUNT' => 'Aunt',
        'GRANDFATHER' => 'Grandfather',
        'GRANDMOTHER' => 'Grandmother',
        'SON_IN_LAW' => 'Son in Law',
        'DAUGHTER_IN_LAW' => 'Daughter in Law',
        'OTHER' => 'Other',
    ];

    const REGISTER_OPTIONS = [
      'Email' => 'email',
      'Phone' => 'phone',
      'Both' => 'both',
      'OTHER' => 'other',
    ];

    const DEFAULT_CURRENCY = 'UGX';
    const DEFAULT_MEMBER_BALANCE = 0;
    const MAXIMUM_ACCOUNT_BALANCE = 1000000;
    const MINIMUM_ACCOUNT_BALANCE = 1000;
    const DEFAULT_ACCOUNT_BALANCE = 1000;
    const MINIMUM_CONTRIBUTION = 1000;


    const PAYMENT_PROVIDERS = [
        'MTN' => 'MTN',
        'AIRTEL' => 'AIRTEL',
    ];

    const TARGET_TYPES = [
        'LONG_TERM' => 'Long Term',
        'SHORT_TERM' => 'Short Term',
    ];


    const COMPANY_LOAN_FEE = 1000;

    //loan statuses
    const LOAN_STATUSES = [
        'PAID' => 'paid',
        'ACTIVE' => 'active',
        'OVERDUE' => 'overdue',
        'OVERPAID' => 'overpaid',
        'CANCELLED' => 'cancelled',
    ];

    const NETWORKS = [
        'MTN' => 'MTN',
        'AIRTEL' => 'AIRTEL',
        'WALLET' => 'WALLET',
    ];
}
