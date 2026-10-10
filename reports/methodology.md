# Metodologia de Benchmarking — PivotPHP (SPEC-106)

Esta documentação define a metodologia oficial para a execução, reprodução e avaliação de desempenho
do PivotPHP em comparação com outros frameworks PHP, utilizando APIs reais em ambientes controlados.

---

## 1. Princípios Fundamentais

1. **APIs Reais, Não Apenas "Hello World"**:
   Frameworks modernos não devem ser avaliados exclusivamente por roteamento estático em memória.
   As suítes medem validação de entrada, injeção de dependências, pools de conexão com banco relacional
   (PostgreSQL), cache distribuído (Redis) e manipulação de transações ACID sob concorrência.

2. **Separação por Arquitetura de Runtime (Dois Rankings)**:
   - **Ranking Grupo 1 — PHP-FPM (Nginx + PHP-FPM 8.3)**:
     Compara frameworks tradicionais de requisição sem estado sob o mesmo servidor Web (Nginx) e
     mesma configuração de pool FPM (`pm = static`, `pm.max_children = 20`, OPcache ativado).
     *Participantes*: PivotPHP, Slim 4, Mezzio, Symfony (microkernel).
   - **Ranking Grupo 2 — Comparativo Geral de Arquiteturas**:
     Apresenta o impacto de diferentes runtimes de execução, incluindo servidores assíncronos/event-driven
     baseados em processos persistentes (CLI) como o Webman (Workerman).

3. **Isolamento Estrito de Recursos**:
   Cada framework é executado em sua própria stack isolada (PostgreSQL + Redis + App exclusivos),
   com limites rígidos de CPU e Memória aplicados via contêineres e cgroups Linux:
   - **Aplicação**: 1.0 vCPU e 512 MB de RAM.
   - **PostgreSQL**: 2.0 vCPU e 2 GB de RAM.
   - **Redis**: 0.5 vCPU e 256 MB de RAM.

4. **Reprodutibilidade e Dados Brutos Auditáveis**:
   Nenhuma métrica é divulgada sem a publicação dos seus dados brutos correspondentes, scripts k6,
   parâmetros de ambiente, commits exatos e metadados (`metadata.json`).

---

## 2. Grupos de Teste

| Grupo | Foco da Medição | Cenários |
|:---|:---|:---|
| **Grupo A** | Overhead Mínimo do Framework | `GET /api/health` — sem banco ou I/O externo. |
| **Grupo B** | CRUD com Persistência Relacional | `GET /api/products` (paginado), `POST /api/products`, `PATCH`, `DELETE`. |
| **Grupo C** | Regras de Negócio e Concorrência | API de votação com validação de CPF, transação concorrente com detecção de duplicidade e cache Redis. |

---

## 3. Métricas Coletadas

Para cada cenário e patamar de carga, são coletadas as seguintes grandezas:

1. **Throughput Sustentado**: Requisições bem-sucedidas por segundo (RPS) durante o período estável.
2. **Latência Percentílica**:
   - `p50` (mediana)
   - `p95` (95% das requisições abaixo deste valor)
   - `p99` (cauda longa / pior caso sob estresse)
3. **Taxa de Erro**: Percentual de requisições que retornaram `5xx`, timeouts ou falhas de conexão.
   - *Critério de Aceitação*: $\le 0.1\%$ de falhas.
4. **Eficiência de Recursos**:
   - CPU % média e pico durante o teste.
   - Consumo de memória residente (RSS).
   - Eficiência energética/computacional calculada como:
     $$\text{Eficiência} = \frac{\text{RPS Médio}}{\text{vCPUs Consumidas}}$$

---

## 4. Perfis de Carga do k6

- **`smoke.js`**: 1 VU, 20 segundos. Validação determinística de conformidade da API antes do teste.
- **`vote-moderate.js`**: Carga moderada (100 a 400 VUs). Mede latência e estabilidade em condições de uso produtivo típico.
- **`vote-load.js`**: Carga alta (até 800 VUs) com escada de subida (ramp-up), saturação e descida.
- **`vote-spike.js`**: Salto repentino de 500 para 2.000 RPS para testar capacidade de absorção de picos e recuperação.
- **`vote-soak.js`**: Execução de longa duração para detecção de vazamentos de memória (memory leaks) ou exaustão de pool.

---

## 5. Como Executar e Reproduzir

```bash
# 1. Validar pré-requisitos do ambiente e cgroups
./scripts/validate-environment.sh

# 2. Executar cenário para todos os frameworks
./scripts/run-benchmark.sh

# 3. Executar framework específico e cenário moderado
./scripts/run-benchmark.sh pivotphp vote-moderate
```

Os resultados são salvos em `results/<timestamp>/` contendo `metadata.json`, logs brutos, `resource-usage.csv` e `report.md`.
