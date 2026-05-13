import http from 'k6/http';
import { check, sleep } from 'k6';

export default function () {
    // جلب المتغيرات من سطر الأوامر
    const mode = __ENV.MODE || 'safe';
    const productId = __ENV.PRODUCT_ID || 1;
    const quantity = __ENV.QUANTITY || 1;

    const url = 'http://localhost:8000/api/checkout'; // تأكد من أن الرابط صحيح
    
    const payload = JSON.stringify({
        items: [
            { product_id: productId, quantity: quantity }
        ]
    });

    const params = {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            // 'Authorization': 'Bearer YOUR_TOKEN' // إذا كان يتطلب توثيقاً
        },
    };

    const res = http.post(url, payload, params);

    // التحقق من نجاح العملية
    check(res, {
        'status is 200': (r) => r.status === 200,
    });

    sleep(0.1); // انتظار بسيط بين الطلبات
}