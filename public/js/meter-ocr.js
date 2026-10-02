/**
 * meter-ocr.js
 * Free in-browser electricity meter OCR using tesseract.js (WASM).
 * - Loads tesseract.js + English language data ONCE from CDN
 * - Preprocesses image: upscale → grayscale → percentile contrast stretch → Otsu binarize
 * - Runs OCR twice (raw + binarized), picks the better candidate
 * - Corrects 7-segment digit confusions (O→0, I→1, S→5, B→8…)
 * - Filters to plausible meter readings (4-7 digits = kWh)
 *
 * Exposed as `window.MeterOCR`.
 */
(function () {
    let workerPromise = null;
    const TESSERACT_VERSION = '7.2.4';
    const CDN = `https://cdn.jsdelivr.net/npm/tesseract.js@${TESSERACT_VERSION}/dist`;

    function loadScript(src) {
        return new Promise((resolve, reject) => {
            if (document.querySelector(`script[src="${src}"]`)) return resolve();
            const s = document.createElement('script');
            s.src = src; s.async = true;
            s.onload = () => resolve();
            s.onerror = () => reject(new Error('Failed to load ' + src));
            document.head.appendChild(s);
        });
    }

    async function getWorker() {
        if (workerPromise) return workerPromise;
        workerPromise = (async () => {
            await loadScript(`${CDN}/tesseract.min.js`);
            const w = await Tesseract.createWorker('eng', 1, {
                logger: () => {},
                workerPath: `${CDN}/worker.min.js`,
                corePath: `https://cdn.jsdelivr.net/npm/tesseract.js-core@5.0.0/tesseract-simd.wasm.js`,
            });
            await w.setParameters({
                tessedit_char_whitelist: '0123456789OoIlSsBbGg',
                tessedit_pageseg_mode: '6', // assume uniform block of text
            });
            return w;
        })();
        return workerPromise;
    }

    function preprocessImage(source, { binarize = false, maxWidth = 1000 } = {}) {
        // source: HTMLCanvasElement or HTMLImageElement
        const canvas = document.createElement('canvas');
        const w = Math.min(source.width || source.naturalWidth, maxWidth);
        const scale = w / (source.width || source.naturalWidth);
        const h = (source.height || source.naturalHeight) * scale;
        canvas.width = w; canvas.height = h;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(source, 0, 0, w, h);

        const imageData = ctx.getImageData(0, 0, w, h);
        const d = imageData.data;
        // Grayscale
        const gray = new Uint8Array(w * h);
        for (let i = 0; i < w * h; i++) {
            const r = d[i * 4], g = d[i * 4 + 1], b = d[i * 4 + 2];
            gray[i] = (r * 0.299 + g * 0.587 + b * 0.114) | 0;
        }

        // Percentile contrast stretch (2%)
        const hist = new Array(256).fill(0);
        for (let i = 0; i < gray.length; i++) hist[gray[i]]++;
        const total = gray.length;
        let lo = 0, hi = 255, acc = 0;
        for (let i = 0; i < 256; i++) {
            acc += hist[i];
            if (acc >= total * 0.02) { lo = i; break; }
        }
        acc = 0;
        for (let i = 255; i >= 0; i--) {
            acc += hist[i];
            if (acc >= total * 0.02) { hi = i; break; }
        }
        if (hi <= lo) { hi = 255; lo = 0; }
        for (let i = 0; i < gray.length; i++) {
            const v = ((gray[i] - lo) * 255) / (hi - lo);
            gray[i] = v < 0 ? 0 : v > 255 ? 255 : v;
        }

        if (binarize) {
            const threshold = computeOtsu(gray);
            for (let i = 0; i < gray.length; i++) {
                gray[i] = gray[i] > threshold ? 255 : 0;
            }
        }

        // Write back to canvas
        for (let i = 0; i < w * h; i++) {
            const v = gray[i];
            d[i * 4] = v; d[i * 4 + 1] = v; d[i * 4 + 2] = v; d[i * 4 + 3] = 255;
        }
        ctx.putImageData(imageData, 0, 0);
        return canvas;
    }

    function computeOtsu(gray) {
        const hist = new Array(256).fill(0);
        for (let i = 0; i < gray.length; i++) hist[gray[i]]++;
        const total = gray.length;
        let sum = 0;
        for (let i = 0; i < 256; i++) sum += i * hist[i];
        let sumB = 0, wB = 0, maxVar = 0, threshold = 128;
        for (let t = 0; t < 256; t++) {
            wB += hist[t];
            if (wB === 0) continue;
            const wF = total - wB;
            if (wF === 0) break;
            sumB += t * hist[t];
            const mB = sumB / wB;
            const mF = (sum - sumB) / wF;
            const between = wB * wF * (mB - mF) * (mB - mF);
            if (between > maxVar) { maxVar = between; threshold = t; }
        }
        return threshold;
    }

    function fixDigitConfusions(token) {
        // Common 7-segment OCR confusions
        return token
            .replace(/O/g, '0').replace(/o/g, '0')
            .replace(/I/g, '1').replace(/l/g, '1')
            .replace(/S/g, '5').replace(/s/g, '5')
            .replace(/B/g, '8').replace(/b/g, '6')
            .replace(/G/g, '6').replace(/g/g, '9')
            .replace(/Z/g, '2').replace(/z/g, '2')
            .replace(/D/g, '0')
            .replace(/[^0-9]/g, '');
    }

    function plausibleNumberTokens(text) {
        // Pull out runs of 4-7 digit-like characters (typical kWh meter reading)
        const tokens = text.match(/[0-9OoIlSsBbGgZzD]{4,7}/g) || [];
        return tokens.map(t => ({
            raw: t,
            cleaned: fixDigitConfusions(t),
        })).filter(t => t.cleaned.length >= 4 && t.cleaned.length <= 7);
    }

    /**
     * @param {HTMLCanvasElement|HTMLImageElement} source
     * @param {(progress:number)=>void} [onProgress]
     * @returns {Promise<{reading:number|null, rawText:string, candidates:Array}>}
     */
    async function recognizeMeter(source, onProgress) {
        const worker = await getWorker();

        const candidates = [];
        for (const binarize of [false, true]) {
            const canvas = preprocessImage(source, { binarize });
            const { data } = await worker.recognize(canvas);
            if (onProgress) onProgress(binarize ? 0.9 : 0.5);
            const tokens = plausibleNumberTokens(data.text || '');
            for (const t of tokens) {
                candidates.push({
                    raw: t.raw,
                    cleaned: t.cleaned,
                    value: parseInt(t.cleaned, 10),
                    confidence: data.confidence / 100,
                    binarized: binarize,
                });
            }
        }

        // Sort: prefer longer tokens, higher confidence
        candidates.sort((a, b) => {
            if (b.cleaned.length !== a.cleaned.length) return b.cleaned.length - a.cleaned.length;
            return b.confidence - a.confidence;
        });

        if (candidates.length === 0) {
            return { reading: null, rawText: '', candidates: [] };
        }
        return {
            reading: candidates[0].value,
            rawText: candidates[0].raw,
            candidates,
        };
    }

    async function dispose() {
        if (workerPromise) {
            try {
                const w = await workerPromise;
                await w.terminate();
            } catch (e) {}
            workerPromise = null;
        }
    }

    window.MeterOCR = { recognizeMeter, dispose, preprocessImage };
})();
