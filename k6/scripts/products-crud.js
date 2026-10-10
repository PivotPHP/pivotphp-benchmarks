import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend } from 'k6/metrics';

// Grupo B — CRUD de Produtos com Persistência Relacional e Cache (SPEC-106)
// Proporção de carga realista:
// - 70% leitura (listagem paginada, busca por id, filtro por categoria)
// - 15% agregação em cache (dashboard)
// - 15% escrita (criação e atualização de produtos)

const listDuration = new Trend('crud_list_duration_ms');
const showDuration = new Trend('crud_show_duration_ms');
const createDuration = new Trend('crud_create_duration_ms');
const dashboardDuration = new Trend('crud_dashboard_duration_ms');

export const options = {
  stages: [
    { duration: '30s', target: 100 }, // Ramp-up
    { duration: '1m',  target: 300 }, // Carga moderada
    { duration: '1m',  target: 500 }, // Carga alta
    { duration: '30s', target: 0 },   // Ramp-down
  ],
  thresholds: {
    http_req_failed: ['rate<0.01'], // < 1% erros
    crud_list_duration_ms: ['p(95)<100'],
    crud_show_duration_ms: ['p(95)<50'],
    crud_create_duration_ms: ['p(95)<150'],
    crud_dashboard_duration_ms: ['p(95)<30'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8080';

export default function () {
  const rand = Math.random();

  if (rand < 0.40) {
    // 1. Listagem paginada (40%)
    const page = Math.floor(Math.random() * 20) + 1;
    const res = http.get(`${BASE_URL}/api/products?page=${page}&limit=20`, {
      tags: { endpoint: 'list' },
    });
    listDuration.add(res.timings.duration);
    check(res, {
      'list: 200': (r) => r.status === 200,
      'list: tem dados': (r) => r.json('data') && r.json('data').length > 0,
    });
  } else if (rand < 0.65) {
    // 2. Detalhe de produto específico por ID (25%)
    const id = Math.floor(Math.random() * 1000) + 1;
    const res = http.get(`${BASE_URL}/api/products/${id}`, {
      tags: { endpoint: 'show' },
    });
    showDuration.add(res.timings.duration);
    check(res, {
      'show: 200': (r) => r.status === 200,
    });
  } else if (rand < 0.80) {
    // 3. Filtro por categoria (15%)
    const catId = Math.floor(Math.random() * 10) + 1;
    const res = http.get(`${BASE_URL}/api/products?category_id=${catId}&limit=10`, {
      tags: { endpoint: 'filter' },
    });
    listDuration.add(res.timings.duration);
    check(res, {
      'filter: 200': (r) => r.status === 200,
    });
  } else if (rand < 0.90) {
    // 4. Dashboard agregado com cache Redis (10%)
    const res = http.get(`${BASE_URL}/api/dashboard`, {
      tags: { endpoint: 'dashboard' },
    });
    dashboardDuration.add(res.timings.duration);
    check(res, {
      'dashboard: 200': (r) => r.status === 200,
      'dashboard: total': (r) => r.json('total_products') > 0,
    });
  } else {
    // 5. Escrita / Inserção de novo produto (10%)
    const payload = JSON.stringify({
      name: `Benchmark Produto VU${__VU} I${__ITER}`,
      description: 'Criado durante carga k6',
      price: (Math.random() * 100 + 10).toFixed(2),
      stock: 50,
      category_id: Math.floor(Math.random() * 10) + 1,
    });

    const res = http.post(`${BASE_URL}/api/products`, payload, {
      headers: { 'Content-Type': 'application/json' },
      tags: { endpoint: 'create' },
    });
    createDuration.add(res.timings.duration);
    check(res, {
      'create: 201': (r) => r.status === 201,
      'create: id gerado': (r) => r.json('id') > 0,
    });
  }

  sleep(0.05);
}
