<x-layouts.public title="Galeri" :eventName="$event?->name">
    <div class="mx-auto max-w-6xl text-left">
        <div class="flex items-center gap-3">
            <span class="hidden h-8 w-1.5 rounded-full bg-red-600 md:block"></span>
            <div class="text-center md:text-left">
                <span class="merdeka-badge">Galeri</span>
                <h1 class="mt-2 text-2xl font-black tracking-tight text-stone-900">Keseruan HUT RI 2024</h1>
                <p class="mt-1 text-sm text-stone-500">Dokumentasi foto kegiatan warga selama rangkaian acara.</p>
            </div>
        </div>

        @if ($photos->isEmpty())
            <div class="merdeka-card mt-6 p-8 text-center">
                <x-icon name="image" class="mx-auto h-10 w-10 text-stone-300" />
                <p class="mt-3 text-sm font-semibold text-stone-500">Belum ada foto di galeri.</p>
            </div>
        @else
            <div class="mt-6 columns-2 gap-3 sm:columns-3 lg:columns-4" data-gallery>
                @foreach ($photos as $photo)
                    <button type="button" class="mb-3 block w-full break-inside-avoid overflow-hidden rounded-xl bg-stone-100 shadow-sm" data-gallery-open data-src="{{ $photo['full'] }}">
                        <img src="{{ $photo['thumb'] }}" loading="lazy" decoding="async" alt="Foto keseruan HUT RI 2024" class="block h-auto w-full rounded-xl object-cover opacity-0 transition duration-300 hover:opacity-90">
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    <div class="fixed inset-0 z-[80] hidden items-center justify-center bg-black p-3 opacity-0 transition-opacity duration-200 sm:p-6" data-gallery-lightbox>
        <p class="absolute left-1/2 top-4 -translate-x-1/2 text-xs font-semibold text-white/70" data-gallery-counter></p>

        <button type="button" class="absolute right-3 top-3 flex h-10 w-10 items-center justify-center rounded-full bg-white/15 text-white transition hover:bg-white/25 sm:right-5 sm:top-5" data-gallery-close aria-label="Tutup">
            <x-icon name="x-mark" class="h-5 w-5" />
        </button>

        <button type="button" class="absolute left-2 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/15 text-white transition hover:bg-white/25 sm:left-4 sm:h-11 sm:w-11" data-gallery-prev aria-label="Sebelumnya">
            <x-icon name="chevron-right" class="h-5 w-5 rotate-180" />
        </button>
        <button type="button" class="absolute right-2 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/15 text-white transition hover:bg-white/25 sm:right-4 sm:h-11 sm:w-11" data-gallery-next aria-label="Berikutnya">
            <x-icon name="chevron-right" class="h-5 w-5" />
        </button>

        <img src="" alt="" class="max-h-[85vh] max-w-[88vw] scale-95 rounded-lg object-contain shadow-2xl transition-transform duration-200" data-gallery-image>
    </div>

    <script>
        (function () {
            document.querySelectorAll('[data-gallery] img').forEach(function (img) {
                if (img.complete) {
                    img.classList.remove('opacity-0');
                } else {
                    img.addEventListener('load', function () { img.classList.remove('opacity-0'); }, { once: true });
                }
            });
        })();

        (function () {
            const lightbox = document.querySelector('[data-gallery-lightbox]');
            if (!lightbox) return;
            const image = lightbox.querySelector('[data-gallery-image]');
            const counter = lightbox.querySelector('[data-gallery-counter]');
            const items = Array.from(document.querySelectorAll('[data-gallery-open]'));
            const sources = items.map(function (el) { return el.dataset.src; });
            let index = 0;

            function render() {
                image.src = sources[index];
                if (counter) counter.textContent = (index + 1) + ' / ' + sources.length;
            }
            function open(i) {
                index = i;
                render();
                lightbox.classList.remove('hidden');
                lightbox.classList.add('flex');
                document.body.style.overflow = 'hidden';
                requestAnimationFrame(function () {
                    lightbox.classList.remove('opacity-0');
                    image.classList.remove('scale-95');
                });
            }
            function close() {
                lightbox.classList.add('opacity-0');
                image.classList.add('scale-95');
                document.body.style.overflow = '';
                setTimeout(function () {
                    lightbox.classList.add('hidden');
                    lightbox.classList.remove('flex');
                    image.src = '';
                }, 200);
            }
            function go(delta) {
                index = (index + delta + sources.length) % sources.length;
                render();
            }

            items.forEach(function (el, i) {
                el.addEventListener('click', function () { open(i); });
            });
            lightbox.addEventListener('click', function (e) { if (e.target === lightbox) close(); });
            lightbox.querySelector('[data-gallery-close]').addEventListener('click', close);
            lightbox.querySelector('[data-gallery-prev]').addEventListener('click', function (e) { e.stopPropagation(); go(-1); });
            lightbox.querySelector('[data-gallery-next]').addEventListener('click', function (e) { e.stopPropagation(); go(1); });

            document.addEventListener('keydown', function (e) {
                if (lightbox.classList.contains('hidden')) return;
                if (e.key === 'Escape') close();
                if (e.key === 'ArrowLeft') go(-1);
                if (e.key === 'ArrowRight') go(1);
            });

            let touchX = null;
            lightbox.addEventListener('touchstart', function (e) { touchX = e.touches[0].clientX; }, { passive: true });
            lightbox.addEventListener('touchend', function (e) {
                if (touchX === null) return;
                const dx = e.changedTouches[0].clientX - touchX;
                if (Math.abs(dx) > 40) go(dx > 0 ? -1 : 1);
                touchX = null;
            }, { passive: true });
        })();
    </script>
</x-layouts.public>
