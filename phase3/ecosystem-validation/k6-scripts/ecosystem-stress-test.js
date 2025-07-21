// PivotPHP Ecosystem Stress Test - K6 Script
// Comprehensive performance validation for Core + ReactPHP + Cycle ORM
// Production-ready load testing scenarios

import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Rate, Trend, Counter } from 'k6/metrics';

// Custom metrics
export const errorRate = new Rate('errors');
export const responseTimeTrend = new Trend('response_time');
export const requestCounter = new Counter('requests_total');

// Test configuration
export const options = {
  stages: [
    // Ramp up
    { duration: '2m', target: 10 },   // Warm up
    { duration: '5m', target: 50 },   // Normal load
    { duration: '10m', target: 100 }, // High load
    { duration: '5m', target: 200 },  // Stress test
    { duration: '5m', target: 300 },  // Peak load
    { duration: '2m', target: 0 },    // Ramp down
  ],
  thresholds: {
    // Performance targets based on PivotPHP benchmarks
    'http_req_duration': ['p(95)<100', 'p(99)<200'], // 95% under 100ms, 99% under 200ms
    'http_req_failed': ['rate<0.05'], // Error rate under 5%
    'errors': ['rate<0.05'],
    'response_time': ['p(95)<100'],
  },
};

// Test scenarios configuration
const scenarios = {
  ecosystem_full: {
    base_url: 'http://pivotphp-full-ecosystem:8080',
    weight: 40 // 40% of traffic
  },
  core_only: {
    base_url: 'http://pivotphp-core-only:8080',
    weight: 20 // 20% of traffic
  },
  core_reactphp: {
    base_url: 'http://pivotphp-core-reactphp:8080',
    weight: 25 // 25% of traffic
  },
  core_orm: {
    base_url: 'http://pivotphp-core-orm:8080',
    weight: 15 // 15% of traffic
  }
};

// Request payloads
const testPayloads = {
  simple: { message: 'test' },
  medium: {
    user: {
      name: 'Test User',
      email: 'test@example.com',
      data: Array.from({length: 50}, (_, i) => ({ key: `value_${i}` }))
    }
  },
  complex: {
    workflow: {
      steps: Array.from({length: 10}, (_, i) => ({
        id: i + 1,
        name: `Step ${i + 1}`,
        data: Array.from({length: 20}, (_, j) => ({ item: j }))
      })),
      metadata: {
        version: '1.0',
        timestamp: Date.now(),
        environment: 'test'
      }
    }
  }
};

export default function() {
  // Select scenario based on weight
  const scenario = selectScenario();
  const baseUrl = scenario.base_url;
  
  group('Health Check', () => {
    testHealthEndpoint(baseUrl);
  });
  
  group('Simple API Performance', () => {
    testSimpleAPI(baseUrl);
  });
  
  group('JSON Processing', () => {
    testJSONProcessing(baseUrl);
  });
  
  group('Database Operations', () => {
    testDatabaseOperations(baseUrl);
  });
  
  group('Real-time Features', () => {
    testRealtimeFeatures(baseUrl);
  });
  
  group('Complex Workflow', () => {
    testComplexWorkflow(baseUrl);
  });
  
  // Random sleep between 0.1 and 1 second
  sleep(Math.random() * 0.9 + 0.1);
}

function selectScenario() {
  const rand = Math.random() * 100;
  let cumulative = 0;
  
  for (const [name, config] of Object.entries(scenarios)) {
    cumulative += config.weight;
    if (rand <= cumulative) {
      return config;
    }
  }
  
  return scenarios.ecosystem_full; // Default fallback
}

function testHealthEndpoint(baseUrl) {
  const response = http.get(`${baseUrl}/health`);
  
  const success = check(response, {
    'health check status is 200': (r) => r.status === 200,
    'health check response time < 50ms': (r) => r.timings.duration < 50,
    'health check has status field': (r) => JSON.parse(r.body).status !== undefined,
  });
  
  errorRate.add(!success);
  responseTimeTrend.add(response.timings.duration);
  requestCounter.add(1);
}

function testSimpleAPI(baseUrl) {
  const response = http.get(`${baseUrl}/api/performance`);
  
  const success = check(response, {
    'simple API status is 200': (r) => r.status === 200,
    'simple API response time < 100ms': (r) => r.timings.duration < 100,
    'simple API returns JSON': (r) => r.headers['Content-Type'] && r.headers['Content-Type'].includes('application/json'),
  });
  
  errorRate.add(!success);
  responseTimeTrend.add(response.timings.duration);
  requestCounter.add(1);
}

function testJSONProcessing(baseUrl) {
  const payload = Math.random() > 0.5 ? testPayloads.simple : testPayloads.medium;
  
  const response = http.post(`${baseUrl}/api/realtime`, JSON.stringify(payload), {
    headers: {
      'Content-Type': 'application/json',
    },
  });
  
  const success = check(response, {
    'JSON processing status is 200': (r) => r.status === 200,
    'JSON processing response time < 150ms': (r) => r.timings.duration < 150,
    'JSON processing returns processed data': (r) => {
      try {
        const body = JSON.parse(r.body);
        return body.input_data !== undefined;
      } catch (e) {
        return false;
      }
    },
  });
  
  errorRate.add(!success);
  responseTimeTrend.add(response.timings.duration);
  requestCounter.add(1);
}

function testDatabaseOperations(baseUrl) {
  const response = http.get(`${baseUrl}/api/database`);
  
  const success = check(response, {
    'database API status is 200': (r) => r.status === 200,
    'database API response time < 200ms': (r) => r.timings.duration < 200,
    'database API returns users': (r) => {
      try {
        const body = JSON.parse(r.body);
        return Array.isArray(body.users) && body.users.length > 0;
      } catch (e) {
        return false;
      }
    },
  });
  
  errorRate.add(!success);
  responseTimeTrend.add(response.timings.duration);
  requestCounter.add(1);
}

function testRealtimeFeatures(baseUrl) {
  const payload = {
    event_type: 'realtime_test',
    data: testPayloads.simple,
    timestamp: Date.now()
  };
  
  const response = http.post(`${baseUrl}/api/realtime`, JSON.stringify(payload), {
    headers: {
      'Content-Type': 'application/json',
    },
  });
  
  const success = check(response, {
    'realtime API status is 200': (r) => r.status === 200,
    'realtime API response time < 100ms': (r) => r.timings.duration < 100,
    'realtime API returns processed timestamp': (r) => {
      try {
        const body = JSON.parse(r.body);
        return body.processed_at !== undefined && body.received_at !== undefined;
      } catch (e) {
        return false;
      }
    },
  });
  
  errorRate.add(!success);
  responseTimeTrend.add(response.timings.duration);
  requestCounter.add(1);
}

function testComplexWorkflow(baseUrl) {
  const response = http.post(`${baseUrl}/api/workflow`, JSON.stringify(testPayloads.complex), {
    headers: {
      'Content-Type': 'application/json',
    },
  });
  
  const success = check(response, {
    'workflow API status is 200': (r) => r.status === 200,
    'workflow API response time < 300ms': (r) => r.timings.duration < 300,
    'workflow API returns workflow ID': (r) => {
      try {
        const body = JSON.parse(r.body);
        return body.workflow_id !== undefined && body.success === true;
      } catch (e) {
        return false;
      }
    },
    'workflow API uses all ecosystem components': (r) => {
      try {
        const body = JSON.parse(r.body);
        const components = body.ecosystem_components_used;
        return components.core_routing && components.reactphp_async && components.cycle_orm_database;
      } catch (e) {
        return false;
      }
    },
  });
  
  errorRate.add(!success);
  responseTimeTrend.add(response.timings.duration);
  requestCounter.add(1);
}

// Test teardown - summary report
export function handleSummary(data) {
  const report = {
    test_summary: {
      start_time: data.state.testRunDurationMs,
      end_time: Date.now(),
      total_requests: data.metrics.requests_total.count,
      error_rate: data.metrics.errors.rate,
      avg_response_time: data.metrics.response_time.avg,
      p95_response_time: data.metrics.response_time.p(95),
      p99_response_time: data.metrics.response_time.p(99)
    },
    http_metrics: {
      req_duration_avg: data.metrics.http_req_duration.avg,
      req_duration_p95: data.metrics.http_req_duration.p(95),
      req_duration_p99: data.metrics.http_req_duration.p(99),
      req_failed_rate: data.metrics.http_req_failed.rate,
      reqs_per_sec: data.metrics.http_reqs.rate
    },
    ecosystem_performance: {
      target_req_sec: 12000, // Full ecosystem target
      actual_req_sec: data.metrics.http_reqs.rate,
      performance_gap: ((data.metrics.http_reqs.rate - 12000) / 12000 * 100).toFixed(2) + '%',
      meets_target: data.metrics.http_reqs.rate >= 12000
    },
    thresholds: data.thresholds,
    test_passed: Object.values(data.thresholds).every(t => t.ok)
  };
  
  return {
    'ecosystem-stress-test-summary.json': JSON.stringify(report, null, 2),
    stdout: `
🚀 PivotPHP Ecosystem Stress Test Results
==========================================

📊 Performance Summary:
- Total Requests: ${report.test_summary.total_requests}
- Requests/sec: ${report.http_metrics.reqs_per_sec.toFixed(2)}
- Error Rate: ${(report.test_summary.error_rate * 100).toFixed(2)}%
- Avg Response Time: ${report.test_summary.avg_response_time.toFixed(2)}ms
- P95 Response Time: ${report.test_summary.p95_response_time.toFixed(2)}ms
- P99 Response Time: ${report.test_summary.p99_response_time.toFixed(2)}ms

🎯 Ecosystem Performance:
- Target: 12,000 req/sec
- Actual: ${report.http_metrics.reqs_per_sec.toFixed(2)} req/sec
- Gap: ${report.ecosystem_performance.performance_gap}
- Target Met: ${report.ecosystem_performance.meets_target ? '✅ YES' : '❌ NO'}

✅ Test Status: ${report.test_passed ? 'PASSED' : 'FAILED'}
    `
  };
}