import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8000';
const TOKEN = __ENV.TOKEN || '';
const MODE = __ENV.MODE || 'before';
const PRODUCT_ID = __ENV.PRODUCT_ID || '1';
const QUANTITY = __ENV.QUANTITY || '1';

export const options = {
  scenarios: {
    high_pressure: {
      executor: 'constant-vus',
      vus: Number(__ENV.VUS || 1000),
      duration: __ENV.DURATION || '15s',
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.2'],
    http_req_duration: ['p(95)<1000'],
  },
};

export default function () {
  const url = `${BASE_URL}/api/test-stock`;
  const payload = JSON.stringify({
    product_id: Number(PRODUCT_ID),
    quantity: Number(QUANTITY),
    mode: MODE,
  });

  const params = {
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      ...(TOKEN ? { Authorization: `Bearer ${TOKEN}` } : {}),
    },
  };

  const res = http.post(url, payload, params);

  check(res, {
    'status is 200': (r) => r.status === 200,
    'has success true': (r) => {
      try {
        const body = r.json();
        return body.success === true;
      } catch (e) {
        return false;
      }
    },
  });

  sleep(0.1);
}