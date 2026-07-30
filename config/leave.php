<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Which employment statuses may file leave
    |--------------------------------------------------------------------------
    |
    | IDs from the `statuses` table:
    |
    |     1  Permanent        5  Elective
    |     2  Casual           6  Job Order
    |     3  Co-terminous     7  Part-time/JO
    |     4  Contractual
    |
    | This existed as a bare `emp_status == 1` repeated in four different files
    | — the dashboard's leave panel, the leave status page, the leave PDF, and
    | the leave-credit page — which is why adding Casual meant finding all four.
    | It lives here now so the next status is one line, not another hunt.
    |
    | WHY CASUAL BELONGS HERE
    | Under the CSC Omnibus Rules on Leave, leave credits accrue to officials
    | and employees whether their appointment is permanent, temporary, casual or
    | co-terminous. A casual employee earning no leave was a bug in this system,
    | not a policy of the LGU.
    |
    | WHAT IS DELIBERATELY EXCLUDED
    | Job Order (6) and Part-time/JO (7). Those engagements carry no
    | employer-employee relationship with the LGU and no leave entitlement, so
    | showing them a leave form would be offering something that cannot be
    | granted.
    |
    | Co-terminous (3), Contractual (4) and Elective (5) are left OUT for now
    | only because they were not asked for — by the CSC rules above they very
    | likely qualify. Confirm with HR and add the IDs here; nothing else needs
    | to change.
    |
    */

    'eligible_statuses' => [
        1, // Permanent
        2, // Casual
    ],

];
