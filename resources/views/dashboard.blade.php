@extends('layouts.app')
@section('title', 'لوحة التحكم')

@section('body')
<div class="min-h-screen bg-gradient-to-br from-cyan-50 via-white to-emerald-50 dark:from-slate-950 dark:via-slate-900 dark:to-emerald-950/30" x-data="dashboard()">
    <!-- Top bar -->
    <header class="sticky top-0 z-10 backdrop-blur-md bg-white/80 dark:bg-slate-900/80 border-b border-cyan-100 dark:border-slate-800 px-4 py-3">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center text-white" style="background: linear-gradient(135deg, #06b6d4 0%, #10b981 100%);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path>
                        <path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"></path>
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100">تعبئة المياه</h1>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-600 dark:text-gray-300 hidden sm:inline">{{ $user->name ?? $user->email }}</span>
                <form method="POST" action="/logout">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 text-sm rounded-md border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800">خروج</button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto p-4 sm:p-6 space-y-6">
        <!-- Weekly usage card -->
        <section class="rounded-2xl shadow-sm border-0 bg-white dark:bg-slate-900 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">الاستهلاك الأسبوعي</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">بدء الأسبوع: {{ $weekStart->format('Y-m-d') }}</p>
                </div>
                <div class="text-right">
                    <p class="text-3xl font-bold text-cyan-600 dark:text-cyan-400">{{ $usedMinutes }}<span class="text-sm font-normal text-gray-500 dark:text-gray-400">/{{ $settings->free_minutes_per_week }} دقيقة</span></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">المتبقي: {{ $remainingMinutes }} دقيقة</p>
                </div>
            </div>
            <div class="h-3 bg-gray-100 dark:bg-slate-800 rounded-full overflow-hidden">
                <div class="h-full transition-all" style="background: linear-gradient(90deg, #06b6d4, #10b981); width: {{ $usedPct }}%;"></div>
            </div>
        </section>

        <!-- Settings -->
        <section class="rounded-2xl shadow-sm border-0 bg-white dark:bg-slate-900 p-6">
            <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-4">إعدادات الكهرباء</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-300">تعرفة الكهرباء (₪/kWh)</label>
                    <input type="number" step="0.01" x-model="settings.electricityTariff"
                           class="w-full h-9 rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 px-3 mt-1">
                </div>
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-300">سحب الغاطس (kW)</label>
                    <input type="number" step="0.1" x-model="settings.enginePowerKw"
                           class="w-full h-9 rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 px-3 mt-1">
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-3 items-center">
                <button @click="saveSettings()" class="px-4 py-2 rounded-md text-white font-medium" style="background: linear-gradient(135deg, #06b6d4 0%, #10b981 100%);">حفظ</button>
                <div x-show="estimate.estimatedPricePerMin !== null" class="text-sm text-gray-700 dark:text-gray-300">
                    سعر الدقيقة التقديري: <strong x-text="estimate.estimatedPricePerMin"></strong> ₪/دقيقة
                </div>
                <div x-show="estimate.lastActual" class="text-sm text-gray-700 dark:text-gray-300">
                    آخر فعلي: <strong x-text="estimate.lastActual?.pricePerMinute"></strong> ₪/دقيقة
                    (<span x-text="estimate.lastActual?.kwhConsumed"></span> kWh، <span x-text="estimate.lastActual?.electricityCost"></span> ₪)
                </div>
            </div>
        </section>

        <!-- Families list -->
        <section class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">العائلات</h2>
                <button @click="addFamily()" class="px-4 py-2 rounded-md text-white text-sm font-medium" style="background: linear-gradient(135deg, #06b6d4 0%, #10b981 100%);">+ إضافة عائلة</button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="fam in families" :key="fam.id">
                    <div class="rounded-2xl shadow-sm border border-gray-100 dark:border-slate-700 bg-white dark:bg-slate-900 p-5">
                        <div class="flex items-start justify-between">
                            <h3 class="font-bold text-gray-800 dark:text-gray-100" x-text="fam.name"></h3>
                            <button @click="deleteFamily(fam)" class="text-xs text-red-500 hover:text-red-700">حذف</button>
                        </div>

                        <!-- Active session indicator -->
                        <div class="mt-3 text-sm">
                            <template x-if="fam.activeSession">
                                <div class="text-cyan-600 dark:text-cyan-400">
                                    نشطة منذ <span x-text="Math.floor(fam.activeDurationSec / 60)"></span> دقيقة
                                </div>
                            </template>
                            <template x-if="!fam.activeSession">
                                <div class="text-gray-400 dark:text-gray-500">لا توجد جلسة نشطة</div>
                            </template>
                        </div>

                        <!-- Action buttons -->
                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <button @click="openMeterReader(fam, 'BEFORE')"
                                    class="px-3 py-2 rounded-md text-xs font-medium border border-cyan-200 text-cyan-700 dark:text-cyan-300 dark:border-cyan-700 hover:bg-cyan-50 dark:hover:bg-cyan-900/20">
                                📷 قبل التعبئة
                            </button>
                            <button @click="openMeterReader(fam, 'AFTER')"
                                    class="px-3 py-2 rounded-md text-xs font-medium border border-emerald-200 text-emerald-700 dark:text-emerald-300 dark:border-emerald-700 hover:bg-emerald-50 dark:hover:bg-emerald-900/20">
                                📷 بعد التعبئة
                            </button>
                            <template x-if="!fam.activeSession">
                                <button @click="startSession(fam)"
                                        class="col-span-2 px-3 py-2 rounded-md text-white text-sm font-medium"
                                        style="background: linear-gradient(135deg, #06b6d4 0%, #10b981 100%);">
                                    ▶ بدء التعبئة
                                </button>
                            </template>
                            <template x-if="fam.activeSession">
                                <button @click="stopSession(fam)"
                                        class="col-span-2 px-3 py-2 rounded-md text-white text-sm font-medium bg-red-500 hover:bg-red-600">
                                    ⏹ إيقاف
                                </button>
                            </template>
                        </div>

                        <!-- Last session -->
                        <div class="mt-4 pt-3 border-t border-gray-100 dark:border-slate-700 text-xs text-gray-500 dark:text-gray-400">
                            <template x-if="fam.lastSession">
                                <div>
                                    آخر جلسة: <span x-text="fam.lastSession.kwhConsumed"></span> kWh،
                                    <span x-text="fam.lastSession.electricityCost"></span> ₪،
                                    <span x-text="fam.lastSession.pricePerMinute"></span> ₪/دقيقة
                                </div>
                            </template>
                            <template x-if="!fam.lastSession">
                                <div>لا توجد جلسات بعد</div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
            <div x-show="families.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-8">
                لا توجد عائلات بعد. أضف أول عائلة!
            </div>
        </section>
    </main>

    <!-- Meter Reader Modal -->
    <div x-show="meterReader.open" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
         @keydown.escape="closeMeterReader()">
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 max-w-md w-full max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">
                    قراءة العداد — <span x-text="meterReader.phase === 'BEFORE' ? 'قبل التعبئة' : 'بعد التعبئة'"></span>
                </h3>
                <button @click="closeMeterReader()" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>

            <div class="space-y-3">
                <div class="grid grid-cols-2 gap-2">
                    <button @click="captureFromCamera()"
                            class="px-3 py-3 rounded-md text-sm font-medium border border-cyan-200 text-cyan-700 dark:text-cyan-300 dark:border-cyan-700 hover:bg-cyan-50 dark:hover:bg-cyan-900/20">
                        📷 من الكاميرا
                    </button>
                    <label class="px-3 py-3 rounded-md text-sm font-medium border border-gray-200 text-gray-700 dark:text-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer text-center">
                        📁 من الملف
                        <input type="file" accept="image/*" class="hidden" @change="captureFromFile($event)">
                    </label>
                </div>

                <canvas x-ref="canvas" class="hidden"></canvas>
                <img x-ref="preview" class="w-full rounded-lg max-h-48 object-contain bg-gray-100 dark:bg-slate-800" />

                <div x-show="meterReader.ocrProgress > 0" class="text-xs text-cyan-600 dark:text-cyan-400">
                    جاري القراءة... <span x-text="meterReader.ocrProgress"></span>%
                </div>

                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-300">القراءة (kWh)</label>
                    <input type="number" step="0.1" x-model="meterReader.reading"
                           class="w-full h-9 rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 px-3 mt-1">
                </div>

                <div class="text-xs text-gray-500 dark:text-gray-400" x-show="estimate.estimatedPricePerMin !== null">
                    سعر الدقيقة التقديري: <strong x-text="estimate.estimatedPricePerMin"></strong> ₪/دقيقة
                </div>

                <button @click="saveReading()"
                        :disabled="meterReader.saving || meterReader.reading === ''"
                        class="w-full h-11 rounded-md text-white font-semibold disabled:opacity-50"
                        style="background: linear-gradient(135deg, #06b6d4 0%, #10b981 100%);">
                    <span x-show="!meterReader.saving">حفظ القراءة</span>
                    <span x-show="meterReader.saving">جاري الحفظ...</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
window.__INITIAL_DATA__ = @json($initialData);
</script>
<script src="/js/meter-ocr.js"></script>
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function dashboard() {
    return {
        families: window.__INITIAL_DATA__.families.map(f => ({
            ...f,
            activeSession: !!f.activeSession,
            activeDurationSec: 0,
            lastSession: f.lastSession,
        })),
        settings: {
            electricityTariff: window.__INITIAL_DATA__.settings.electricity_tariff,
            enginePowerKw: window.__INITIAL_DATA__.settings.engine_power_kw,
        },
        estimate: { estimatedPricePerMin: null, lastActual: null },
        meterReader: {
            open: false, phase: 'BEFORE', familyId: null,
            reading: '', saving: false, ocrProgress: 0,
        },

        init() {
            this.loadEstimate();
            // Refresh active durations every second
            setInterval(() => this.tickDurations(), 1000);
        },

        async tickDurations() {
            // For demo: we don't track exact start time client-side, just visual
        },

        async loadEstimate() {
            if (this.families.length === 0) return;
            const famId = this.families[0].id;
            const res = await fetch(`/api/electricity/estimate-price?familyId=${famId}`);
            if (res.ok) this.estimate = await res.json();
        },

        async addFamily() {
            const name = prompt('اسم العائلة:');
            if (!name) return;
            const res = await fetch('/api/families', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
                body: JSON.stringify({ name })
            });
            if (res.ok) {
                const fam = await res.json();
                this.families.unshift({ id: fam.id, name: fam.name, activeSession: false, lastSession: null });
            } else {
                alert('فشل إضافة العائلة');
            }
        },

        async deleteFamily(fam) {
            if (!confirm(`حذف العائلة "${fam.name}"؟`)) return;
            const res = await fetch(`/api/families/${fam.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }
            });
            if (res.ok) this.families = this.families.filter(f => f.id !== fam.id);
        },

        async startSession(fam) {
            const res = await fetch('/api/sessions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
                body: JSON.stringify({ familyId: fam.id })
            });
            if (res.ok) {
                fam.activeSession = true;
                fam.activeDurationSec = 0;
                const tick = setInterval(() => {
                    if (!fam.activeSession) clearInterval(tick);
                    else fam.activeDurationSec++;
                }, 1000);
            } else {
                const err = await res.json();
                alert(err.error || 'فشل بدء الجلسة');
            }
        },

        async stopSession(fam) {
            const active = await (await fetch(`/api/sessions?familyId=${fam.id}`)).json();
            const activeSession = active.sessions.find(s => s.end_time === null);
            if (!activeSession) {
                fam.activeSession = false;
                return;
            }
            const res = await fetch('/api/sessions', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
                body: JSON.stringify({ sessionId: activeSession.id })
            });
            if (res.ok) {
                const updated = await res.json();
                fam.activeSession = false;
                fam.lastSession = updated;
                this.loadEstimate();
            }
        },

        openMeterReader(fam, phase) {
            this.meterReader = {
                open: true, phase, familyId: fam.id,
                reading: '', saving: false, ocrProgress: 0,
            };
            this.$refs.preview.src = '';
        },

        closeMeterReader() {
            this.meterReader.open = false;
            MeterOCR.dispose();
        },

        async captureFromCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserVideo?.() || await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                const video = document.createElement('video');
                video.srcObject = stream;
                video.play();
                const canvas = document.createElement('canvas');
                video.addEventListener('loadedmetadata', async () => {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    canvas.getContext('2d').drawImage(video, 0, 0);
                    stream.getTracks().forEach(t => t.stop());
                    this.$refs.preview.src = canvas.toDataURL('image/jpeg', 0.7);
                    this.runOCR(canvas);
                });
            } catch (err) {
                alert('تعذّر الوصول للكاميرا: ' + err.message);
            }
        },

        async captureFromFile(event) {
            const file = event.target.files[0];
            if (!file) return;
            const url = URL.createObjectURL(file);
            const img = new Image();
            img.onload = async () => {
                const canvas = document.createElement('canvas');
                canvas.width = img.width;
                canvas.height = img.height;
                canvas.getContext('2d').drawImage(img, 0, 0);
                this.$refs.preview.src = url;
                this.runOCR(canvas);
            };
            img.src = url;
        },

        async runOCR(canvas) {
            this.meterReader.ocrProgress = 1;
            try {
                const result = await MeterOCR.recognizeMeter(canvas, (p) => {
                    this.meterReader.ocrProgress = Math.round(p * 100);
                });
                if (result.reading !== null) {
                    this.meterReader.reading = String(result.reading);
                } else {
                    alert('لم يتم التعرف على رقم — أدخله يدوياً');
                }
            } catch (err) {
                alert('فشل OCR: ' + err.message);
            } finally {
                this.meterReader.ocrProgress = 0;
            }
        },

        async saveReading() {
            if (this.meterReader.reading === '') return;
            this.meterReader.saving = true;
            try {
                // Find active session for AFTER phase
                let sessionId = null;
                if (this.meterReader.phase === 'AFTER') {
                    const res = await fetch(`/api/sessions?familyId=${this.meterReader.familyId}`);
                    if (res.ok) {
                        const data = await res.json();
                        const active = data.sessions.find(s => s.end_time === null);
                        if (active) sessionId = active.id;
                    }
                }

                const res = await fetch('/api/electricity', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
                    body: JSON.stringify({
                        familyId: this.meterReader.familyId,
                        reading: parseFloat(this.meterReader.reading),
                        phase: this.meterReader.phase,
                        source: 'OCR',
                        sessionId: sessionId,
                    })
                });
                if (res.ok) {
                    alert('تم حفظ القراءة بنجاح');
                    this.closeMeterReader();
                    this.loadEstimate();
                } else {
                    const err = await res.json();
                    alert('فشل الحفظ: ' + (err.error || JSON.stringify(err)));
                }
            } finally {
                this.meterReader.saving = false;
            }
        },

        async saveSettings() {
            const res = await fetch('/api/settings', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
                body: JSON.stringify({
                    electricityTariff: parseFloat(this.settings.electricityTariff),
                    enginePowerKw: parseFloat(this.settings.enginePowerKw),
                })
            });
            if (res.ok) {
                alert('تم حفظ الإعدادات');
                this.loadEstimate();
            } else {
                alert('فشل الحفظ');
            }
        },
    };
}
</script>
@endsection
