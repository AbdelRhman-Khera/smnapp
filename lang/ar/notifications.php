<?php

return [
    'customer' => [
        'title' => 'تحديث طلب الصيانة',
        'technician_assigned' => 'تم تعيين فني لطلبك #:id',
        'request_completed' => 'تم إكمال طلبك #:id بنجاح!',
        'technician_on_the_way' => 'الفني في الطريق للطلب #:id',
        'in_progress' => 'تم بدء العمل على الطلب #:id',
        'waiting_for_payment' => 'تم الانتهاء من الخدمة. الطلب #:id في انتظار الدفع.',
        'payment_confirmed' => 'تم تأكيد الدفع للطلب #:id',
        'installation_completed' => 'تم الانتهاء من طلب التركيب رقم #:id بنجاح.',
        'device_withdrawal_requested' => 'طلب الفني سحب جهاز للفحص في الورشة. طلب رقم #:id',
        'device_withdrawal_follow_up_created' => 'تم إنشاء طلب متابعة رقم #:id لجهازك المسحوب.',
    ],
    'technician' => [
        'title' => 'مهمة صيانة جديدة',
        'new_request' => 'تم تعيين طلب صيانة جديد #:id لك.',
        'request_updated' => 'تم تحديث طلب الصيانة #:id.',
        'freelancer_request_available' => 'طلب صيانة جديد #:id متاح لك.',
        'device_withdrawal_approved' => 'وافق العميل على طلب سحب الجهاز رقم #:id.',
        'device_withdrawal_rejected' => 'رفض العميل طلب سحب الجهاز رقم #:id.',
        'device_withdrawal_assigned' => 'تم تحويل طلب تسليم جهاز مسحوب رقم #:id إليك.',
        'device_withdrawal_assigned_by_branch' => 'قام موظف الفرع بتحويل طلب سحب الجهاز رقم #:id إليك.',
        'payout_approved' => 'تمت الموافقة على طلب صرف المستحقات رقم #:id. سيتم تحويل :amount ريال لك.',
        'payout_rejected' => 'تم رفض طلب صرف المستحقات رقم #:id. تمت إعادة المبلغ إلى رصيد محفظتك.',
    ],
];
