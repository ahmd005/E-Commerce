import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
    stages: [
        { duration: '15s', target: 30 },  // 30 مستخدم متزامن موزعين على 4 منافذ
        { duration: '30s', target: 30 },  
        { duration: '10s', target: 0 },   
    ],
    thresholds: {
        http_req_failed: ['rate<0.01'],   // نهدف لنسبة فشل 0%
        http_req_duration: ['p(95)<1000'], 
    },
};

const ports = ['8000', '8001', '8002', '8003'];

function generateRandomToken() {
    return 'token-' + Math.random().toString(36).substring(2, 15);
}

export default function () {
    const randomPort = ports[Math.floor(Math.random() * ports.length)];
    const uniqueToken = generateRandomToken(); 
    
    // 💡 تصحيح الرابط بإضافة /index بناءً على ملف الـ api.php الخاص بك
    const url = `http://127.0.0.1:${randomPort}/api/v1/cart/redis/index`; 
    
    const params = {
        headers: {
            'Content-Type': 'application/json',
            'X-Cart-Token': uniqueToken, 
        },
    };

    const res = http.get(url, params);

    check(res, {
        'status is 200': (r) => r.status === 200,
    });

    sleep(Math.random() * 1 + 1); 
}