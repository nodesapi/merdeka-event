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

<div class="relative flex min-h-screen flex-col overflow-hidden bg-gradient-to-b from-red-950 via-red-800 to-red-950" wire:poll.700ms="poll" x-data="doorprizeDisplay()" x-init="init()">
    {{-- Dekorasi latar: bintik cahaya + sorot lembut, murni CSS --}}
    <div class="pointer-events-none absolute inset-0" style="background-image: radial-gradient(circle, rgba(255,255,255,.10) 1px, transparent 1px); background-size: 26px 26px;"></div>
    <div class="pointer-events-none absolute -left-32 -top-32 h-96 w-96 rounded-full bg-amber-400/20 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-32 -right-32 h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>
    <div class="pointer-events-none absolute left-1/2 top-1/3 h-[36rem] w-[36rem] -translate-x-1/2 -translate-y-1/2 rounded-full bg-red-400/10 blur-3xl"></div>

    {{-- Watermark angka "81" raksasa di belakang panggung --}}
    <div class="pointer-events-none absolute inset-0 flex select-none items-center justify-center overflow-hidden">
        <span class="font-black leading-none text-white/[0.05]" style="font-size: min(62vw, 620px);">81</span>
    </div>

    {{-- Kembang api ambient, murni CSS --}}
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <span class="firework-spark" style="top:14%; left:12%; animation-delay: 0s;"></span>
        <span class="firework-spark" style="top:22%; left:82%; animation-delay: 1.6s;"></span>
        <span class="firework-spark" style="top:72%; left:8%; animation-delay: 3s;"></span>
        <span class="firework-spark" style="top:66%; left:88%; animation-delay: 2.2s;"></span>
        <span class="firework-spark" style="top:8%; left:50%; animation-delay: 0.9s;"></span>
    </div>

    {{-- Untaian bendera merah-putih (bunting) --}}
    <div class="pointer-events-none absolute inset-x-0 top-0 z-10 flex justify-center">
        <div class="relative flex flex-nowrap">
            <div class="absolute inset-x-0 top-0 border-t-2 border-dashed border-white/30"></div>
            @for ($i = 0; $i < 46; $i++)
                <span class="bunting-flag {{ $i % 2 === 0 ? 'bunting-flag--red' : 'bunting-flag--white' }}" style="animation-delay: {{ $i * 0.05 }}s"></span>
            @endfor
        </div>
    </div>

    <button
        @click="toggleSound"
        class="fixed bottom-4 left-4 z-20 flex items-center gap-2 rounded-full border border-white/20 bg-black/30 px-3 py-2 text-sm text-white/80 hover:bg-black/50"
    >
        <x-icon name="speaker" x-show="soundEnabled" class="h-4 w-4" />
        <x-icon name="speaker-off" x-show="!soundEnabled" class="h-4 w-4" />
        <span x-text="soundEnabled ? 'Suara Aktif' : 'Aktifkan Suara'"></span>
    </button>
    <button
        @click="toggleFullscreen"
        class="fixed bottom-4 right-4 z-20 flex items-center gap-2 rounded-full border border-white/20 bg-black/30 px-3 py-2 text-sm text-white/80 hover:bg-black/50"
    >
        <x-icon name="expand" class="h-4 w-4" />
        <span x-text="isFullscreen ? 'Keluar Layar Penuh' : 'Layar Penuh'"></span>
    </button>

    <div class="relative flex flex-1 flex-col items-center justify-center px-6 py-12 text-center">
        @if ($siteLogoUrl)
            <img src="{{ $siteLogoUrl }}" alt="Logo" class="mb-4 h-16 w-auto object-contain drop-shadow-lg" style="filter: brightness(0) invert(1);">
        @endif

        <div class="ribbon-banner">
            <p class="text-sm font-semibold uppercase tracking-[0.3em] text-white">{{ $eventName ?: 'Belum ada acara aktif' }}</p>
        </div>

        <h1 class="mt-5 flex items-center justify-center gap-3 text-3xl font-black tracking-wide text-white drop-shadow-lg sm:text-5xl">
            <x-icon name="sparkles" class="h-8 w-8 shrink-0 text-amber-300 sm:h-10 sm:w-10" />
            UNDIAN DOORPRIZE
            <x-icon name="sparkles" class="h-8 w-8 shrink-0 text-amber-300 sm:h-10 sm:w-10" />
        </h1>
        <p class="mt-2 flex items-center justify-center gap-2 text-xs font-bold uppercase tracking-[0.45em] text-amber-200/90">
            <span class="mini-flag"></span> Dirgahayu Republik Indonesia <span class="mini-flag"></span>
        </p>

        <div
            class="medal-card relative mt-10 w-full max-w-2xl rounded-3xl border-4 p-8 shadow-2xl backdrop-blur-sm transition-colors duration-500 sm:p-14"
            :class="phase === 'revealed' ? 'border-amber-300 bg-gradient-to-b from-amber-400/25 via-amber-500/10 to-transparent shadow-[0_0_80px_-10px_rgba(251,191,36,.55)]' : 'border-white/40 bg-white/10'"
            x-bind:style="phase === 'spinning' ? 'animation: doorprize-pulse 1s ease-in-out infinite' : ''"
        >
            <span class="mini-flag pointer-events-none absolute -left-3 -top-3 shadow-lg" style="transform: rotate(-14deg);"></span>
            <span class="mini-flag pointer-events-none absolute -right-3 -top-3 shadow-lg" style="transform: rotate(14deg);"></span>

            <p x-show="phase === 'idle'" class="flex items-center justify-center gap-2 text-base font-semibold uppercase tracking-widest text-red-100">
                <x-icon name="gift" class="h-5 w-5" /> Menunggu Diundi
            </p>
            <p x-show="phase === 'spinning'" class="flex items-center justify-center gap-2 text-base font-semibold uppercase tracking-widest text-amber-200">
                <x-icon name="sparkles" class="h-5 w-5" /> Sedang Mengundi...
            </p>
            <p x-show="phase === 'suspense'" class="animate-pulse text-base font-semibold uppercase tracking-widest text-amber-200">Menentukan pemenang...</p>
            <p x-show="phase === 'revealed'" class="flex items-center justify-center gap-2 text-base font-semibold uppercase tracking-widest text-amber-200">
                <img src="{{ asset('trophy.gif') }}" alt="" class="h-6 w-6" style="image-rendering: pixelated;"> Pemenang
            </p>

            <div class="mt-5 font-mono text-6xl font-black tabular-nums text-white sm:text-8xl" x-text="displayNumber"></div>
            <div class="mt-3 min-h-[2.25rem] text-2xl font-bold text-amber-200 sm:text-4xl" x-text="displayName"></div>
            <div class="mt-1 text-sm text-red-100" x-text="displayBlock"></div>
        </div>

        <div class="mt-6 flex min-h-[1.5rem] items-center justify-center gap-2 text-lg font-semibold text-amber-200" x-show="prizeName">
            <x-icon name="gift" class="h-5 w-5" x-show="prizeName" /> <span x-text="prizeName ? ('Hadiah: ' + prizeName) : ''"></span>
        </div>
        <div class="mt-4 inline-flex items-center gap-2 rounded-full border border-white/20 bg-black/25 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-red-100 backdrop-blur-sm">
            <x-icon name="users" class="h-4 w-4" /> Sisa {{ $poolCount }} kepala keluarga belum menang
        </div>
    </div>

    <style>
        @keyframes doorprize-pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(252, 211, 77, .45); }
            50% { box-shadow: 0 0 0 18px rgba(252, 211, 77, 0); }
        }

        .bunting-flag {
            width: 16px;
            height: 24px;
            margin: 0 1px;
            clip-path: polygon(0 0, 100% 0, 50% 100%);
            transform-origin: top center;
            animation: bunting-sway 3.2s ease-in-out infinite;
            box-shadow: 0 2px 5px rgba(0, 0, 0, .25);
        }
        .bunting-flag--red { background: #dc2626; }
        .bunting-flag--white { background: #fafafa; }
        @keyframes bunting-sway {
            0%, 100% { transform: rotate(-5deg); }
            50% { transform: rotate(5deg); }
        }

        .firework-spark {
            position: absolute;
            width: 4px;
            height: 4px;
            border-radius: 9999px;
            background: radial-gradient(circle, #fff 0%, #fbbf24 45%, transparent 75%);
            animation: firework-burst 4.2s ease-out infinite;
        }
        @keyframes firework-burst {
            0% { transform: scale(0); opacity: 0; box-shadow: 0 0 0 0 rgba(251, 191, 36, .65); }
            14% { transform: scale(1); opacity: 1; }
            42% { box-shadow: 0 0 0 55px rgba(251, 191, 36, 0); opacity: .45; }
            60%, 100% { opacity: 0; transform: scale(1); box-shadow: 0 0 0 55px rgba(251, 191, 36, 0); }
        }

        .ribbon-banner {
            position: relative;
            display: inline-block;
            padding: 8px 30px;
            background: linear-gradient(135deg, #b91c1c, #7f1d1d);
            border-top: 1px solid rgba(255, 255, 255, .25);
            border-bottom: 1px solid rgba(0, 0, 0, .3);
        }
        .ribbon-banner::before, .ribbon-banner::after {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            width: 14px;
            background: inherit;
        }
        .ribbon-banner::before { left: -10px; clip-path: polygon(0 50%, 100% 0, 100% 100%); }
        .ribbon-banner::after { right: -10px; clip-path: polygon(0 0, 100% 50%, 0 100%); }

        .mini-flag {
            display: inline-block;
            width: 22px;
            height: 15px;
            border-radius: 2px;
            background: linear-gradient(to bottom, #dc2626 50%, #fafafa 50%);
            box-shadow: 0 1px 4px rgba(0, 0, 0, .35);
        }

        .medal-card::before {
            content: '';
            position: absolute;
            inset: -10px;
            border-radius: inherit;
            border: 2px dashed rgba(255, 255, 255, .25);
            pointer-events: none;
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
                isFullscreen: false,

                init() {
                    this.prizeName = this.$wire.prizeName || '';
                    this.$watch('$wire.status', (value) => this.onStatusChange(value));
                    this.onStatusChange(this.$wire.status);

                    document.addEventListener('fullscreenchange', () => {
                        this.isFullscreen = !!document.fullscreenElement;
                    });
                },

                toggleFullscreen() {
                    if (!document.fullscreenElement) {
                        document.documentElement.requestFullscreen?.().catch(() => {});
                    } else {
                        document.exitFullscreen?.();
                    }
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
                    const colors = ['#dc2626', '#ffffff', '#fbbf24', '#f87171', '#fde68a'];
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
