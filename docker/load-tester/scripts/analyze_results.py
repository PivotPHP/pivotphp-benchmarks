#!/usr/bin/env python3

"""
Advanced High-Scale Performance Analysis Tool
Processes load testing results and generates comprehensive reports
"""

import json
import csv
import os
import sys
import re
import statistics
from pathlib import Path
from collections import defaultdict
import matplotlib.pyplot as plt
import pandas as pd
import numpy as np

class PerformanceAnalyzer:
    def __init__(self, results_dir):
        self.results_dir = Path(results_dir)
        self.frameworks = []
        self.concurrency_levels = []
        self.results = defaultdict(lambda: defaultdict(dict))
        
    def discover_test_data(self):
        """Discover available frameworks and concurrency levels"""
        for framework_dir in self.results_dir.iterdir():
            if framework_dir.is_dir() and not framework_dir.name.startswith('.'):
                framework = framework_dir.name
                self.frameworks.append(framework)
                
                for concurrency_dir in framework_dir.iterdir():
                    if concurrency_dir.is_dir() and concurrency_dir.name.startswith('c'):
                        concurrency = int(concurrency_dir.name[1:])  # Remove 'c' prefix
                        if concurrency not in self.concurrency_levels:
                            self.concurrency_levels.append(concurrency)
        
        self.frameworks.sort()
        self.concurrency_levels.sort()
        print(f"📊 Discovered frameworks: {self.frameworks}")
        print(f"📊 Discovered concurrency levels: {self.concurrency_levels}")
    
    def parse_ab_results(self, ab_file):
        """Parse Apache Bench results"""
        if not ab_file.exists():
            return None
            
        with open(ab_file, 'r') as f:
            content = f.read()
        
        result = {}
        
        # Parse key metrics
        patterns = {
            'requests_per_second': r'Requests per second:\s+([0-9.]+)',
            'time_per_request': r'Time per request:\s+([0-9.]+)\s+\[ms\]\s+\(mean\)',
            'time_per_request_concurrent': r'Time per request:\s+([0-9.]+)\s+\[ms\]\s+\(mean, across all concurrent requests\)',
            'transfer_rate': r'Transfer rate:\s+([0-9.]+)',
            'failed_requests': r'Failed requests:\s+([0-9]+)',
            'total_requests': r'Complete requests:\s+([0-9]+)',
            'total_time': r'Time taken for tests:\s+([0-9.]+)'
        }
        
        for key, pattern in patterns.items():
            match = re.search(pattern, content)
            if match:
                result[key] = float(match.group(1))
        
        # Parse percentiles
        percentile_pattern = r'\s+(\d+)%\s+([0-9.]+)'
        percentiles = {}
        for match in re.finditer(percentile_pattern, content):
            percentiles[f'p{match.group(1)}'] = float(match.group(2))
        
        result['percentiles'] = percentiles
        result['tool'] = 'apache_bench'
        
        return result
    
    def parse_wrk_results(self, wrk_file):
        """Parse wrk results"""
        if not wrk_file.exists():
            return None
            
        with open(wrk_file, 'r') as f:
            content = f.read()
        
        result = {}
        
        # Parse wrk output
        patterns = {
            'requests_per_second': r'Requests/sec:\s+([0-9.]+)',
            'transfer_rate': r'Transfer/sec:\s+([0-9.]+[KMG]?B)',
            'total_requests': r'(\d+) requests in',
            'total_time': r'requests in ([0-9.]+)s',
            'latency_avg': r'Latency\s+([0-9.]+[mun]?s)',
            'latency_stdev': r'Latency\s+[0-9.]+[mun]?s\s+([0-9.]+[mun]?s)',
            'latency_max': r'Latency\s+[0-9.]+[mun]?s\s+[0-9.]+[mun]?s\s+([0-9.]+[mun]?s)'
        }
        
        for key, pattern in patterns.items():
            match = re.search(pattern, content)
            if match:
                value = match.group(1)
                # Convert time units to milliseconds
                if key.startswith('latency') and ('ms' in value or 'us' in value or 's' in value):
                    value = self.convert_to_ms(value)
                result[key] = float(value) if value.replace('.', '').isdigit() else value
        
        result['tool'] = 'wrk'
        return result
    
    def parse_vegeta_results(self, vegeta_file):
        """Parse Vegeta results"""
        if not vegeta_file.exists():
            return None
            
        with open(vegeta_file, 'r') as f:
            content = f.read()
        
        result = {}
        
        patterns = {
            'requests_per_second': r'Rate\s+([0-9.]+)',
            'success_rate': r'Success\s+([0-9.]+)%',
            'total_requests': r'Requests\s+\[total\]\s+([0-9]+)',
            'latency_min': r'Min\s+\[min\]\s+([0-9.]+[mun]?s)',
            'latency_mean': r'Mean\s+\[mean\]\s+([0-9.]+[mun]?s)',
            'latency_p50': r'50th\s+\[P50\]\s+([0-9.]+[mun]?s)',
            'latency_p90': r'90th\s+\[P90\]\s+([0-9.]+[mun]?s)',
            'latency_p95': r'95th\s+\[P95\]\s+([0-9.]+[mun]?s)',
            'latency_p99': r'99th\s+\[P99\]\s+([0-9.]+[mun]?s)',
            'latency_max': r'Max\s+\[max\]\s+([0-9.]+[mun]?s)'
        }
        
        for key, pattern in patterns.items():
            match = re.search(pattern, content)
            if match:
                value = match.group(1)
                if key.startswith('latency') and ('ms' in value or 'us' in value or 's' in value):
                    value = self.convert_to_ms(value)
                result[key] = float(value) if str(value).replace('.', '').isdigit() else value
        
        result['tool'] = 'vegeta'
        return result
    
    def parse_hey_results(self, hey_file):
        """Parse hey results"""
        if not hey_file.exists():
            return None
            
        with open(hey_file, 'r') as f:
            content = f.read()
        
        result = {}
        
        patterns = {
            'requests_per_second': r'Requests/sec:\s+([0-9.]+)',
            'total_requests': r'Total:\s+([0-9.]+) secs',
            'total_time': r'Total:\s+([0-9.]+)',
            'fastest': r'Fastest:\s+([0-9.]+)',
            'slowest': r'Slowest:\s+([0-9.]+)',
            'average': r'Average:\s+([0-9.]+)',
            'success_rate': r'Status code distribution:\s+\[200\]\s+([0-9]+)'
        }
        
        for key, pattern in patterns.items():
            match = re.search(pattern, content)
            if match:
                result[key] = float(match.group(1))
        
        result['tool'] = 'hey'
        return result
    
    def convert_to_ms(self, time_str):
        """Convert time string to milliseconds"""
        if 'ms' in time_str:
            return float(time_str.replace('ms', ''))
        elif 'us' in time_str or 'µs' in time_str:
            return float(time_str.replace('us', '').replace('µs', '')) / 1000
        elif 's' in time_str and 'ms' not in time_str:
            return float(time_str.replace('s', '')) * 1000
        return float(time_str)
    
    def load_all_results(self):
        """Load results from all test files"""
        for framework in self.frameworks:
            for concurrency in self.concurrency_levels:
                concurrency_dir = self.results_dir / framework / f'c{concurrency}'
                if not concurrency_dir.exists():
                    continue
                
                # Parse all available tool results
                tools_data = {}
                
                # Apache Bench
                ab_data = self.parse_ab_results(concurrency_dir / 'ab.txt')
                if ab_data:
                    tools_data['ab'] = ab_data
                
                # wrk
                wrk_data = self.parse_wrk_results(concurrency_dir / 'wrk.txt')
                if wrk_data:
                    tools_data['wrk'] = wrk_data
                
                # Vegeta
                vegeta_data = self.parse_vegeta_results(concurrency_dir / 'vegeta.txt')
                if vegeta_data:
                    tools_data['vegeta'] = vegeta_data
                
                # hey
                hey_data = self.parse_hey_results(concurrency_dir / 'hey.txt')
                if hey_data:
                    tools_data['hey'] = hey_data
                
                if tools_data:
                    self.results[framework][concurrency] = tools_data
    
    def calculate_composite_metrics(self):
        """Calculate composite metrics from multiple tools"""
        composite_results = defaultdict(lambda: defaultdict(dict))
        
        for framework in self.frameworks:
            for concurrency in self.concurrency_levels:
                if concurrency not in self.results[framework]:
                    continue
                
                tools_data = self.results[framework][concurrency]
                
                # Calculate averages across tools
                rps_values = []
                latency_values = []
                success_rates = []
                
                for tool, data in tools_data.items():
                    if 'requests_per_second' in data:
                        rps_values.append(data['requests_per_second'])
                    
                    # Get latency from different tools
                    if 'time_per_request' in data:
                        latency_values.append(data['time_per_request'])
                    elif 'latency_mean' in data:
                        latency_values.append(data['latency_mean'])
                    elif 'average' in data:
                        latency_values.append(data['average'])
                    
                    # Calculate success rate
                    if 'success_rate' in data:
                        success_rates.append(data['success_rate'])
                    elif 'failed_requests' in data and 'total_requests' in data:
                        success_rate = ((data['total_requests'] - data['failed_requests']) / data['total_requests']) * 100
                        success_rates.append(success_rate)
                
                # Store composite metrics
                composite = {
                    'avg_rps': statistics.mean(rps_values) if rps_values else 0,
                    'max_rps': max(rps_values) if rps_values else 0,
                    'min_rps': min(rps_values) if rps_values else 0,
                    'avg_latency': statistics.mean(latency_values) if latency_values else 0,
                    'avg_success_rate': statistics.mean(success_rates) if success_rates else 100,
                    'tools_count': len(tools_data),
                    'raw_data': tools_data
                }
                
                composite_results[framework][concurrency] = composite
        
        return composite_results
    
    def generate_scalability_analysis(self, composite_results):
        """Generate scalability analysis"""
        analysis = {
            'framework_rankings': {},
            'scalability_scores': {},
            'performance_degradation': {},
            'stability_analysis': {},
            'recommendations': {}
        }
        
        # Calculate performance metrics for each framework
        for framework in self.frameworks:
            framework_data = composite_results[framework]
            
            if not framework_data:
                continue
            
            concurrency_levels = sorted(framework_data.keys())
            rps_values = [framework_data[c]['avg_rps'] for c in concurrency_levels]
            latency_values = [framework_data[c]['avg_latency'] for c in concurrency_levels]
            success_rates = [framework_data[c]['avg_success_rate'] for c in concurrency_levels]
            
            # Peak performance
            peak_rps = max(rps_values) if rps_values else 0
            peak_concurrency = concurrency_levels[rps_values.index(peak_rps)] if rps_values else 0
            
            # Scalability score (performance retention)
            if len(rps_values) >= 2:
                initial_rps = rps_values[0]
                final_rps = rps_values[-1]
                scalability_score = (final_rps / initial_rps) * 100 if initial_rps > 0 else 0
            else:
                scalability_score = 100
            
            # Stability analysis (find degradation point)
            degradation_point = None
            for i, success_rate in enumerate(success_rates):
                if success_rate < 95:  # Less than 95% success rate
                    degradation_point = concurrency_levels[i]
                    break
            
            # Performance degradation analysis
            if len(rps_values) >= 3:
                # Find the point where RPS starts declining
                decline_point = None
                for i in range(1, len(rps_values)):
                    if rps_values[i] < rps_values[i-1] * 0.95:  # 5% decline
                        decline_point = concurrency_levels[i]
                        break
            else:
                decline_point = None
            
            analysis['framework_rankings'][framework] = {
                'peak_rps': peak_rps,
                'peak_concurrency': peak_concurrency,
                'scalability_score': scalability_score,
                'degradation_point': degradation_point,
                'decline_point': decline_point,
                'max_tested_concurrency': max(concurrency_levels),
                'avg_latency_at_peak': latency_values[rps_values.index(peak_rps)] if rps_values else 0
            }
        
        # Generate recommendations
        best_overall = max(analysis['framework_rankings'].items(), 
                          key=lambda x: x[1]['peak_rps'], default=(None, None))
        
        best_scalability = max(analysis['framework_rankings'].items(), 
                              key=lambda x: x[1]['scalability_score'], default=(None, None))
        
        analysis['recommendations'] = {
            'best_performance': best_overall[0] if best_overall[0] else 'None',
            'best_scalability': best_scalability[0] if best_scalability[0] else 'None',
            'production_guidance': self.generate_production_guidance(analysis['framework_rankings'])
        }
        
        return analysis
    
    def generate_production_guidance(self, rankings):
        """Generate production deployment guidance"""
        guidance = {}
        
        for framework, metrics in rankings.items():
            if metrics['degradation_point']:
                safe_concurrency = metrics['degradation_point'] * 0.8  # 80% of degradation point
            else:
                safe_concurrency = metrics['max_tested_concurrency'] * 0.9
            
            guidance[framework] = {
                'recommended_max_concurrency': int(safe_concurrency),
                'performance_tier': self.classify_performance_tier(metrics['peak_rps']),
                'scalability_rating': self.classify_scalability(metrics['scalability_score']),
                'production_readiness': self.assess_production_readiness(metrics)
            }
        
        return guidance
    
    def classify_performance_tier(self, peak_rps):
        """Classify performance tier based on peak RPS"""
        if peak_rps >= 50000:
            return 'Enterprise'
        elif peak_rps >= 20000:
            return 'High Performance'
        elif peak_rps >= 10000:
            return 'Standard'
        elif peak_rps >= 5000:
            return 'Basic'
        else:
            return 'Low Performance'
    
    def classify_scalability(self, scalability_score):
        """Classify scalability rating"""
        if scalability_score >= 80:
            return 'Excellent'
        elif scalability_score >= 60:
            return 'Good'
        elif scalability_score >= 40:
            return 'Fair'
        else:
            return 'Poor'
    
    def assess_production_readiness(self, metrics):
        """Assess production readiness"""
        if (metrics['peak_rps'] >= 10000 and 
            metrics['scalability_score'] >= 60 and 
            (not metrics['degradation_point'] or metrics['degradation_point'] >= 5000)):
            return 'Ready'
        elif metrics['peak_rps'] >= 5000 and metrics['scalability_score'] >= 40:
            return 'Conditional'
        else:
            return 'Not Ready'
    
    def generate_charts(self, composite_results, analysis, output_dir):
        """Generate performance charts"""
        charts_dir = Path(output_dir) / 'charts'
        charts_dir.mkdir(exist_ok=True)
        
        # RPS vs Concurrency chart
        plt.figure(figsize=(12, 8))
        for framework in self.frameworks:
            if framework in composite_results:
                concurrency_levels = sorted(composite_results[framework].keys())
                rps_values = [composite_results[framework][c]['avg_rps'] for c in concurrency_levels]
                plt.plot(concurrency_levels, rps_values, marker='o', label=framework)
        
        plt.xlabel('Concurrency Level')
        plt.ylabel('Requests per Second')
        plt.title('Framework Performance vs Concurrency')
        plt.legend()
        plt.grid(True)
        plt.savefig(charts_dir / 'rps_vs_concurrency.png', dpi=300, bbox_inches='tight')
        plt.close()
        
        # Latency vs Concurrency chart
        plt.figure(figsize=(12, 8))
        for framework in self.frameworks:
            if framework in composite_results:
                concurrency_levels = sorted(composite_results[framework].keys())
                latency_values = [composite_results[framework][c]['avg_latency'] for c in concurrency_levels]
                plt.plot(concurrency_levels, latency_values, marker='s', label=framework)
        
        plt.xlabel('Concurrency Level')
        plt.ylabel('Average Latency (ms)')
        plt.title('Framework Latency vs Concurrency')
        plt.legend()
        plt.grid(True)
        plt.savefig(charts_dir / 'latency_vs_concurrency.png', dpi=300, bbox_inches='tight')
        plt.close()
        
        # Performance comparison bar chart
        frameworks = list(analysis['framework_rankings'].keys())
        peak_rps = [analysis['framework_rankings'][f]['peak_rps'] for f in frameworks]
        
        plt.figure(figsize=(10, 6))
        bars = plt.bar(frameworks, peak_rps, color=['skyblue', 'lightcoral', 'lightgreen', 'gold'][:len(frameworks)])
        plt.xlabel('Framework')
        plt.ylabel('Peak RPS')
        plt.title('Peak Performance Comparison')
        plt.xticks(rotation=45)
        
        # Add value labels on bars
        for bar in bars:
            height = bar.get_height()
            plt.text(bar.get_x() + bar.get_width()/2., height,
                    f'{int(height):,}', ha='center', va='bottom')
        
        plt.tight_layout()
        plt.savefig(charts_dir / 'peak_performance_comparison.png', dpi=300, bbox_inches='tight')
        plt.close()
        
        print(f"📊 Charts generated in: {charts_dir}")
    
    def save_results(self, composite_results, analysis, output_file):
        """Save complete analysis to JSON"""
        complete_report = {
            'metadata': {
                'analysis_timestamp': pd.Timestamp.now().isoformat(),
                'frameworks_analyzed': self.frameworks,
                'concurrency_levels_tested': self.concurrency_levels,
                'total_test_scenarios': len(self.frameworks) * len(self.concurrency_levels)
            },
            'composite_results': {
                framework: {str(concurrency): data for concurrency, data in framework_data.items()}
                for framework, framework_data in composite_results.items()
            },
            'scalability_analysis': analysis
        }
        
        with open(output_file, 'w') as f:
            json.dump(complete_report, f, indent=2, default=str)
        
        print(f"📄 Complete analysis saved to: {output_file}")
    
    def run_analysis(self):
        """Run complete analysis"""
        print("🔍 Starting performance analysis...")
        
        # Discover and load data
        self.discover_test_data()
        self.load_all_results()
        
        # Calculate composite metrics
        composite_results = self.calculate_composite_metrics()
        
        # Generate analysis
        analysis = self.generate_scalability_analysis(composite_results)
        
        # Generate outputs
        output_dir = self.results_dir / 'analysis'
        output_dir.mkdir(exist_ok=True)
        
        # Save results
        self.save_results(composite_results, analysis, output_dir / 'complete_analysis.json')
        
        # Generate charts
        self.generate_charts(composite_results, analysis, output_dir)
        
        # Print summary
        self.print_summary(analysis)
        
        return composite_results, analysis
    
    def print_summary(self, analysis):
        """Print analysis summary"""
        print("\n" + "="*80)
        print("📊 HIGH-SCALE PERFORMANCE ANALYSIS SUMMARY")
        print("="*80)
        
        print("\n🏆 FRAMEWORK RANKINGS:")
        rankings = sorted(analysis['framework_rankings'].items(), 
                         key=lambda x: x[1]['peak_rps'], reverse=True)
        
        for i, (framework, metrics) in enumerate(rankings, 1):
            print(f"   {i}. {framework.upper()}")
            print(f"      Peak RPS: {metrics['peak_rps']:,.0f}")
            print(f"      Scalability Score: {metrics['scalability_score']:.1f}%")
            print(f"      Peak Concurrency: {metrics['peak_concurrency']:,}")
            if metrics['degradation_point']:
                print(f"      Degradation Point: {metrics['degradation_point']:,} concurrent")
            print()
        
        print("💡 RECOMMENDATIONS:")
        recs = analysis['recommendations']
        print(f"   Best Overall Performance: {recs['best_performance']}")
        print(f"   Best Scalability: {recs['best_scalability']}")
        
        print("\n📋 PRODUCTION GUIDANCE:")
        for framework, guidance in recs['production_guidance'].items():
            print(f"   {framework}:")
            print(f"     Max Recommended Concurrency: {guidance['recommended_max_concurrency']:,}")
            print(f"     Performance Tier: {guidance['performance_tier']}")
            print(f"     Scalability Rating: {guidance['scalability_rating']}")
            print(f"     Production Readiness: {guidance['production_readiness']}")

def main():
    if len(sys.argv) != 2:
        print("Usage: python3 analyze_results.py <results_directory>")
        sys.exit(1)
    
    results_dir = sys.argv[1]
    
    if not os.path.exists(results_dir):
        print(f"❌ Results directory not found: {results_dir}")
        sys.exit(1)
    
    analyzer = PerformanceAnalyzer(results_dir)
    analyzer.run_analysis()

if __name__ == "__main__":
    main()