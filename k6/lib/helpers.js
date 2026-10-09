// Helpers compartilhados dos cenários de carga.

// Dígitos verificadores de um CPF (base de 9 dígitos → 2 dígitos).
export function checkDigits(base) {
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

// Gera um CPF válido e único por (VU, iteração).
// __ITER é por-VU, então combinamos VU e iteração para evitar colisões de CPF
// (que gerariam 409 de voto duplicado).
export function generateCpf(vu, iter) {
  const n = ((vu - 1) * 500000) + iter;
  let base = String(n).padStart(9, '0');
  if (base.length > 9) {
    base = base.slice(-9);
  }
  if (/^(\d)\1{8}$/.test(base)) {
    base = '123456789';
  }
  return base + checkDigits(base);
}

export function randomCandidateId(max = 5) {
  return Math.floor(Math.random() * max) + 1;
}
