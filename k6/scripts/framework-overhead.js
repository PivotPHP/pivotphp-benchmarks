import http from 'k6/http';
import { check, sleep } from 'k6';

// Grupo A — Overhead Mínimo do Framework (SPEC-106)
// Mede custo da camada de abstração HTTP, roteamento e middlewares sem I/O de banco/cache.

export const options = {
  stages: [
    { duration: '30s', target: 200 },  // Aquecimento rápido
    { duration: '1m',  target: 500 },  // Carga constante
    { duration: '1m',  target: 1000 }, // Carga alta de throughput
    { duration: '30s', target: 0 },    // Resfriamento
  ],
  thresholds: {
    http_req_failed: ['rate<0.001'], // 99.99% de sucesso esperado
    'http_req_duration{endpoint:health}': ['p(95)<10'], // p95 < 10ms
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8080';

export default function () {
  // 1. Health JSON
  const resHealth = http.get(`${BASE_URL}/api/health`, {
    tags: { endpoint: 'health' },
  });
  check(resHealth, {
    'health: 200': (r) => r.status === 200,
    'health: status ok': (r) => r.json('status') === 'ok',
  });

  // 2. Ping plain text
  const resPing = http.get(`${BASE_URL}/api/ping`, {
    tags: { endpoint: 'ping' },
  });
  check(resPing, {
    'ping: 200': (r) => r.status === 200,
  });

  sleep(0.1);
}
