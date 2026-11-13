import http from 'k6/http';
import { check } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://app:8080';
const METHOD = (__ENV.SCENARIO_METHOD || 'GET').toUpperCase();
const PATH = __ENV.SCENARIO_PATH || '/api/bare';
const EXPECTED_STATUS = parseInt(__ENV.SCENARIO_STATUS || '200', 10);
const PAYLOAD = __ENV.SCENARIO_BODY || null;
const VUS = parseInt(__ENV.SCENARIO_VUS || '100', 10);
const WARMUP_SEC = parseInt(__ENV.SCENARIO_WARMUP || '3', 10);
const DURATION_SEC = parseInt(__ENV.SCENARIO_DURATION || '15', 10);

export const options = {
  stages: [
    { duration: `${WARMUP_SEC}s`, target: VUS },
    { duration: `${DURATION_SEC}s`, target: VUS },
    { duration: '1s', target: 0 },
  ],
  thresholds: {
    http_req_failed: ['rate<0.05'],
  },
  summaryTrendStats: ['avg', 'min', 'med', 'max', 'p(90)', 'p(95)', 'p(99)'],
};

const params = {
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
};

export default function () {
  let res;
  if (METHOD === 'POST') {
    res = http.post(`${BASE_URL}${PATH}`, PAYLOAD, params);
  } else if (METHOD === 'PUT') {
    res = http.put(`${BASE_URL}${PATH}`, PAYLOAD, params);
  } else if (METHOD === 'PATCH') {
    res = http.patch(`${BASE_URL}${PATH}`, PAYLOAD, params);
  } else if (METHOD === 'DELETE') {
    res = http.del(`${BASE_URL}${PATH}`, PAYLOAD, params);
  } else {
    res = http.get(`${BASE_URL}${PATH}`, params);
  }

  check(res, {
    'expected status': (r) => r.status === EXPECTED_STATUS,
  });
}
