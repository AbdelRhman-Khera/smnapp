<?php

return [
    'customer' => [
        'title' => 'Maintenance Request Update',
        'technician_assigned' => 'A technician has been assigned to your request #:id',
        'request_completed' => 'Your request #:id has been completed successfully!',
        'technician_on_the_way' => 'Technician is on the way for request #:id',
        'in_progress' => 'The request #:id is now in progress',
        'waiting_for_payment' => 'The request #:id is waiting for payment.',
        'payment_confirmed' => 'Payment confirmed for request #:id',
        'installation_completed' => 'The installation request #:id has been successfully completed.',
        'device_withdrawal_requested' => 'A technician requested to withdraw a device for workshop inspection. Request #:id',
        'device_withdrawal_follow_up_created' => 'A follow-up maintenance request #:id was created for your withdrawn device.',
    ],
    'technician' => [
        'title' => 'New Maintenance Assignment',
        'new_request' => 'A new maintenance request #:id has been assigned to you.',
        'request_updated' => 'The request #:id has been updated.',
        'freelancer_request_available' => 'A new maintenance request #:id is available for you.',
        'device_withdrawal_approved' => 'Customer approved device withdrawal request #:id.',
        'device_withdrawal_rejected' => 'Customer rejected device withdrawal request #:id.',
        'device_withdrawal_assigned' => 'New device withdrawal delivery request #:id.',
        'device_withdrawal_assigned_by_branch' => 'A branch employee assigned device withdrawal request #:id to you.',
        'payout_approved' => 'Your payout request #:id has been approved. :amount SAR will be transferred to you.',
        'payout_rejected' => 'Your payout request #:id has been rejected. The amount is back in your wallet balance.',
    ],
];
