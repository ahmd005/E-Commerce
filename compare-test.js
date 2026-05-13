import http from 'k6/http';
import { check, fail } from 'k6';
import { Counter } from 'k6/metrics';

const baseHost = __ENV.HOST || 'http://127.0.0.1';
const ports = (__ENV.PORTS || '8000').split(',').map((value) => value.trim()).filter(Boolean);
const users = parseInt(__ENV.USERS || '100', 10);
const mode = (__ENV.MODE || 'safe').toLowerCase();
const requestedProductId = parseInt(__ENV.PRODUCT_ID || '1', 10);
const stock = parseInt(__ENV.STOCK || '10', 10);
const quantity = parseInt(__ENV.QUANTITY || '1', 10);
const resetEnabled = (__ENV.RESET || 'true').toLowerCase() !== 'false';
const simulateRace = (__ENV.SIMULATE_RACE || 'true').toLowerCase() !== 'false';
const delayMs = parseInt(__ENV.DELAY_MS || '50', 10);

export const options = {
  scenarios: {
    checkout_burst: {
      executor: 'per-vu-iterations',
      vus: users,
      iterations: 1,
      maxDuration: '10m',
    },
  },
};

const purchaseSuccess = new Counter('purchase_success');
const purchaseFail = new Counter('purchase_fail');

// 1. تعديل دالة الطباعة لتقبل قيمة المخزون (Observed Stock)
function logApiLine(name, res, observedStock = 'N/A', observedBalance = 'N/A') {
  const durationMs = res && res.timings ? res.timings.duration.toFixed(2) : 'n/a';
  console.log(
    `[api] ${name} | Status=${res.status} | Stock_Read=${observedStock} | Balance_Read=${observedBalance} | Duration=${durationMs}ms`
  );
}

function pickBaseUrl() {
  const index = (__VU - 1) % ports.length;
  return `${baseHost}:${ports[index]}`;
}

function primaryBaseUrl() {
  return `${baseHost}:${ports[0]}`;
}

export function setup() {
  let productId = requestedProductId;
  const setupBaseUrl = primaryBaseUrl();

  if (resetEnabled) {
    const resetRes = http.post(
      `${setupBaseUrl}/api/benchmark/bootstrap-stock`,
      JSON.stringify({
        product_id: productId,
        stock,
        name: `Stress Product ${productId}`,
        price: 100,
        description: 'Benchmark product for concurrency testing',
      }),
      { headers: { 'Content-Type': 'application/json' } }
    );

    logApiLine('bootstrap-stock', resetRes);

    if (!check(resetRes, { 'bootstrap stock returns success': (r) => r.status === 200 })) {
      fail(`Stock bootstrap failed`);
    }

    const bootstrappedProductId = resetRes.json('product_id');
    if (bootstrappedProductId) {
      productId = Number(bootstrappedProductId);
    }
  }

  return { productId, stock, quantity, mode, users };
}

export default function (data) {
  const endpoint = data.mode === 'unsafe' ? '/api/checkout/unsafe' : '/api/checkout';
  const baseUrl = pickBaseUrl();
  const payload = {
    items: [
      {
        product_id: data.productId,
        quantity: data.quantity,
      },
    ],
    simulate_race: simulateRace,
    delay_ms: delayMs,
  };

  const res = http.post(
    `${baseUrl}${endpoint}`,
    JSON.stringify(payload),
    { headers: { 'Content-Type': 'application/json' } }
  );

  // 2. استخراج قيمة المخزون من استجابة الـ API
  let observedStock = 'unknown';
  let observedBalance = 'unknown';
  try {
    const responseBody = res.json();
    observedStock = responseBody.observed_stock !== undefined ? responseBody.observed_stock : 'N/A';
    observedBalance = responseBody.observed_balance !== undefined ? responseBody.observed_balance : 'N/A';
  } catch (error) {
    observedStock = 'parse_error';
    observedBalance = 'parse_error';
  }

  const port = baseUrl.split(':').pop();
  // 3. تمرير القيمة المستخرجة لدالة الطباعة
  logApiLine(`checkout mode=${data.mode} vu=${__VU} port=${port}`, res, observedStock, observedBalance);

  check(res, {
    'checkout request finished': (r) => r.status === 200 || r.status === 500 || r.status === 422 || r.status === 409,
  });

  if (res.status === 200) {
    purchaseSuccess.add(1);
  } else {
    purchaseFail.add(1);
  }
}

export function teardown(data) {
  const productsRes = http.get(`${primaryBaseUrl()}/api/products`);
  
  let finalStock = 'unknown';
  try {
    const products = productsRes.json();
    const productsArray = Array.isArray(products) ? products : Object.values(products);
    const product = productsArray.find((item) => Number(item.id) === data.productId);
    finalStock = product ? product.stock : 'missing';
  } catch (error) {
    finalStock = 'unreadable';
  }

  logApiLine('products-final', productsRes, finalStock, 'N/A');
  console.log(`summary mode=${data.mode} users=${data.users} final_stock=${finalStock}`);
}

export function handleSummary(data) {
  const successCount = data.metrics.purchase_success
    ? data.metrics.purchase_success.values.count
    : 0;
  const failCount = data.metrics.purchase_fail
    ? data.metrics.purchase_fail.values.count
    : 0;

  return {
    stdout: `\nsummary purchases_success=${successCount} purchases_failed=${failCount}\n`,
  };
}