import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  vus: 1,
  duration: '20s',
  thresholds: {
    http_req_failed: ['rate<0.01'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8080';

// Gera um CPF válido e único por iteração (dígitos verificadores corretos),
// para evitar 409 de voto duplicado durante o smoke test.
function checkDigits(base) {
  let acc = base;
  let out = '';
  for (let t = 9; t < 11; t++) {
    let sum = 0;
    for (let i = 0; i < t; i++) {
      sum += parseInt(acc[i], 10) * (t + 1 - i);
    }
    const d = ((10 * sum) % 11) % 10;
    acc += d;
    out += d;
  }
  return out;
}

function generateCpf() {
  const n = (__VU * 1000000) + __ITER;
  let base = String(n).padStart(9, '0').slice(-9);
  if (/^(\d)\1{8}$/.test(base)) {
    base = '123456789';
  }
  return base + checkDigits(base);
}

export default function () {
  // Health
  const health = http.get(`${BASE_URL}/api/health`);
  check(health, { 'health: 200': (r) => r.status === 200 });

  // Candidatos
  const candidates = http.get(`${BASE_URL}/api/candidates`);
  check(candidates, { 'candidates: 200': (r) => r.status === 200 });

  // Voto (CPF válido e único)
  const payload = JSON.stringify({
    candidateId: Math.floor(Math.random() * 5) + 1,
    cpf: generateCpf(),
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

  sleep(1);
}
