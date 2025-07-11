#!/usr/bin/env python3
"""
Sistema de Scoring e Ranking para Benchmarks Cross-Framework
Implementa metodologia científica para comparação objetiva de frameworks
"""

import json
import math
import statistics
from typing import Dict, List, Tuple, Any
from dataclasses import dataclass
from datetime import datetime

@dataclass
class BenchmarkResult:
    """Estrutura para um resultado de benchmark individual"""
    framework: str
    version: str
    benchmark_name: str
    ops_per_second: float
    latency_p95: float
    memory_peak: int
    cpu_usage: float
    iterations: int
    duration: float

@dataclass
class FrameworkScore:
    """Estrutura para score final de um framework"""
    framework: str
    version: str
    performance_score: float
    efficiency_score: float
    features_score: float
    usability_score: float
    overall_score: float
    rank: int

class BenchmarkScorer:
    """Sistema principal de scoring e ranking"""
    
    def __init__(self):
        self.weights = {
            'performance': 0.40,
            'efficiency': 0.25,
            'features': 0.20,
            'usability': 0.15
        }
        
        self.performance_weights = {
            'throughput': 0.50,
            'latency': 0.30,
            'scalability': 0.20
        }
        
        self.efficiency_weights = {
            'memory': 0.60,
            'cpu': 0.40
        }
        
        # Baseline values for normalization
        self.baselines = {
            'throughput_max': 3000000,  # 3M ops/sec
            'throughput_min': 100000,   # 100K ops/sec
            'latency_max': 0.100,       # 100ms
            'latency_min': 0.001,       # 1ms
            'memory_max': 128,          # 128MB
            'memory_min': 4,            # 4MB
            'cpu_max': 80,              # 80%
            'cpu_min': 5                # 5%
        }
    
    def normalize_metric(self, value: float, min_val: float, max_val: float, invert: bool = False) -> float:
        """
        Normaliza métrica para escala 0-1
        invert=True para métricas onde menor é melhor (latência, memória)
        """
        if max_val == min_val:
            return 0.5
        
        normalized = (value - min_val) / (max_val - min_val)
        normalized = max(0, min(1, normalized))
        
        if invert:
            normalized = 1 - normalized
        
        return normalized
    
    def calculate_throughput_score(self, results: List[BenchmarkResult]) -> float:
        """Calcula score de throughput baseado em múltiplos benchmarks"""
        throughputs = [r.ops_per_second for r in results]
        avg_throughput = statistics.mean(throughputs)
        
        # Normaliza usando baselines
        normalized = self.normalize_metric(
            avg_throughput, 
            self.baselines['throughput_min'], 
            self.baselines['throughput_max']
        )
        
        # Converte para escala 1-5
        return 1 + (normalized * 4)
    
    def calculate_latency_score(self, results: List[BenchmarkResult]) -> float:
        """Calcula score de latência (menor é melhor)"""
        latencies = [r.latency_p95 for r in results]
        avg_latency = statistics.mean(latencies)
        
        # Normaliza usando baselines (invertido pois menor é melhor)
        normalized = self.normalize_metric(
            avg_latency, 
            self.baselines['latency_min'], 
            self.baselines['latency_max'],
            invert=True
        )
        
        return 1 + (normalized * 4)
    
    def calculate_memory_score(self, results: List[BenchmarkResult]) -> float:
        """Calcula score de uso de memória (menor é melhor)"""
        memories = [r.memory_peak / 1024 / 1024 for r in results]  # Convert to MB
        avg_memory = statistics.mean(memories)
        
        normalized = self.normalize_metric(
            avg_memory, 
            self.baselines['memory_min'], 
            self.baselines['memory_max'],
            invert=True
        )
        
        return 1 + (normalized * 4)
    
    def calculate_cpu_score(self, results: List[BenchmarkResult]) -> float:
        """Calcula score de uso de CPU (menor é melhor)"""
        cpus = [r.cpu_usage for r in results]
        avg_cpu = statistics.mean(cpus)
        
        normalized = self.normalize_metric(
            avg_cpu, 
            self.baselines['cpu_min'], 
            self.baselines['cpu_max'],
            invert=True
        )
        
        return 1 + (normalized * 4)
    
    def calculate_scalability_score(self, results: List[BenchmarkResult]) -> float:
        """
        Calcula score de escalabilidade baseado na consistência
        Menor variação = melhor escalabilidade
        """
        throughputs = [r.ops_per_second for r in results]
        
        if len(throughputs) < 2:
            return 4.0  # Score neutro se não há dados suficientes
        
        cv = statistics.stdev(throughputs) / statistics.mean(throughputs)
        
        # Coeficiente de variação baixo = boa escalabilidade
        # 0% = 5.0, 20% = 1.0
        cv_score = max(1.0, 5.0 - (cv * 20))
        
        return cv_score
    
    def calculate_performance_score(self, results: List[BenchmarkResult]) -> float:
        """Calcula score geral de performance"""
        throughput_score = self.calculate_throughput_score(results)
        latency_score = self.calculate_latency_score(results)
        scalability_score = self.calculate_scalability_score(results)
        
        weighted_score = (
            throughput_score * self.performance_weights['throughput'] +
            latency_score * self.performance_weights['latency'] +
            scalability_score * self.performance_weights['scalability']
        )
        
        return weighted_score
    
    def calculate_efficiency_score(self, results: List[BenchmarkResult]) -> float:
        """Calcula score geral de eficiência"""
        memory_score = self.calculate_memory_score(results)
        cpu_score = self.calculate_cpu_score(results)
        
        weighted_score = (
            memory_score * self.efficiency_weights['memory'] +
            cpu_score * self.efficiency_weights['cpu']
        )
        
        return weighted_score
    
    def calculate_features_score(self, framework: str) -> float:
        """
        Calcula score de funcionalidades baseado em análise qualitativa
        TODO: Implementar análise automática de features
        """
        # Scores manuais baseados em análise de funcionalidades
        features_scores = {
            'pivotphp-core': 3.8,
            'flight': 2.8,
            'slim4': 2.5,
            'lumen': 2.8,
            'symfony': 3.0
        }
        
        return features_scores.get(framework.lower(), 3.0)
    
    def calculate_usability_score(self, framework: str) -> float:
        """
        Calcula score de facilidade de uso baseado em análise qualitativa
        TODO: Implementar análise automática de usabilidade
        """
        # Scores manuais baseados em análise de usabilidade
        usability_scores = {
            'pivotphp-core': 4.0,
            'flight': 3.0,
            'slim4': 2.8,
            'lumen': 2.5,
            'symfony': 2.0
        }
        
        return usability_scores.get(framework.lower(), 3.0)
    
    def calculate_overall_score(self, performance: float, efficiency: float, 
                               features: float, usability: float) -> float:
        """Calcula score geral ponderado"""
        overall = (
            performance * self.weights['performance'] +
            efficiency * self.weights['efficiency'] +
            features * self.weights['features'] +
            usability * self.weights['usability']
        )
        
        return overall
    
    def score_framework(self, framework: str, version: str, 
                       results: List[BenchmarkResult]) -> FrameworkScore:
        """Calcula score completo para um framework"""
        performance_score = self.calculate_performance_score(results)
        efficiency_score = self.calculate_efficiency_score(results)
        features_score = self.calculate_features_score(framework)
        usability_score = self.calculate_usability_score(framework)
        
        overall_score = self.calculate_overall_score(
            performance_score, efficiency_score, features_score, usability_score
        )
        
        return FrameworkScore(
            framework=framework,
            version=version,
            performance_score=performance_score,
            efficiency_score=efficiency_score,
            features_score=features_score,
            usability_score=usability_score,
            overall_score=overall_score,
            rank=0  # Será calculado após comparação
        )
    
    def rank_frameworks(self, scores: List[FrameworkScore]) -> List[FrameworkScore]:
        """Ordena frameworks por score e atribui rankings"""
        sorted_scores = sorted(scores, key=lambda x: x.overall_score, reverse=True)
        
        for i, score in enumerate(sorted_scores):
            score.rank = i + 1
        
        return sorted_scores

class BenchmarkAnalyzer:
    """Analisador de resultados de benchmarks"""
    
    def __init__(self):
        self.scorer = BenchmarkScorer()
    
    def load_results_from_json(self, file_path: str) -> List[BenchmarkResult]:
        """Carrega resultados de arquivo JSON"""
        with open(file_path, 'r') as f:
            data = json.load(f)
        
        results = []
        framework = data.get('framework', 'unknown')
        version = data.get('version', '1.0.0')
        
        for benchmark_name, benchmark_data in data.get('benchmarks', {}).items():
            result = BenchmarkResult(
                framework=framework,
                version=version,
                benchmark_name=benchmark_name,
                ops_per_second=benchmark_data.get('ops_per_second', 0),
                latency_p95=benchmark_data.get('latency_p95', 0.1),
                memory_peak=benchmark_data.get('memory_peak', 0),
                cpu_usage=benchmark_data.get('cpu_usage', 0),
                iterations=benchmark_data.get('iterations', 10000),
                duration=benchmark_data.get('duration', 1.0)
            )
            results.append(result)
        
        return results
    
    def analyze_directory(self, directory: str) -> List[FrameworkScore]:
        """Analisa todos os arquivos JSON em um diretório"""
        import os
        import glob
        
        framework_results = {}
        
        # Carrega todos os arquivos JSON
        pattern = os.path.join(directory, "*.json")
        for file_path in glob.glob(pattern):
            try:
                results = self.load_results_from_json(file_path)
                if results:
                    framework = results[0].framework
                    version = results[0].version
                    
                    if framework not in framework_results:
                        framework_results[framework] = {
                            'version': version,
                            'results': []
                        }
                    
                    framework_results[framework]['results'].extend(results)
            except Exception as e:
                print(f"Erro ao carregar {file_path}: {e}")
        
        # Calcula scores para cada framework
        scores = []
        for framework, data in framework_results.items():
            score = self.scorer.score_framework(
                framework, data['version'], data['results']
            )
            scores.append(score)
        
        # Ordena por ranking
        ranked_scores = self.scorer.rank_frameworks(scores)
        
        return ranked_scores
    
    def generate_report(self, scores: List[FrameworkScore]) -> Dict[str, Any]:
        """Gera relatório completo com scores e rankings"""
        report = {
            'timestamp': datetime.now().isoformat(),
            'methodology': {
                'performance_weight': self.scorer.weights['performance'],
                'efficiency_weight': self.scorer.weights['efficiency'],
                'features_weight': self.scorer.weights['features'],
                'usability_weight': self.scorer.weights['usability']
            },
            'rankings': {
                'overall': [],
                'performance': [],
                'efficiency': [],
                'features': [],
                'usability': []
            },
            'detailed_scores': []
        }
        
        # Ranking geral
        for score in scores:
            report['rankings']['overall'].append({
                'rank': score.rank,
                'framework': score.framework,
                'version': score.version,
                'score': round(score.overall_score, 2)
            })
        
        # Rankings por categoria
        for category in ['performance', 'efficiency', 'features', 'usability']:
            category_scores = sorted(scores, 
                                   key=lambda x: getattr(x, f'{category}_score'), 
                                   reverse=True)
            
            for i, score in enumerate(category_scores):
                report['rankings'][category].append({
                    'rank': i + 1,
                    'framework': score.framework,
                    'version': score.version,
                    'score': round(getattr(score, f'{category}_score'), 2)
                })
        
        # Scores detalhados
        for score in scores:
            report['detailed_scores'].append({
                'framework': score.framework,
                'version': score.version,
                'overall_rank': score.rank,
                'overall_score': round(score.overall_score, 2),
                'performance_score': round(score.performance_score, 2),
                'efficiency_score': round(score.efficiency_score, 2),
                'features_score': round(score.features_score, 2),
                'usability_score': round(score.usability_score, 2)
            })
        
        return report
    
    def save_report(self, report: Dict[str, Any], output_path: str):
        """Salva relatório em arquivo JSON"""
        with open(output_path, 'w') as f:
            json.dump(report, f, indent=2)
        
        print(f"Relatório salvo em: {output_path}")

def main():
    """Função principal para execução do script"""
    import argparse
    
    parser = argparse.ArgumentParser(description='Sistema de Scoring Cross-Framework')
    parser.add_argument('--input', '-i', required=True, 
                       help='Diretório com arquivos JSON de resultados')
    parser.add_argument('--output', '-o', required=True,
                       help='Arquivo de saída para relatório')
    parser.add_argument('--verbose', '-v', action='store_true',
                       help='Output detalhado')
    
    args = parser.parse_args()
    
    # Inicializa analisador
    analyzer = BenchmarkAnalyzer()
    
    if args.verbose:
        print("Analisando resultados...")
    
    # Analisa diretório
    scores = analyzer.analyze_directory(args.input)
    
    if args.verbose:
        print(f"Frameworks analisados: {len(scores)}")
        print("\nRanking:")
        for score in scores:
            print(f"{score.rank}º - {score.framework} v{score.version} - {score.overall_score:.2f}")
    
    # Gera relatório
    report = analyzer.generate_report(scores)
    
    # Salva relatório
    analyzer.save_report(report, args.output)
    
    print(f"\nAnálise concluída! Relatório salvo em: {args.output}")

if __name__ == "__main__":
    main()