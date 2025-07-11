#!/usr/bin/env python3
"""
Script para instalar dependências Python necessárias para o sistema de relatórios
"""

import subprocess
import sys
import os

def install_package(package):
    """Instala um pacote Python usando pip"""
    try:
        subprocess.check_call([sys.executable, "-m", "pip", "install", package])
        print(f"✅ {package} instalado com sucesso")
        return True
    except subprocess.CalledProcessError:
        print(f"❌ Erro ao instalar {package}")
        return False

def main():
    """Função principal"""
    print("📦 Instalando dependências Python para o sistema de relatórios...\n")
    
    # Lista de pacotes necessários
    packages = [
        "jinja2>=3.0.0",
        "python-dateutil>=2.8.0"
    ]
    
    success_count = 0
    total_packages = len(packages)
    
    for package in packages:
        if install_package(package):
            success_count += 1
        print()
    
    print(f"📊 Resultado: {success_count}/{total_packages} pacotes instalados com sucesso")
    
    if success_count == total_packages:
        print("\n🎉 Todas as dependências foram instaladas com sucesso!")
        print("O sistema de relatórios está pronto para uso.")
    else:
        print("\n⚠️  Algumas dependências falharam na instalação.")
        print("Verifique os erros acima e tente novamente.")
        sys.exit(1)

if __name__ == "__main__":
    main()