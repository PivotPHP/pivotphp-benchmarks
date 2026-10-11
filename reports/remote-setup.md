# Guia — Execução remota: APIs numa VM Hyper-V e k6 em outro computador

Este guia prepara a topologia da [SPEC-110](https://github.com/PivotPHP/pivotphp-specs/blob/main/SPECS/SPEC-110-benchmark-remote-execution.md):
as APIs rodam numa **VM Linux dedicada** e a carga é gerada pelo **k6 nativo do Windows** em outro computador,
para que o gerador não dispute CPU com as aplicações.

```
┌───────────────── Máquina principal (Windows + Hyper-V) ──────────────┐       ┌──── Notebook (Windows) ────┐
│  VM "bench-sut" — Ubuntu 22.04/24.04 LTS · 6 vCPU · 12 GB fixos       │  LAN  │  k6 nativo (gera a carga)   │
│  Docker Engine · pivotphp-benchmarks clonado · stacks :8080–:8084     │◄─────►│  Git Bash + SSH (comanda    │
│  (uma stack ligada por vez durante a medição)                         │ cabo  │  a VM)                      │
└───────────────────────────────────────────────────────────────────────┘       └────────────────────────────┘
```

> **Escopo**: este guia cobre a preparação dos dois computadores e uma **validação manual** (subir uma stack
> na VM e rodar o k6 do notebook contra ela). O modo remoto automatizado do `scripts/run-benchmark.sh` ainda
> não existe — ele é parte da SPEC-110.

**Rede recomendada**: os dois computadores **no mesmo switch, por cabo** (de preferência gigabit), na mesma
rede do roteador. A latência fica baixa e estável e o tráfego do teste não passa por Wi-Fi. Confira que as duas
placas negociaram 1 Gbps (Windows: `Get-NetAdapter | Select Name, LinkSpeed`).

Portas publicadas por framework (iguais na VM):

| Framework | Porta |
|---|---|
| PivotPHP | 8080 |
| Slim | 8081 |
| Mezzio | 8082 |
| Symfony | 8083 |
| Webman | 8084 |

---

## Parte 1 — VM na máquina principal (Hyper-V)

### 1.1 Pré-requisitos

- Windows **Pro/Enterprise/Education** com Hyper-V habilitado e virtualização (VT-x) ativa na BIOS. Para conferir,
  no PowerShell: `Get-ComputerInfo | Select-Object WindowsProductName, HyperVisorPresent`.
- **Cabo de rede** na máquina principal, ligado ao mesmo switch do notebook (o switch externo do Hyper-V funciona
  com Wi-Fi, mas com mais variação de latência).
- ISO do **Ubuntu Server 22.04 LTS** ou **24.04 LTS** (`ubuntu-2x.04.x-live-server-amd64.iso`, em ubuntu.com/download/server).
- ~90 GB livres no SSD.

### 1.2 Switch virtual externo

No **PowerShell como administrador**:

```powershell
Get-NetAdapter                       # anote o nome da placa cabeada (ex.: "Ethernet")
New-VMSwitch -Name "Bench-External" -NetAdapterName "Ethernet" -AllowManagementOS $true
```

> A rede da máquina cai por alguns segundos enquanto o switch é criado.
> (Alternativa gráfica: Gerenciador do Hyper-V → Gerenciador de Comutador Virtual → Externo.)

### 1.3 Criar a VM

#### Opção A — ISO do Ubuntu Server (recomendada)

```powershell
$vm  = "bench-sut"
$dir = "D:\VMs"                      # ajuste para uma pasta no SSD
$iso = "D:\ISOs\ubuntu-22.04.5-live-server-amd64.iso"   # ajuste para o seu arquivo

New-Item -ItemType Directory -Force $dir | Out-Null
New-VHD -Path "$dir\$vm.vhdx" -SizeBytes 80GB -Fixed     # disco FIXO (demora alguns minutos)
New-VM -Name $vm -Generation 2 -MemoryStartupBytes 12GB -VHDPath "$dir\$vm.vhdx" -SwitchName "Bench-External"
Set-VMMemory    $vm -DynamicMemoryEnabled $false         # memória estática
Set-VMProcessor $vm -Count 6
Set-VM          $vm -CheckpointType Disabled -AutomaticStartAction Nothing -AutomaticStopAction ShutDown
Set-VMFirmware  $vm -SecureBootTemplate MicrosoftUEFICertificateAuthority   # necessário para Linux
Add-VMDvdDrive  $vm -Path $iso
Set-VMFirmware  $vm -FirstBootDevice (Get-VMDvdDrive $vm)
Start-VM $vm
vmconnect.exe localhost $vm
```

#### Opção B — "Criação Rápida" do Hyper-V (Ubuntu 22.04 Desktop)

A Criação Rápida cria memória dinâmica, disco dinâmico e checkpoints. Com a VM **desligada**:

```powershell
$vm = "<nome-da-vm>"
Set-VMMemory    $vm -DynamicMemoryEnabled $false -StartupBytes 12GB
Set-VMProcessor $vm -Count 6
Set-VM          $vm -CheckpointType Disabled
Get-VMHardDiskDrive $vm                                   # anote o caminho do .vhdx
Convert-VHD -Path "<caminho>.vhdx" -DestinationPath "<caminho>-fixo.vhdx" -VHDType Fixed
Set-VMHardDiskDrive -VMName $vm -ControllerLocation 0 -Path "<caminho>-fixo.vhdx"
Get-VMNetworkAdapter $vm | Connect-VMNetworkAdapter -SwitchName "Bench-External"
```

Depois do primeiro boot, desligue a interface gráfica (passo 1.5).

### 1.4 Instalar o Ubuntu (opção A)

No instalador:

1. Idioma/teclado à escolha; tipo de instalação **Ubuntu Server (minimized)** ou padrão.
2. Rede: deixe em DHCP por enquanto (ajustamos o IP no passo 1.6).
3. Disco: usar o disco inteiro (o de 80 GB).
4. Perfil: usuário **`bench`**, nome do servidor **`bench-sut`**.
5. **Marque "Install OpenSSH server"**.
6. Não selecione snaps adicionais. Ao final, **Reboot Now** (remova a ISO se ele pedir: `Remove-VMDvdDrive bench-sut -ControllerNumber 0 -ControllerLocation 1` ou pelo menu da VM).

### 1.5 Ajustes do sistema

Na VM (pelo console ou por SSH):

```bash
sudo apt update && sudo apt full-upgrade -y          # atualiza UMA vez, antes das rodadas
sudo timedatectl set-timezone America/Sao_Paulo
sudo systemctl disable --now unattended-upgrades      # sem atualizações automáticas durante as medições
sudo systemctl disable --now apt-daily.timer apt-daily-upgrade.timer
```

Somente na **opção B (Desktop)**:

```bash
sudo apt install -y openssh-server
sudo systemctl set-default multi-user.target          # inicia sem interface gráfica
sudo snap refresh --hold                              # pausa atualizações de snaps
sudo reboot
```

### 1.6 IP fixo

Descubra o IP atual e a interface:

```bash
ip -4 -brief address          # ex.: eth0  UP  192.168.0.57/24
ip route | grep default       # gateway, ex.: 192.168.0.1
```

O mais simples é **reservar esse IP no roteador** (reserva DHCP pelo endereço MAC da VM, visto em
`Get-VMNetworkAdapter bench-sut | Select MacAddress`). Alternativa, com netplan (ajuste interface, IP e gateway):

```bash
sudo tee /etc/netplan/60-bench.yaml >/dev/null <<'EOF'
network:
  version: 2
  ethernets:
    eth0:
      dhcp4: false
      addresses: [192.168.0.50/24]
      routes: [{to: default, via: 192.168.0.1}]
      nameservers: {addresses: [1.1.1.1, 8.8.8.8]}
EOF
sudo chmod 600 /etc/netplan/60-bench.yaml
sudo netplan apply
```

No restante do guia, `<IP-DA-VM>` é esse endereço.

### 1.7 Docker Engine (repositório oficial)

```bash
sudo apt-get install -y ca-certificates curl git python3
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] \
https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" \
  | sudo tee /etc/apt/sources.list.d/docker.list >/dev/null
sudo apt-get update
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo usermod -aG docker bench
exit                                  # saia e entre de novo para o grupo valer
```

Teste: `docker run --rm hello-world` e `docker compose version`.

### 1.8 Repositório, imagens e validação

```bash
git clone https://github.com/PivotPHP/pivotphp-benchmarks.git ~/pivotphp-benchmarks
cd ~/pivotphp-benchmarks
for fw in pivotphp slim mezzio symfony webman; do (cd "$fw" && docker compose build app); done
docker pull grafana/k6:latest postgres:17-alpine redis:7-alpine
bash scripts/validate-environment.sh  # deve terminar com "Validação concluída com sucesso"
```

### 1.9 Firewall da VM (opcional)

Por padrão o `ufw` vem **inativo** no Ubuntu e as portas ficam acessíveis na rede local. Para restringir à sua
rede (ajuste a faixa):

```bash
sudo ufw allow from 192.168.0.0/24 to any port 22 proto tcp
sudo ufw allow from 192.168.0.0/24 to any port 8080:8084 proto tcp
sudo ufw enable
```

Não exponha essas portas à internet (sem redirecionamento no roteador).

### 1.10 Antes de cada rodada (Windows da máquina principal)

```powershell
wsl --shutdown                                  # WSL2 e a VM disputam o mesmo hypervisor
# feche o Docker Desktop, navegador e demais programas
powercfg /setactive SCHEME_MIN                  # plano "Alto desempenho"
powercfg /change standby-timeout-ac 0           # sem suspensão na tomada
```

E pause o Windows Update (Configurações → Windows Update → Pausar atualizações).

---

## Parte 2 — Notebook (gerador de carga)

### 2.1 Rede e energia

- Ligue o notebook **na tomada** e **no cabo**, no mesmo switch da máquina principal.
- A rede deve estar como **Privada** (Configurações → Rede e Internet → propriedades da conexão).
- No PowerShell como administrador:

```powershell
powercfg /setactive SCHEME_MIN
powercfg /change standby-timeout-ac 0
powercfg /change monitor-timeout-ac 0
```

### 2.2 Ferramentas

No PowerShell:

```powershell
winget install --id Git.Git -e                  # Git + Git Bash
winget install --id Python.Python.3.12 -e       # usado pelos scripts de relatório
winget install --id k6.k6 -e                    # k6 nativo do Windows
```

Feche e abra o terminal e confira:

```powershell
git --version; python --version; k6 version     # anote a versão do k6: ela vai para o relatório
```

O cliente SSH já vem no Windows 10/11 (`ssh -V`). Não é preciso Docker no notebook.

### 2.3 Acesso SSH à VM por chave

No PowerShell do notebook:

```powershell
ssh-keygen -t ed25519 -C "bench-notebook"       # Enter em tudo (ou defina uma senha para a chave)
type $env:USERPROFILE\.ssh\id_ed25519.pub | ssh bench@<IP-DA-VM> "mkdir -p ~/.ssh && cat >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys"
ssh bench@<IP-DA-VM> "docker ps"                # deve responder sem pedir senha
```

### 2.4 Clonar os scripts do k6

No Git Bash:

```bash
git clone https://github.com/PivotPHP/pivotphp-benchmarks.git ~/pivotphp-benchmarks
```

O notebook usa apenas `k6/scripts/` (os scripts aceitam `-e BASE_URL=...`).

---

## Parte 3 — Validação manual da topologia

Tudo no **Git Bash do notebook**. Defina o IP uma vez:

```bash
VM=<IP-DA-VM>
cd ~/pivotphp-benchmarks
```

### 3.1 Latência base da rede

```bash
ping -n 20 $VM                                  # anote média e máximo
```

### 3.2 Subir uma stack na VM e rodar o smoke

```bash
ssh bench@$VM "cd ~/pivotphp-benchmarks/pivotphp && docker compose up -d postgres redis app"
until curl -sf http://$VM:8080/api/health >/dev/null; do sleep 2; done

for i in 1 2 3 4 5; do curl -s -o /dev/null -w '%{time_total}s\n' http://$VM:8080/api/health; done   # latência sem carga

k6 run -e BASE_URL=http://$VM:8080 k6/scripts/smoke.js   # esperado: checks 100%, http_req_failed 0%

ssh bench@$VM "cd ~/pivotphp-benchmarks/pivotphp && docker compose down -v"
```

> O `docker compose` roda **na VM** (via SSH) porque os compose montam arquivos locais
> (`./database/*.sql`); um Docker remoto via `DOCKER_HOST` procuraria esses caminhos no lugar errado.

### 3.3 Os 5 frameworks

```bash
declare -A PORT=([pivotphp]=8080 [slim]=8081 [mezzio]=8082 [symfony]=8083 [webman]=8084)
for fw in pivotphp slim mezzio symfony webman; do
  ssh bench@$VM "cd ~/pivotphp-benchmarks/$fw && docker compose up -d postgres redis app" >/dev/null
  until curl -sf http://$VM:${PORT[$fw]}/api/health >/dev/null; do sleep 2; done
  echo "== $fw"
  k6 run -q -e BASE_URL=http://$VM:${PORT[$fw]} k6/scripts/smoke.js | grep -E 'checks_succeeded|http_req_failed'
  ssh bench@$VM "cd ~/pivotphp-benchmarks/$fw && docker compose down -v" >/dev/null
done
```

Esperado: `checks_succeeded 100%` e `http_req_failed 0.00%` nos cinco.

### 3.4 Checklist

- [ ] VM com 6 vCPU, 12 GB **estáticos**, disco **fixo**, checkpoints desligados, switch externo
- [ ] Ubuntu sem interface gráfica e sem atualizações automáticas
- [ ] Docker Engine oficial; `validate-environment.sh` verde na VM
- [ ] IP fixo/reservado; SSH por chave do notebook sem senha
- [ ] k6 nativo instalado no notebook (versão anotada)
- [ ] Latência base anotada; smoke 100% nos 5 frameworks a partir do notebook
- [ ] Antes das rodadas: `wsl --shutdown`, Docker Desktop fechado, alto desempenho e nada aberto nos dois computadores
