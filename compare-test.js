
// import http from 'k6/http';
// import { check, sleep } from 'k6';

// const vus_count = parseInt(__ENV.VUS || '20');
// const isControlled = (__ENV.MODE === 'controlled' || __ENV.MODE === 'after');

// export let options = {
//   scenarios: {
//     thread_pool_simulation: {
//       executor: 'per-vu-iterations',
//       vus: vus_count,
//       iterations: isControlled ? 1 : 1,
//       maxDuration: '1m',
//     },
//   },
// };

// // قائمة المنافذ التي سنفتحها (يجب تشغيل سيرفر في تيرمنال مستقل لكل بورت)
//  const ports = [ 8001, 8002, 8003, 8004];

// export function setup() {
//   // نستخدم البورت الرئيسي 8000 لعملية التجهيز (Setup)
//   const setupUrl = 'http://127.0.0.1:8000';
  
//   const loginRes = http.post(`${setupUrl}/api/register`, JSON.stringify({
//     name: "Performance Tester",
//     email: `test-${Date.now()}@test.com`,
//     password: "Password123!",
//     password_confirmation: "Password123!"
//   }), { headers: { 'Content-Type': 'application/json' } });

//   const token = loginRes.json().token;

//   // إعادة ضبط المخزون ليكون 100 قبل البدء
//   http.post(`${setupUrl}/api/benchmark/reset`, JSON.stringify({ product_id: 1 }), {
//     headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' }
//   });

//   return { token };
// }

// export default function (data) {
//   // اختيار بورت عشوائي لكل طلب لمحاكاة التوازي الحقيقي (Parallelism)
//   const randomPort = ports[Math.floor(Math.random() * ports.length)];
//   const baseUrl = `http://127.0.0.1:${randomPort}`;

//   const headers = {
//     'Authorization': `Bearer ${data.token}`,
//     'Content-Type': 'application/json'
//   };

//   const mode = isControlled ? 'after' : 'before';
  
//   const res = http.post(
//     `${baseUrl}/api/benchmark/checkout/${mode}`, 
//     JSON.stringify({ product_id: 1 }), 
//     { headers }
//   );

//   let body;
//   try { body = res.json(); } catch (e) { body = {}; }
  
//   const stockAfter = (body.stock_after !== undefined) ? body.stock_after : 'N/A';

//   if (isControlled) {
//     check(res, {
//       'Controlled: Stock Safe': (r) => stockAfter >= 0,
//     });
//   } else {
//     check(res, {
//       'Uncontrolled: Request Processed': (r) => r.status === 200,
//     });
//   }

//   console.log(
//     `[VU-${__VU} | Port-${randomPort}] ` +
//     `MODE: ${mode.toUpperCase()} | ` +
//     `Stock: ${stockAfter} | ` +
//     `Status: ${body.status}`
//   );

//   sleep(isControlled ? 0.1 : 0.01);            
// }


// // UPDATE products SET stock = 100 WHERE id = 1;

// // php artisan queue:work

// //http://127.0.0.1:8000/telescope/views

// // http://localhost:8000/api/report/sync-bad-way
// // http://127.0.0.1:8000/api/generate-inventory-report
// // php artisan db:seed --class=OrderSeeder





////////////////////////////////////////////////////////////////////////////////////



// import http from 'k6/http';
// import { check, sleep } from 'k6';

// const vus_count = parseInt(__ENV.VUS || '20');
// const isControlled = (__ENV.MODE === 'controlled' || __ENV.MODE === 'after');

// export let options = {
//   scenarios: {
//     thread_pool_simulation: {
//       executor: 'per-vu-iterations',
//       vus: vus_count,
//       iterations: isControlled ? 1 : 1,
//       maxDuration: '1m',
//     },
//   },
// };

// const ports = [ 8001, 8002, 8003, 8004];

// export function setup() {
//   const setupUrl = 'http://127.0.0.1:8000';
  
//   const loginRes = http.post(`${setupUrl}/api/register`, JSON.stringify({
//     name: "Performance Tester",
//     email: `test-${Date.now()}@test.com`,
//     password: "Password123!",
//     password_confirmation: "Password123!"
//   }), { headers: { 'Content-Type': 'application/json' } });

//   const token = loginRes.json().token;

//   http.post(`${setupUrl}/api/benchmark/reset`, JSON.stringify({ product_id: 1 }), {
//     headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' }
//   });

//   return { token };
// }

// export default function (data) {
//   const mode = isControlled ? 'after' : 'before';
//   let selectedPort;

//   // --- تطبيق منطق التوزيع المزدوج ---
//   if (isControlled) {
//     // خوارزمية Round Robin للـ After (توزيع دوري منظم)
//     const portIndex = (__VU - 1) % ports.length;
//     selectedPort = ports[portIndex];
    
//     // تأخير تسلسلي لضمان ظهور اللوغ بشكل مرتب 8000, 8001...
//     sleep((__VU - 1) * 0.05);
//   } else {
//     // خوارزمية التوزيع العشوائي للـ Before (محاكاة عدم التنظيم)
//     selectedPort = ports[Math.floor(Math.random() * ports.length)];
//   }

//   const baseUrl = `http://127.0.0.1:${selectedPort}`;

//   const headers = {
//     'Authorization': `Bearer ${data.token}`,
//     'Content-Type': 'application/json'
//   };

//   const res = http.post(
//     `${baseUrl}/api/benchmark/checkout/${mode}`, 
//     JSON.stringify({ product_id: 1 }), 
//     { headers }
//   );

//   let body;
//   try { body = res.json(); } catch (e) { body = {}; }
  
//   const stockAfter = (body.stock_after !== undefined) ? body.stock_after : 'N/A';

//   if (isControlled) {
//     check(res, {
//       'Controlled: Stock Safe': (r) => stockAfter >= 0,
//     });
//   } else {
//     check(res, {
//       'Uncontrolled: Request Processed': (r) => r.status === 200,
//     });
//   }

//   console.log(
//     `[VU-${__VU} | Port-${selectedPort}] ` +
//     `MODE: ${mode.toUpperCase()} | ` +
//     `Stock: ${stockAfter} | ` +
//     `Status: ${body.status}`
//   );
// }

// UPDATE products SET stock = 100 WHERE id = 1;
// php artisan queue:work
// http://127.0.0.1:8000/telescope/views
// http://localhost:8000/api/report/sync-bad-way
// http://127.0.0.1:8000/api/generate-inventory-report
// php artisan db:seed --class=OrderSeeder

//////////////////////////////////////////////////////////////////////



import http from 'k6/http';
import { check, sleep } from 'k6';
import { Counter } from 'k6/metrics'; // استيراد العدادات

const vus_count = parseInt(__ENV.VUS || '20');
const isControlled = (__ENV.MODE === 'controlled' || __ENV.MODE === 'after');

const portStats = {
  8001: new Counter('port_8001_count'),
  8002: new Counter('port_8002_count'),
  8003: new Counter('port_8003_count'),
  8004: new Counter('port_8004_count'),
};

export let options = {
  scenarios: {
    thread_pool_simulation: {
      executor: 'per-vu-iterations',
      vus: vus_count,
      iterations: isControlled ? 1 : 1,
      maxDuration: '2m',
    },
  },
};

const ports = [8001, 8002, 8003, 8004];


// export function setup() {
//   console.log("--- Resetting Rate Limit for all ports ---");
  
//   for (const port of ports) {
//     try {
//       http.post(`http://127.0.0.1:${port}/api/benchmark/reset-limit`);
//       console.log(`Reset successful for port: ${port}`);
      
//       sleep(0.1); 
//     } catch (e) {
//       console.log(`Failed to reset port ${port}: ${e}`);
//     }
//   }

//   sleep(1); 
//   console.log("--- Setup Complete for all ports, Starting VUs ---");
// }

export default function (data) {
  const mode = isControlled ? 'after' : 'before';
  let selectedPort;

  if (isControlled) {
    const portIndex = (__VU - 1) % ports.length;
    selectedPort = ports[portIndex];

    sleep((__VU - 1) * 0.05);
  } else {
    selectedPort = ports[Math.floor(Math.random() * ports.length)];
  }

   portStats[selectedPort].add(1);

  const baseUrl = `http://127.0.0.1:${selectedPort}`;

  
    
const headers = {
    'X-Forwarded-For': `1.1.1.${__VU}`, 
    'Content-Type': 'application/json'
};

  const res = http.post(
    `${baseUrl}/api/benchmark/checkout/${mode}`, 
    JSON.stringify({ product_id: 1 }), 
    { headers }
  );
  let body;
  try { body = res.json(); } catch (e) { body = {}; }
  
  const stockAfter = (body.stock_after !== undefined) ? body.stock_after : 'N/A';
  const serverStatus = body.status || (res.status === 429 ? 'RATE_LIMIT_REACHED' : 'UNKNOWN');

  if (isControlled) {
    check(res, {
      'Controlled: Stock Safe or Rate Limited': (r) => r.status === 200 ,
    });
  } else {
    check(res, {
      'Uncontrolled: Request Processed': (r) => r.status === 200,
    });
  }

  console.log(
    `[VU-${__VU} | Port-${selectedPort}] ` +
    `MODE: ${mode.toUpperCase()} | ` +
    `Stock: ${stockAfter} | ` +
    `HTTP-Status: ${res.status} | ` +
    `Response-Status: ${serverStatus}`
  );


}


// UPDATE products SET stock = 100 WHERE id = 1;
// php artisan queue:work
// http://127.0.0.1:8000/telescope/views
// http://localhost:8000/api/report/sync-bad-way
// http://127.0.0.1:8000/api/generate-inventory-report
// php artisan db:seed --class=OrderSeeder

//php artisan config:cache
//php artisan route:cache       imprtant for performance testing to ensure all routes are cached and optimized











// import http from 'k6/http';
// import { check, sleep } from 'k6';

// const vus_count = parseInt(__ENV.VUS || '20');
// const isControlled = (__ENV.MODE === 'controlled' || __ENV.MODE === 'after');

// export let options = {
//   scenarios: {
//     thread_pool_simulation: {
//       executor: 'per-vu-iterations',
//       vus: vus_count,
//       iterations: 1,
//       maxDuration: '1m',
//     },
//   },
// };

// const ports = [8001, 8002, 8003, 8004];

// export function setup() {
//   // تم تحويله إلى 8001 لضمان الاتصال بأحد الخوادم الأربعة المشغلة فعلياً
//   const setupUrl = 'http://127.0.0.1:8001'; 
  
//   const loginRes = http.post(`${setupUrl}/api/register`, JSON.stringify({
//     name: "Performance Tester",
//     email: `test-${Date.now()}@test.com`,
//     password: "Password123!",
//     password_confirmation: "Password123!"
//   }), { headers: { 'Content-Type': 'application/json' } });

//   const token = loginRes.json().token;
//   return { token };
// }

// export default function (data) {
//   const mode = isControlled ? 'after' : 'before';
//   let selectedPort;

//   if (isControlled) {
//     const portIndex = (__VU - 1) % ports.length;
//     selectedPort = ports[portIndex];
//     sleep((__VU - 1) * 0.05); // تأخير تسلسلي منظم للـ Thread Pool
//   } else {
//     selectedPort = ports[Math.floor(Math.random() * ports.length)]; // فوضى عشوائية للـ Before
//   }

//   const baseUrl = `http://127.0.0.1:${selectedPort}`;

//   const headers = {
//     'Authorization': `Bearer ${data.token}`,
//     'Content-Type': 'application/json'
//   };

//   const res = http.post(
//     `${baseUrl}/api/benchmark/checkout/${mode}`, 
//     JSON.stringify({ product_id: 1 }), 
//     { headers }
//   );

//   let body;
//   try { body = res.json(); } catch (e) { body = {}; }
  
//   const stockAfter = (body.stock_after !== undefined) ? body.stock_after : 'N/A';
//   const serverStatus = body.status || (res.status === 429 ? 'RATE_LIMIT_REACHED' : 'UNKNOWN');

//   if (isControlled) {
//     check(res, {
//       'Controlled: Stock Safe or Rate Limited': (r) => r.status === 200 || r.status === 429,
//     });
//   } else {
//     check(res, {
//       'Uncontrolled: Request Processed': (r) => r.status === 200,
//     });
//   }

//   console.log(
//     `[VU-${__VU} | Port-${selectedPort}] ` +
//     `MODE: ${mode.toUpperCase()} | ` +
//     `Stock: ${stockAfter} | ` +
//     `HTTP-Status: ${res.status} | ` +
//     `Response-Status: ${serverStatus}`
//   );
// }