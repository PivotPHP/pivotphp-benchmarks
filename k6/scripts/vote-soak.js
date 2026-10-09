import http from 'k6/http';
import { check } from 'k6';
import { Trend } from 'k6/metrics';

const responseTimeDrift = new Trend('response_time_drift', true);

export const options = {
  stages: [
    { duration: '30s', target: 100 },  // ramp-up
    { duration: '5m', target: 100 },   // sustentar (estabilidade)
    { duration: '30s', target: 0 },    // ramp-down
  ],
  thresholds: {
    'http_req_duration': ['p(95)<300', 'p(99)<600'],
    'http_req_failed': ['rate<0.01'],
  },
  summaryTrendStats: ['avg', 'min', 'med', 'max', 'p(90)', 'p(95)', 'p(99)', 'p(99.9)'],
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8080';

export default function () {
  const res = http.get(`${BASE_URL}/api/votes/results`);

  responseTimeDrift.add(res.timings.duration);

  check(res, { 'soak: 200': (r) => r.status === 200 });
}
