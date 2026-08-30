const enterBtn = document.getElementById("enterBtn");
(() => {
    const SIZE = 36;
    const GAP = 3;
    const STEP = SIZE + GAP;
    const cubeEl = document.getElementById('rubik-cube');

    function multiply(a, b) {
      const r = new Array(16).fill(0);
      for (let row = 0; row < 4; row++) {
        for (let col = 0; col < 4; col++) {
          let sum = 0;
          for (let k = 0; k < 4; k++) sum += a[row*4+k] * b[k*4+col];
          r[row*4+col] = sum;
        }
      }
      return r;
    }
    function translationMatrix(x, y, z) {
      return [1,0,0,x, 0,1,0,y, 0,0,1,z, 0,0,0,1];
    }
    function rotationMatrix(axis, deg) {
      const rad = deg * Math.PI / 180;
      const c = Math.cos(rad), s = Math.sin(rad);
      if (axis === 'x') return [1,0,0,0, 0,c,-s,0, 0,s,c,0, 0,0,0,1];
      if (axis === 'y') return [c,0,s,0, 0,1,0,0, -s,0,c,0, 0,0,0,1];
      return [c,-s,0,0, s,c,0,0, 0,0,1,0, 0,0,0,1];
    }
    function toCssMatrix3d(m) {
      const t = [
        m[0], m[4], m[8], m[12],
        m[1], m[5], m[9], m[13],
        m[2], m[6], m[10], m[14],
        m[3], m[7], m[11], m[15]
      ];
      return `matrix3d(${t.map(v => v.toFixed(5)).join(',')})`;
    }
    function getTranslation(m) {
      return [m[3], m[7], m[11]];
    }
    function roundMatrix(m) {
      return m.map(v => {
        const r = Math.round(v);
        return Math.abs(v - r) < 1e-4 ? r : v;
      });
    }

    const cubies = [];
    for (let x = -1; x <= 1; x++) {
      for (let y = -1; y <= 1; y++) {
        for (let z = -1; z <= 1; z++) {
          const el = document.createElement('div');
          el.className = 'cubie';

          const faces = [
            { transform: `translateZ(${SIZE/2}px)`, white: false },
            { transform: `rotateY(180deg) translateZ(${SIZE/2}px)`, white: true },
            { transform: `rotateY(90deg) translateZ(${SIZE/2}px)`, white: false },
            { transform: `rotateY(-90deg) translateZ(${SIZE/2}px)`, white: true },
            { transform: `rotateX(90deg) translateZ(${SIZE/2}px)`, white: false },
            { transform: `rotateX(-90deg) translateZ(${SIZE/2}px)`, white: true },
          ];

          faces.forEach(f => {
            const faceEl = document.createElement('div');
            faceEl.className = 'cubie-face' + (f.white ? ' face-white' : '');
            faceEl.style.transform = f.transform;
            el.appendChild(faceEl);
          });

          cubeEl.appendChild(el);
          cubies.push({
            el,
            matrix: translationMatrix(x * STEP, y * STEP, z * STEP)
          });
        }
      }
    }

    function render() {
      cubies.forEach(c => {
        c.el.style.transform = toCssMatrix3d(c.matrix);
      });
    }
    render();

    function turnLayer(axis, layerCoord, direction, duration = 220) {
      return new Promise(resolve => {
        const idx = axis === 'x' ? 0 : axis === 'y' ? 1 : 2;
        const target = cubies.filter(c => {
          const t = getTranslation(c.matrix)[idx];
          return Math.abs(t - layerCoord * STEP) < 1;
        });

        const start = performance.now();
        const totalDeg = 90 * direction;

        function step(now) {
          const elapsed = now - start;
          const progress = Math.min(elapsed / duration, 1);
          const eased = 1 - Math.pow(1 - progress, 3);
          const deg = totalDeg * eased - (step.lastDeg || 0);
          step.lastDeg = totalDeg * eased;

          const rot = rotationMatrix(axis, deg);
          target.forEach(c => {
            c.matrix = multiply(rot, c.matrix);
          });
          render();

          if (progress < 1) {
            requestAnimationFrame(step);
          } else {
            target.forEach(c => {
              c.matrix = roundMatrix(c.matrix);
            });
            render();
            resolve();
          }
        }
        requestAnimationFrame(step);
      });
    }

    const axes = ['x', 'y', 'z'];
    const layers = [-1, 0, 1];

    async function playSequence() {
      while (true) {
        const axis = axes[Math.floor(Math.random() * axes.length)];
        const layer = layers[Math.floor(Math.random() * layers.length)];
        const dir = Math.random() < 0.5 ? 1 : -1;
        await turnLayer(axis, layer, dir);
        await new Promise(r => setTimeout(r, 80));
      }
    }

    playSequence();
  })();

  enterBtn.addEventListener("click", ()=>{
    window.location.href = "sign-up.html";
  })