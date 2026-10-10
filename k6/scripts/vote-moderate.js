import http from 'k6/http';
import { check } from 'k6';
import { Rate, Trend } from 'k6/metrics';
import { generateCpf, randomCandidateId } from '../lib/helpers.js';

const voteSuccessRate = new Rate('vote_success_rate');
const voteDuration = new Trend('vote_duration_ms', true);
const resultsDuration = new Trend('results_duration_ms', true);

// Carga moderada: 25 VUs fixos, SEM think time (mede a latência real,
// abaixo da fila — pm.max_children=32 no PHP-FPM).
export const options = {
  vus: 25,
  duration: '2m',
  thresholds: {
    'http_req_failed': ['rate<0.01'],
    'vote_success_rate': ['rate>0.99'],
  },
  summaryTrendStats: ['avg', 'min', 'med', 'max', 'p(90)', 'p(95)', 'p(99)', 'p(99.9)'],
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8080';

export default function () {
  const rand = Math.random();

  if (rand < 0.5) {
    // 50%: votar
    const payload = JSON.stringify({
      candidateId: randomCandidateId(),
      cpf: generateCpf(__VU, __ITER),
    });

    const res = http.post(`${BASE_URL}/api/votes`, payload, {
      headers: { 'Content-Type': 'application/json' },
      tags: { endpoint: 'vote' },
    });

    voteSuccessRate.add(res.status === 201);
    voteDuration.add(res.timings.duration);
    check(res, { 'vote: 201': (r) => r.status === 201 });
  } else if (rand < 0.8) {
    // 30%: resultados
    const res = http.get(`${BASE_URL}/api/votes/results`, {
      tags: { endpoint: 'results' },
    });

    resultsDuration.add(res.timings.duration);
    check(res, { 'results: 200': (r) => r.status === 200 });
  } else {
    // 20%: candidatos
    const res = http.get(`${BASE_URL}/api/candidates`, {
      tags: { endpoint: 'candidates' },
    });

    check(res, { 'candidates: 200': (r) => r.status === 200 });
  }
}
