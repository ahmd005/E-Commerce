content = """# مخطط ERD لمشروع نظام إدارة مواعيد المركز الطبي

هذا المخطط مصمم خصيصاً ليتناسب مع متطلبات مشروعك باستخدام إطار عمل **Laravel**، مع مراعاة نظام الصلاحيات (Roles and Permissions) ومرونة جداول الدوام.

---

## 1. الهيكلية الأساسية للجداول (Database Schema)

### أ. جدول المستخدمين `users`
بما أنك ستستخدم Laravel مع نظام الأدوار، فإن هذا الجدول هو الأساس.
- `id`: (Primary Key)
- `name`: اسم المستخدم
- `email`: البريد الإلكتروني
- `password`: كلمة المرور
- يتم ربط الأدوار (طبيب، موظف استقبال، مدير) باستخدام جداول حزمة Spatie التلقائية.

### ب. جدول العيادات `clinics`
- `id`: (Primary Key)
- `name`: اسم العيادة (مثلاً: عيادة القلب، عيادة الأطفال).
- `location`: (اختياري) موقع العيادة داخل المركز.

### ج. جدول فترات الدوام `doctor_schedules` (الجوهر)
هذا الجدول يسمح للطبيب بالعمل في عيادة واحدة، ويسمح بوجود أكثر من طبيب في نفس العيادة في أوقات مختلفة، ويدعم فترات متعددة لنفس الطبيب في نفس اليوم.
- `id`: (Primary Key)
- `doctor_id`: (Foreign Key) يربط بجدول المستخدمين.
- `clinic_id`: (Foreign Key) يربط بجدول العيادات.
- `day_of_week`: يوم الأسبوع (Enum: Sunday, Monday, ...).
- `start_time`: وقت بداية الفترة.
- `end_time`: وقت نهاية الفترة.
- `status`: حالة الفترة (نشط/غير نشط).

### د. جدول طلبات التبديل `shift_swap_requests`
لمعالجة طلبات تبديل الدوام بين الأطباء في نفس العيادة.
- `id`: (Primary Key)
- `requester_doctor_id`: (Foreign Key) الطبيب الذي يطلب التبديل.
- `requested_doctor_id`: (Foreign Key) الطبيب الموجه له الطلب.
- `requester_schedule_id`: (Foreign Key) الفترة الزمنية التي يريد الطبيب التنازل عنها.
- `requested_schedule_id`: (Foreign Key) الفترة الزمنية التي يريد الطبيب الاستحواذ عليها.
- `status`: حالة الطلب (Pending, Approved, Rejected).
- `reason`: سبب التبديل (اختياري).

### هـ. جدول المواعيد `appointments`
- `id`: (Primary Key)
- `patient_name`: اسم المريض (أو ربطه بجدول مرضى مستقل).
- `doctor_id`: (Foreign Key) الطبيب المعالج.
- `clinic_id`: (Foreign Key) العيادة.
- `appointment_date`: تاريخ الموعد.
- `start_time`: وقت البداية.
- `end_time`: وقت النهاية.
- `status`: حالة الموعد (Scheduled, Completed, Cancelled).

---

## 2. العلاقات (Relationships)

1.  **طبيب إلى فترات دوام (1:N):** الطبيب الواحد له عدة فترات دوام في أيام وأوقات مختلفة.
2.  **عيادة إلى فترات دوام (1:N):** العيادة الواحدة تستضيف عدة أطباء في فترات زمنية مختلفة.
3.  **تبديل الدوام (Self-Referencing):** علاقة تربط طبيبين ببعضهما من خلال فترات دوام مسجلة مسبقاً.

---

## 3. ملاحظات تنفيذية في Laravel

### الصلاحيات (Roles & Permissions)
- استخدم حزمة **Spatie Laravel-Permission**.
- **Role: Doctor**: لديه صلاحية `view-own-schedule`, `request-shift-swap`.
- **Role: Admin**: لديه صلاحية `manage-all-schedules`, `approve-shift-swap`.

### المنطق البرمجي (Business Logic) للتبديل
عند الموافقة على طلب التبديل (`Approved`)، يجب تنفيذ عملية `Transaction` في قاعدة البيانات تقوم بتحديث:
1. `doctor_id` في السجل الأول ليصبح الطبيب الثاني.
2. `doctor_id` في السجل الثاني ليصبح الطبيب الأول.

### التحقق (Validation)
يجب التأكد برمجياً قبل حفظ أي فترة دوام جديدة من عدم وجود تداخل (Overlap):
- لنفس الطبيب في أي عيادة أخرى بنفس الوقت.
- لنفس العيادة مع طبيب آخر في نفس الوقت.

---

## 4. رسم توضيحي مبسط (Mermaid Diagram)

```mermaid
erDiagram
    CLINIC ||--o{ DOCTOR_SCHEDULE : "hosts"
    USER ||--o{ DOCTOR_SCHEDULE : "has"
    USER ||--o{ SHIFT_SWAP_REQUEST : "initiates"
    USER ||--o{ SHIFT_SWAP_REQUEST : "receives"
    DOCTOR_SCHEDULE ||--o{ APPOINTMENT : "contains"
    
    USER {
        int id
        string name
        string role
    }
    CLINIC {
        int id
        string name
    }
    DOCTOR_SCHEDULE {
        int id
        int doctor_id
        int clinic_id
        enum day_of_week
        time start_time
        time end_time
    }
    SHIFT_SWAP_REQUEST {
        int id
        int requester_doctor_id
        int requested_doctor_id
        int requester_schedule_id
        int requested_schedule_id
        string status
    }