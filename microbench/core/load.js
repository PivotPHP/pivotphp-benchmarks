// Carga do microbench do core: número fixo de requisições, para dividir a CPU consumida por elas.
import http from 'k6/http';
import { check } from 'k6';

export const options = {
  scenarios: {
    fixed: {
      executor: 'shared-iterations',
      vus: Number(__ENV.VUS || 8),
      iterations: Number(__ENV.REQUESTS),
      maxDuration: '5m',
    },
  },
};

export default function () {
  const res = http.get(`http://${__ENV.TARGET}${__ENV.ROUTE || '/api/bare'}`);
  check(res, { 'status 200': (r) => r.status === 200 });
}
