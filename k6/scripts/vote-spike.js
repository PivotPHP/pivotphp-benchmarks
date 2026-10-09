import http from 'k6/http';
import { check } from 'k6';
import { generateCpf, randomCandidateId } from '../lib/helpers.js';

export const options = {
  stages: [
    { duration: '30s', target: 10 },     // baseline
    { duration: '20s', target: 1500 },    // pico súbito
    { duration: '40s', target: 1500 },    // sustentar pico
    { duration: '30s', target: 10 },      // recuperação
  ],
  thresholds: {
    'http_req_duration': ['p(95)<2000'],
    'http_req_failed': ['rate<0.05'],
  },
  summaryTrendStats: ['avg', 'min', 'med', 'max', 'p(90)', 'p(95)', 'p(99)', 'p(99.9)'],
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8080';

export default function () {
  const payload = JSON.stringify({
    candidateId: randomCandidateId(),
    cpf: generateCpf(__VU, __ITER),
  });

  const res = http.post(`${BASE_URL}/api/votes`, payload, {
    headers: { 'Content-Type': 'application/json' },
  });

  check(res, {
    'spike: não é 500': (r) => r.status !== 500,
    'spike: resposta < 3s': (r) => r.timings.duration < 3000,
  });
}
