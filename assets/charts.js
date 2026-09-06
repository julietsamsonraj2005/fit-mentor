// Lightweight charts and BMI utilities (no external libs)

function renderLineChart(canvasId, labels, data, color) {
  try {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !canvas.getContext) return;
    const ctx = canvas.getContext('2d');
    const W = canvas.width;
    const H = canvas.height;
    // Clear
    ctx.clearRect(0, 0, W, H);

    // Guards
    if (!Array.isArray(data) || data.length === 0) {
      ctx.fillStyle = '#6c757d';
      ctx.font = '14px sans-serif';
      ctx.fillText('No data', 10, 20);
      return;
    }

    // Padding
    const pad = { l: 40, r: 10, t: 10, b: 30 };

    // Scales
    const minVal = Math.min(...data);
    const maxVal = Math.max(...data);
    const range = maxVal - minVal || 1;
    const n = data.length;

    function x(i) {
      if (n === 1) return pad.l + (W - pad.l - pad.r) / 2;
      return pad.l + (i * (W - pad.l - pad.r)) / (n - 1);
    }
    function y(v) {
      const norm = (v - minVal) / range; // 0..1
      return H - pad.b - norm * (H - pad.t - pad.b);
    }

    // Axes
    ctx.strokeStyle = '#e9ecef';
    ctx.lineWidth = 1;
    // X axis
    ctx.beginPath();
    ctx.moveTo(pad.l, H - pad.b);
    ctx.lineTo(W - pad.r, H - pad.b);
    ctx.stroke();
    // Y axis
    ctx.beginPath();
    ctx.moveTo(pad.l, pad.t);
    ctx.lineTo(pad.l, H - pad.b);
    ctx.stroke();

    // Y ticks (min/mid/max)
    ctx.fillStyle = '#6c757d';
    ctx.font = '12px sans-serif';
    const ticks = [minVal, minVal + range / 2, maxVal];
    ticks.forEach((t) => {
      const ty = y(t);
      ctx.fillText(String(Math.round(t)), 5, ty + 4);
      ctx.strokeStyle = '#f1f3f5';
      ctx.beginPath();
      ctx.moveTo(pad.l, ty);
      ctx.lineTo(W - pad.r, ty);
      ctx.stroke();
    });

    // Line
    ctx.strokeStyle = color || '#4a90e2';
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(x(0), y(data[0]));
    for (let i = 1; i < n; i++) {
      ctx.lineTo(x(i), y(data[i]));
    }
    ctx.stroke();

    // Points
    ctx.fillStyle = color || '#4a90e2';
    for (let i = 0; i < n; i++) {
      ctx.beginPath();
      ctx.arc(x(i), y(data[i]), 2.5, 0, Math.PI * 2);
      ctx.fill();
    }

    // Optional: a few x labels (first, mid, last)
    if (Array.isArray(labels) && labels.length === n && n >= 1) {
      ctx.fillStyle = '#6c757d';
      ctx.font = '11px sans-serif';
      const positions = [0, Math.floor((n - 1) / 2), n - 1];
      positions.forEach((idx) => {
        const lx = x(idx);
        ctx.fillText(String(labels[idx]), lx - 20, H - 8);
      });
    }
  } catch (e) {
    console.error('renderLineChart error:', e);
  }
}

function calcBMI(heightInputId, weightInputId, resultElementId) {
  const hEl = document.getElementById(heightInputId);
  const wEl = document.getElementById(weightInputId);
  const rEl = document.getElementById(resultElementId);
  if (!hEl || !wEl || !rEl) return;
  const h = parseFloat(hEl.value);
  const w = parseFloat(wEl.value);
  if (!h || !w || h <= 0 || w <= 0) {
    rEl.textContent = 'Please enter valid height and weight.';
    return;
  }
  const bmi = w / Math.pow(h / 100, 2);
  let cat = 'Underweight';
  if (bmi >= 30) cat = 'Obese';
  else if (bmi >= 25) cat = 'Overweight';
  else if (bmi >= 18.5) cat = 'Normal';
  rEl.textContent = `BMI: ${bmi.toFixed(1)} (${cat})`;
}

// Expose to global
window.renderLineChart = renderLineChart;
window.calcBMI = calcBMI;
