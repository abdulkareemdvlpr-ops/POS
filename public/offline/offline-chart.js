(function () {
    function hexToRgb(hex) {
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        return `${r},${g},${b}`;
    }

    function formatRs(v) {
        if (v >= 1000000) return 'Rs. ' + (v / 1000000).toFixed(1) + 'M';
        if (v >= 1000)    return 'Rs. ' + (v / 1000).toFixed(1) + 'K';
        return 'Rs. ' + Math.round(v).toLocaleString();
    }

    window.renderOfflineBarChart = function (canvas, labels, values, opts) {
        if (!canvas || !canvas.getContext) return;
        opts = opts || {};
        const color      = opts.color || '#0ea5e9';
        const colorRgb   = hexToRgb(color);
        const ctx        = canvas.getContext('2d');
        const ratio      = Math.min(window.devicePixelRatio || 1, 2);
        const cssW       = canvas.offsetWidth  || canvas.clientWidth  || 640;
        const cssH       = Number(canvas.getAttribute('height')) || 180;
        canvas.width     = cssW * ratio;
        canvas.height    = cssH * ratio;
        ctx.scale(ratio, ratio);

        const pad    = { top: 24, right: 20, bottom: 40, left: 64 };
        const W      = cssW - pad.left - pad.right;
        const H      = cssH - pad.top  - pad.bottom;
        const nums   = values.map(Number);
        const maxVal = Math.max(1, ...nums);
        const N      = Math.max(1, labels.length);
        const stepX  = W / N;

        // --- background ---
        ctx.clearRect(0, 0, cssW, cssH);

        // --- grid lines & y-axis labels ---
        ctx.font = '11px "Segoe UI", Arial, sans-serif';
        ctx.textAlign = 'right';
        const steps = 4;
        for (let i = 0; i <= steps; i++) {
            const y   = pad.top + H * i / steps;
            const val = maxVal * (steps - i) / steps;
            ctx.strokeStyle = i === steps ? '#cbd5e1' : '#e2e8f0';
            ctx.lineWidth = i === steps ? 1.5 : 1;
            ctx.beginPath();
            ctx.moveTo(pad.left, y);
            ctx.lineTo(pad.left + W, y);
            ctx.stroke();

            ctx.fillStyle = '#94a3b8';
            ctx.fillText(formatRs(val), pad.left - 6, y + 4);
        }

        // --- gradient fill under curve ---
        const grad = ctx.createLinearGradient(0, pad.top, 0, pad.top + H);
        grad.addColorStop(0,   `rgba(${colorRgb},0.25)`);
        grad.addColorStop(0.7, `rgba(${colorRgb},0.05)`);
        grad.addColorStop(1,   `rgba(${colorRgb},0)`);

        // build smooth bezier path
        function getPoint(i) {
            const x = pad.left + i * stepX + stepX / 2;
            const y = pad.top + H - H * (nums[i] || 0) / maxVal;
            return [x, y];
        }

        ctx.beginPath();
        for (let i = 0; i < N; i++) {
            const [x, y] = getPoint(i);
            if (i === 0) {
                ctx.moveTo(x, y);
            } else {
                const [px, py] = getPoint(i - 1);
                const cpx = (px + x) / 2;
                ctx.bezierCurveTo(cpx, py, cpx, y, x, y);
            }
        }
        // close fill area
        const [lastX] = getPoint(N - 1);
        ctx.lineTo(lastX, pad.top + H);
        ctx.lineTo(pad.left + stepX / 2, pad.top + H);
        ctx.closePath();
        ctx.fillStyle = grad;
        ctx.fill();

        // --- line ---
        ctx.beginPath();
        ctx.strokeStyle = color;
        ctx.lineWidth   = 2.5;
        ctx.lineJoin    = 'round';
        for (let i = 0; i < N; i++) {
            const [x, y] = getPoint(i);
            if (i === 0) {
                ctx.moveTo(x, y);
            } else {
                const [px, py] = getPoint(i - 1);
                const cpx = (px + x) / 2;
                ctx.bezierCurveTo(cpx, py, cpx, y, x, y);
            }
        }
        ctx.stroke();

        // --- dots & value labels ---
        for (let i = 0; i < N; i++) {
            const [x, y] = getPoint(i);
            const v      = nums[i] || 0;

            // outer ring
            ctx.beginPath();
            ctx.arc(x, y, 5, 0, Math.PI * 2);
            ctx.fillStyle = '#fff';
            ctx.fill();
            ctx.strokeStyle = color;
            ctx.lineWidth   = 2;
            ctx.stroke();

            // value on top
            if (v > 0) {
                ctx.fillStyle   = '#334155';
                ctx.font        = 'bold 10px "Segoe UI", Arial, sans-serif';
                ctx.textAlign   = 'center';
                ctx.fillText(formatRs(v), x, y - 10);
            }
        }

        // --- x-axis labels ---
        ctx.fillStyle  = '#64748b';
        ctx.font       = '11px "Segoe UI", Arial, sans-serif';
        ctx.textAlign  = 'center';
        for (let i = 0; i < N; i++) {
            const [x] = getPoint(i);
            ctx.fillText(labels[i] || '', x, pad.top + H + 18);
        }

        // --- today marker (last bar) ---
        if (N > 0) {
            const [tx, ty] = getPoint(N - 1);
            ctx.beginPath();
            ctx.arc(tx, ty, 5, 0, Math.PI * 2);
            ctx.fillStyle = color;
            ctx.fill();
        }

        ctx.textAlign = 'left';
    };

    // Auto re-render on resize
    window.addEventListener('resize', function () {
        document.querySelectorAll('[data-chart-labels]').forEach(function (canvas) {
            try {
                const labels = JSON.parse(canvas.dataset.chartLabels);
                const values = JSON.parse(canvas.dataset.chartValues);
                window.renderOfflineBarChart(canvas, labels, values);
            } catch (e) {}
        });
    });
})();
