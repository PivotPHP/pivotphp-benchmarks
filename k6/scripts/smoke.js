import http from 'k6/http';
import { check, sleep } from 'k6';
import { generateCpf, randomCandidateId } from '../lib/helpers.js';

export const options = {
  vus: 1,
  duration: '20s',
  thresholds: {
    http_req_failed: ['rate<0.01'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8080';

export default function () {
  // Reseta os votos uma única vez (torna o smoke determinístico entre execuções).
  if (__ITER === 0 && __VU === 1) {
    http.del(`${BASE_URL}/api/votes`);
  }

  // Health
  const health = http.get(`${BASE_URL}/api/health`);
  check(health, { 'health: 200': (r) => r.status === 200 });

  // Candidatos
  const candidates = http.get(`${BASE_URL}/api/candidates`);
  check(candidates, { 'candidates: 200': (r) => r.status === 200 });

  // Voto (CPF válido e único)
  const payload = JSON.stringify({
    candidateId: randomCandidateId(),
    cpf: generateCpf(__VU, __ITER),
  });
  const vote = http.post(`${BASE_URL}/api/votes`, payload, {
    headers: { 'Content-Type': 'application/json' },
  });
  check(vote, {
    'vote: 201': (r) => r.status === 201,
  });

  // Resultados
  const results = http.get(`${BASE_URL}/api/votes/results`);
  check(results, { 'results: 200': (r) => r.status === 200 });

  // Produtos CRUD (Grupo B)
  const products = http.get(`${BASE_URL}/api/products?page=1&limit=5`);
  check(products, { 'products list: 200': (r) => r.status === 200 });

  const productOne = http.get(`${BASE_URL}/api/products/1`);
  check(productOne, { 'product detail: 200': (r) => r.status === 200 });

  const dashboard = http.get(`${BASE_URL}/api/dashboard`);
  check(dashboard, { 'dashboard: 200': (r) => r.status === 200 });

  sleep(1);
}
