<?php

use Livewire\Component;
use App\Models\DoorprizeWinner;
use App\Models\Event;
use App\Models\FamilyMember;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

new class extends Component
{
    public ?string $eventId = null;
    public ?string $eventName = null;
    public ?string $siteLogoUrl = null;
    public string $status = 'idle';
    public ?string $prizeName = null;
    public ?array $winner = null;
    public array $poolNames = [];
    public int $poolCount = 0;

    public function mount()
    {
        $event = Event::where('status', 'active')->latest('start_date')->first()
            ?? Event::latest('start_date')->first();
        $site = SiteSetting::current();

        $this->eventId = $event?->id;
        $this->eventName = $event?->name ?: ($site->site_name ?: config('app.name'));
        $this->siteLogoUrl = $site->logo_url;

        $this->poll();
    }

    protected function cacheKey(): string
    {
        return "doorprize:state:{$this->eventId}";
    }

    protected function eligibleHeadsQuery()
    {
        $wonIds = DoorprizeWinner::where('event_id', $this->eventId)->pluck('family_member_id')->all();

        return FamilyMember::where('event_id', $this->eventId)
            ->whereNotNull('registration_number')
            ->whereHas('familySubmission', fn ($q) => $q->where('status', '!=', 'rejected'))
            ->with('familySubmission:id,resident_block')
            ->get()
            ->groupBy('family_submission_id')
            ->map(fn ($members) => $members->sortBy(fn ($m) => (int) $m->registration_number)->first())
            ->reject(fn ($m) => in_array($m->id, $wonIds, true));
    }

    protected function currentPoolNames(): array
    {
        if (! $this->eventId) {
            return [];
        }

        return $this->eligibleHeadsQuery()
            ->map(fn ($m) => [
                'registration_number' => $m->registration_number,
                'name' => $m->name,
                'resident_block' => $m->familySubmission?->resident_block,
            ])
            ->values()
            ->all();
    }

    public function poll(): void
    {
        if (! $this->eventId) {
            $this->status = 'idle';
            return;
        }

        $state = Cache::get($this->cacheKey(), ['status' => 'idle']);
        $newStatus = $state['status'] ?? 'idle';

        // Ambil daftar kandidat sekali saja tiap kali putaran baru mulai, dipakai
        // buat animasi acak visual (bukan sumber keputusan pemenang).
        if ($newStatus === 'spinning' && $this->status !== 'spinning') {
            $this->poolNames = $this->currentPoolNames();
        }

        $this->status = $newStatus;
        $this->prizeName = $state['prize_name'] ?? null;
        $this->winner = $state['winner'] ?? null;
        $this->poolCount = $this->eligibleHeadsQuery()->count();
    }
};
?>

<div class="relative flex min-h-screen flex-col overflow-hidden" wire:poll.700ms="poll" x-data="doorprizeDisplay()" x-init="init()">
    {{-- Dekorasi latar: bintik cahaya + sorot lembut, murni CSS --}}
    <div class="pointer-events-none absolute inset-0" style="background-image: radial-gradient(circle, rgba(255,255,255,.10) 1px, transparent 1px); background-size: 26px 26px;"></div>
    <div class="pointer-events-none absolute -left-32 -top-32 h-96 w-96 rounded-full bg-amber-400/20 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-32 -right-32 h-96 w-96 rounded-full bg-red-500/30 blur-3xl"></div>

    <button
        @click="toggleSound"
        class="fixed right-4 top-4 z-20 rounded-full border border-white/20 bg-black/30 px-3 py-2 text-sm text-white/80 hover:bg-black/50"
        x-text="soundEnabled ? '🔊' : '🔇 Aktifkan Suara'"
    ></button>

    <div class="relative flex flex-1 flex-col items-center justify-center px-6 py-10 text-center">
        @if ($siteLogoUrl)
            <img src="{{ $siteLogoUrl }}" alt="Logo" class="mb-3 h-16 w-auto object-contain drop-shadow-lg" style="filter: brightness(0) invert(1);">
        @endif
        <p class="text-sm font-semibold uppercase tracking-[0.3em] text-red-200">{{ $eventName ?: 'Belum ada acara aktif' }}</p>
        <h1 class="mt-2 text-3xl font-black tracking-wide text-white drop-shadow-lg sm:text-5xl">🎉 UNDIAN DOORPRIZE 🎉</h1>

        <div
            class="relative mt-10 w-full max-w-2xl rounded-3xl border-4 p-8 shadow-2xl backdrop-blur-sm transition-colors duration-500 sm:p-14"
            :class="phase === 'revealed' ? 'border-emerald-300 bg-emerald-900/20 shadow-emerald-400/30' : 'border-amber-300 bg-white/10'"
            x-bind:style="phase === 'spinning' ? 'animation: doorprize-pulse 1s ease-in-out infinite' : ''"
        >
            <p x-show="phase === 'idle'" class="text-base font-semibold uppercase tracking-widest text-red-200">🎁 Menunggu Diundi</p>
            <p x-show="phase === 'spinning'" class="text-base font-semibold uppercase tracking-widest text-amber-200">🎰 Sedang Mengundi...</p>
            <p x-show="phase === 'suspense'" class="animate-pulse text-base font-semibold uppercase tracking-widest text-amber-200">Menentukan pemenang...</p>
            <p x-show="phase === 'revealed'" class="text-base font-semibold uppercase tracking-widest text-emerald-300">🏆 Pemenang</p>

            <div class="mt-5 font-mono text-6xl font-black tabular-nums text-white sm:text-8xl" x-text="displayNumber"></div>
            <div class="mt-3 min-h-[2.25rem] text-2xl font-bold text-amber-200 sm:text-4xl" x-text="displayName"></div>
            <div class="mt-1 text-sm text-red-200" x-text="displayBlock"></div>
        </div>

        <div class="mt-6 min-h-[1.5rem] text-lg font-semibold text-amber-200" x-show="prizeName" x-text="prizeName ? ('🎁 Hadiah: ' + prizeName) : ''"></div>
        <p class="mt-3 text-xs uppercase tracking-widest text-red-300/80">Sisa {{ $poolCount }} kepala keluarga belum menang</p>
    </div>

    <style>
        @keyframes doorprize-pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(252, 211, 77, .45); }
            50% { box-shadow: 0 0 0 18px rgba(252, 211, 77, 0); }
        }
    </style>

    <script>
        function doorprizeDisplay() {
            return {
                phase: 'idle', // idle | spinning | suspense | revealed
                lastStatus: 'idle',
                displayNumber: '----',
                displayName: '',
                displayBlock: '',
                prizeName: '',
                spinTimer: null,
                decelTimer: null,
                tickCounter: 0,
                soundEnabled: false,
                audioCtx: null,

                init() {
                    this.prizeName = this.$wire.prizeName || '';
                    this.$watch('$wire.status', (value) => this.onStatusChange(value));
                    this.onStatusChange(this.$wire.status);
                },

                toggleSound() {
                    if (!this.audioCtx) {
                        this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                    }
                    if (this.audioCtx.state === 'suspended') {
                        this.audioCtx.resume();
                    }
                    this.soundEnabled = !this.soundEnabled;
                },

                playTick() {
                    if (!this.soundEnabled || !this.audioCtx) return;
                    const ctx = this.audioCtx;
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'square';
                    osc.frequency.value = 340;
                    gain.gain.setValueAtTime(0.05, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.06);
                    osc.connect(gain).connect(ctx.destination);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.06);
                },

                playFanfare() {
                    if (!this.soundEnabled || !this.audioCtx) return;
                    const ctx = this.audioCtx;
                    const notes = [523.25, 659.25, 783.99, 1046.50];
                    notes.forEach((freq, i) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'triangle';
                        osc.frequency.value = freq;
                        const t = ctx.currentTime + i * 0.15;
                        gain.gain.setValueAtTime(0.0001, t);
                        gain.gain.linearRampToValueAtTime(0.15, t + 0.02);
                        gain.gain.exponentialRampToValueAtTime(0.0001, t + 0.6);
                        osc.connect(gain).connect(ctx.destination);
                        osc.start(t);
                        osc.stop(t + 0.65);
                    });
                },

                onStatusChange(status) {
                    this.prizeName = this.$wire.prizeName || '';

                    if (status === 'spinning' && this.lastStatus !== 'spinning') {
                        this.startSpin();
                    } else if (status === 'revealed' && this.lastStatus !== 'revealed') {
                        this.decelerateAndReveal();
                    } else if (status === 'idle') {
                        this.reset();
                    }

                    this.lastStatus = status;
                },

                startSpin() {
                    clearInterval(this.spinTimer);
                    clearTimeout(this.decelTimer);
                    this.phase = 'spinning';
                    this.tickCounter = 0;

                    this.spinTimer = setInterval(() => {
                        const pool = this.$wire.poolNames;
                        if (!pool || !pool.length) return;
                        const c = pool[Math.floor(Math.random() * pool.length)];
                        this.displayNumber = c.registration_number;
                        this.displayName = c.name;
                        this.displayBlock = c.resident_block ? ('Blok ' + c.resident_block) : '';

                        this.tickCounter++;
                        if (this.tickCounter % 2 === 0) this.playTick();
                    }, 80);
                },

                decelerateAndReveal() {
                    clearInterval(this.spinTimer);
                    this.phase = 'spinning';

                    let delay = 80;
                    const pool = (this.$wire.poolNames && this.$wire.poolNames.length) ? this.$wire.poolNames : [];

                    const tick = () => {
                        if (pool.length) {
                            const c = pool[Math.floor(Math.random() * pool.length)];
                            this.displayNumber = c.registration_number;
                            this.displayName = c.name;
                            this.displayBlock = c.resident_block ? ('Blok ' + c.resident_block) : '';
                        }
                        this.playTick();

                        delay += 60;

                        if (delay < 900) {
                            this.decelTimer = setTimeout(tick, delay);
                            return;
                        }

                        // jeda hening sebelum reveal biar dramatis
                        this.phase = 'suspense';
                        this.displayNumber = '···';
                        this.displayName = '';
                        this.displayBlock = '';

                        this.decelTimer = setTimeout(() => {
                            const w = this.$wire.winner;
                            if (w) {
                                this.displayNumber = w.registration_number;
                                this.displayName = w.name;
                                this.displayBlock = w.resident_block ? ('Blok ' + w.resident_block) : '';
                            }
                            this.phase = 'revealed';
                            this.playFanfare();
                            this.burstConfetti();
                        }, 1200);
                    };

                    tick();
                },

                reset() {
                    clearInterval(this.spinTimer);
                    clearTimeout(this.decelTimer);
                    this.phase = 'idle';
                    this.displayNumber = '----';
                    this.displayName = '';
                    this.displayBlock = '';
                },

                burstConfetti() {
                    let canvas = document.getElementById('doorprize-confetti-canvas');
                    if (!canvas) {
                        canvas = document.createElement('canvas');
                        canvas.id = 'doorprize-confetti-canvas';
                        canvas.style.position = 'fixed';
                        canvas.style.inset = '0';
                        canvas.style.pointerEvents = 'none';
                        canvas.style.zIndex = '9999';
                        document.body.appendChild(canvas);
                    }
                    canvas.width = window.innerWidth;
                    canvas.height = window.innerHeight;
                    const ctx = canvas.getContext('2d');
                    const colors = ['#fbbf24', '#f87171', '#ffffff', '#34d399', '#60a5fa'];
                    const particles = Array.from({ length: 220 }, () => ({
                        x: Math.random() * canvas.width,
                        y: -20 - Math.random() * canvas.height * 0.4,
                        r: 4 + Math.random() * 5,
                        vy: 2 + Math.random() * 3,
                        vx: -2 + Math.random() * 4,
                        rot: Math.random() * 360,
                        vr: -6 + Math.random() * 12,
                        color: colors[Math.floor(Math.random() * colors.length)],
                        shape: Math.random() > 0.5 ? 'rect' : 'circle',
                    }));
                    const start = performance.now();
                    const duration = 3800;

                    const frame = (now) => {
                        const elapsed = now - start;
                        ctx.clearRect(0, 0, canvas.width, canvas.height);
                        particles.forEach((p) => {
                            p.x += p.vx;
                            p.y += p.vy;
                            p.vy += 0.03;
                            p.rot += p.vr;
                            ctx.save();
                            ctx.translate(p.x, p.y);
                            ctx.rotate(p.rot * Math.PI / 180);
                            ctx.fillStyle = p.color;
                            if (p.shape === 'rect') {
                                ctx.fillRect(-p.r / 2, -p.r / 2, p.r, p.r * 1.6);
                            } else {
                                ctx.beginPath();
                                ctx.arc(0, 0, p.r / 2, 0, Math.PI * 2);
                                ctx.fill();
                            }
                            ctx.restore();
                        });
                        if (elapsed < duration) {
                            requestAnimationFrame(frame);
                        } else {
                            ctx.clearRect(0, 0, canvas.width, canvas.height);
                        }
                    };
                    requestAnimationFrame(frame);
                },
            };
        }
    </script>
</div>
