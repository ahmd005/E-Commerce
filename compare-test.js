
import http from 'k6/http';
import { check, sleep } from 'k6';

const vus_count = parseInt(__ENV.VUS || '20');
const isControlled = (__ENV.MODE === 'controlled' || __ENV.MODE === 'after');

export let options = {
  scenarios: {
    thread_pool_simulation: {
      executor: 'per-vu-iterations',
      vus: vus_count,
      iterations: isControlled ? 1 : 1,
      maxDuration: '1m',
    },
  },
};

const ports = [8000, 8001, 8002, 8003, 8004, 8005, 8006, 8007, 8008, 8009];

export function setup() {
  const setupUrl = 'http://127.0.0.1:8000';
  
  const loginRes = http.post(`${setupUrl}/api/register`, JSON.stringify({
    name: "Performance Tester",
    email: `test-${Date.now()}@test.com`,
    password: "Password123!",
    password_confirmation: "Password123!"
  }), { headers: { 'Content-Type': 'application/json' } });

  const token = loginRes.json().token;

  http.post(`${setupUrl}/api/benchmark/reset`, JSON.stringify({ product_id: 1 }), {
    headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' }
  });

  return { token };
}

export default function (data) {
  const randomPort = ports[Math.floor(Math.random() * ports.length)];
  const baseUrl = `http://127.0.0.1:${randomPort}`;

  const headers = {
    'Authorization': `Bearer ${data.token}`,
    'Content-Type': 'application/json'
  };

  const mode = isControlled ? 'after' : 'before';
  
  const res = http.post(
    `${baseUrl}/api/benchmark/checkout/${mode}`, 
    JSON.stringify({ product_id: 1 }), 
    { headers }
  );

  let body;
  try { body = res.json(); } catch (e) { body = {}; }
  
  const stockAfter = (body.stock_after !== undefined) ? body.stock_after : 'N/A';

  if (isControlled) {
    check(res, {
      'Controlled: Stock Safe': (r) => stockAfter >= 0,
    });
  } else {
    check(res, {
      'Uncontrolled: Request Processed': (r) => r.status === 200,
    });
  }

  console.log(
    `[VU-${__VU} | Port-${randomPort}] ` +
    `MODE: ${mode.toUpperCase()} | ` +
    `Stock: ${stockAfter} | ` +
    `Status: ${body.status}`
  );

  sleep(isControlled ? 0.1 : 0.01);            
}


// UPDATE products SET stock = 100 WHERE id = 1;
// php artisan queue:work