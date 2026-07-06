const adminShell = document.querySelector('[data-admin-shell]');

if (adminShell) {
    const sidebar = adminShell.querySelector('[data-admin-sidebar]');
    const overlay = adminShell.querySelector('[data-admin-overlay]');
    const openButton = adminShell.querySelector('[data-admin-open]');
    const closeButton = adminShell.querySelector('[data-admin-close]');

    const openSidebar = () => {
        sidebar?.classList.remove('-translate-x-full');
        sidebar?.classList.add('translate-x-0');
        overlay?.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    };

    const closeSidebar = () => {
        sidebar?.classList.add('-translate-x-full');
        sidebar?.classList.remove('translate-x-0');
        overlay?.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    openButton?.addEventListener('click', openSidebar);
    closeButton?.addEventListener('click', closeSidebar);
    overlay?.addEventListener('click', closeSidebar);

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024) {
            overlay?.classList.add('hidden');
            sidebar?.classList.remove('-translate-x-full');
            sidebar?.classList.add('translate-x-0');
            document.body.classList.remove('overflow-hidden');
            return;
        }

        if (!overlay?.classList.contains('hidden')) {
            return;
        }

        sidebar?.classList.add('-translate-x-full');
        sidebar?.classList.remove('translate-x-0');
    });
}

const formatIdrDigits = (value) => {
    const digits = String(value ?? '')
        .replace(/\D+/g, '')
        .replace(/^0+(?=\d)/, '');

    if (!digits) {
        return '';
    }

    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
};

const countDigits = (value) => String(value ?? '').replace(/\D+/g, '').length;

const caretFromDigitIndex = (formatted, digitIndex) => {
    if (digitIndex <= 0) {
        return 0;
    }

    let seen = 0;

    for (let index = 0; index < formatted.length; index += 1) {
        if (/\d/.test(formatted[index])) {
            seen += 1;

            if (seen === digitIndex) {
                return index + 1;
            }
        }
    }

    return formatted.length;
};

const dispatchNativeControlUpdate = (control) => {
    control.dispatchEvent(new Event('input', { bubbles: true }));
    control.dispatchEvent(new Event('change', { bubbles: true }));
};

const syncHiddenValue = (hiddenInput, digits) => {
    if (!hiddenInput) {
        return;
    }

    hiddenInput.value = digits;
    dispatchNativeControlUpdate(hiddenInput);
};

const closeFloatingControls = (except = null) => {
    document.querySelectorAll('[data-floating-control].is-open').forEach((control) => {
        if (except && control === except) {
            return;
        }

        control.dispatchEvent(new CustomEvent('merdeka:close-control'));
    });
};

const bindRupiahInput = (container) => {
    if (container.dataset.rupiahReady === 'true') {
        return;
    }

    const visibleInput = container.querySelector('[data-rupiah-visible]');
    const hiddenInput = container.querySelector('[data-rupiah-hidden]');

    if (!visibleInput || !hiddenInput) {
        return;
    }

    const applyFormattedValue = (sourceValue) => {
        const digits = String(sourceValue ?? '')
            .replace(/\D+/g, '')
            .replace(/^0+(?=\d)/, '');

        visibleInput.value = formatIdrDigits(digits);
        syncHiddenValue(hiddenInput, digits);
    };

    applyFormattedValue(hiddenInput.value || visibleInput.value);

    visibleInput.addEventListener('input', () => {
        const previousDigits = countDigits(visibleInput.value.slice(0, visibleInput.selectionStart ?? visibleInput.value.length));
        const digits = String(visibleInput.value ?? '')
            .replace(/\D+/g, '')
            .replace(/^0+(?=\d)/, '');
        const formatted = formatIdrDigits(digits);

        visibleInput.value = formatted;
        syncHiddenValue(hiddenInput, digits);

        const nextCaret = caretFromDigitIndex(formatted, previousDigits);
        window.requestAnimationFrame(() => {
            visibleInput.setSelectionRange(nextCaret, nextCaret);
        });
    });

    visibleInput.addEventListener('blur', () => {
        visibleInput.value = formatIdrDigits(hiddenInput.value);
    });

    container.dataset.rupiahReady = 'true';
};

const initRupiahInputs = (root = document) => {
    root.querySelectorAll('[data-rupiah-input]').forEach(bindRupiahInput);
};

const selectChevronIcon = `
    <svg class="merdeka-control-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
        <path d="M5 7.5l5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
`;

const checkIcon = `
    <svg class="merdeka-option-check" viewBox="0 0 20 20" fill="none" aria-hidden="true">
        <path d="M5.5 10.5l3 3 6-7" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
`;

const calendarIcon = `
    <svg class="merdeka-control-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
        <path d="M5.5 2.75v2.5M14.5 2.75v2.5M3.5 7.25h13M6.25 10.25h.01M10 10.25h.01M13.75 10.25h.01M6.25 13.5h.01M10 13.5h.01M13.75 13.5h.01M5.1 4.75h9.8c1.05 0 1.9.85 1.9 1.9v8.25c0 1.05-.85 1.9-1.9 1.9H5.1c-1.05 0-1.9-.85-1.9-1.9V6.65c0-1.05.85-1.9 1.9-1.9z" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
`;

const prevMonthIcon = `
    <svg width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true">
        <path d="M11.75 5.75L7.5 10l4.25 4.25" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
`;

const nextMonthIcon = `
    <svg width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true">
        <path d="M8.25 5.75L12.5 10l-4.25 4.25" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
`;

const bindCustomSelect = (select) => {
    const existingWrapper = select.nextElementSibling;
    const hasBoundWrapper =
        existingWrapper instanceof HTMLElement &&
        existingWrapper.matches('[data-floating-control="select"]');

    if (select.dataset.customSelectReady === 'true' && hasBoundWrapper) {
        return;
    }

    if (select.dataset.customSelectReady === 'true' && !hasBoundWrapper) {
        delete select.dataset.customSelectReady;
        select.classList.remove('merdeka-native-control');
    }

    const wrapper = document.createElement('div');
    wrapper.className = 'merdeka-select';
    wrapper.dataset.floatingControl = 'select';

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'merdeka-control-button';

    const label = select.dataset.customSelectLabel?.trim();
    const placeholder = select.dataset.customSelectPlaceholder?.trim() || 'Pilih salah satu';

    const valueSlot = document.createElement('span');
    valueSlot.className = 'merdeka-control-value';
    valueSlot.innerHTML = `
        ${label ? `<span class="merdeka-control-label">${label}</span>` : ''}
        <span class="merdeka-control-text"></span>
    `;

    const text = valueSlot.querySelector('.merdeka-control-text');
    button.append(valueSlot);
    button.insertAdjacentHTML('beforeend', selectChevronIcon);

    const popover = document.createElement('div');
    popover.className = 'merdeka-popover';
    popover.hidden = true;

    const optionsList = document.createElement('div');
    optionsList.className = 'merdeka-options';
    popover.append(optionsList);

    const open = () => {
        closeFloatingControls(wrapper);
        wrapper.classList.add('is-open');
        button.classList.add('is-open');
        popover.hidden = false;
    };

    const close = () => {
        wrapper.classList.remove('is-open');
        button.classList.remove('is-open');
        popover.hidden = true;
    };

    const syncFromSelect = () => {
        const selectedOption = select.options[select.selectedIndex] ?? null;
        const displayText = selectedOption?.text?.trim() || placeholder;

        text.textContent = displayText;
        text.classList.toggle('is-placeholder', !selectedOption?.value);

        optionsList.querySelectorAll('[data-option-value]').forEach((optionButton) => {
            const isSelected = optionButton.dataset.optionValue === (select.value ?? '');
            optionButton.classList.toggle('is-selected', isSelected);
            optionButton.setAttribute('aria-selected', isSelected ? 'true' : 'false');
        });
    };

    Array.from(select.options).forEach((option) => {
        const optionButton = document.createElement('button');
        optionButton.type = 'button';
        optionButton.className = 'merdeka-option';
        optionButton.dataset.optionValue = option.value;
        optionButton.innerHTML = `
            <span class="merdeka-option-meta">
                <span class="merdeka-option-title">${option.text}</span>
            </span>
            ${checkIcon}
        `;

        optionButton.addEventListener('click', () => {
            if (select.value !== option.value) {
                select.value = option.value;
                dispatchNativeControlUpdate(select);
            }

            syncFromSelect();
            close();
        });

        optionsList.append(optionButton);
    });

    button.addEventListener('click', () => {
        if (wrapper.classList.contains('is-open')) {
            close();
            return;
        }

        open();
    });

    select.addEventListener('change', syncFromSelect);
    select.addEventListener('merdeka:sync-select', syncFromSelect);
    wrapper.addEventListener('merdeka:close-control', close);

    select.classList.add('merdeka-native-control');
    select.after(wrapper);
    wrapper.append(button, popover);

    syncFromSelect();
    select.dataset.customSelectReady = 'true';
};

const toLocalDateTimeValue = (date) => {
    const pad = (value) => String(value).padStart(2, '0');

    return [
        date.getFullYear(),
        pad(date.getMonth() + 1),
        pad(date.getDate()),
    ].join('-') + 'T' + [pad(date.getHours()), pad(date.getMinutes())].join(':');
};

const parseLocalDateTime = (value) => {
    if (!value || typeof value !== 'string') {
        return null;
    }

    const [datePart, timePart = '00:00'] = value.split('T');
    const [year, month, day] = datePart.split('-').map(Number);
    const [hours, minutes] = timePart.split(':').map(Number);

    if (!year || !month || !day) {
        return null;
    }

    return new Date(year, month - 1, day, hours || 0, minutes || 0, 0, 0);
};

const formatDateTimeDisplay = (date, emptyText) => {
    if (!date) {
        return {
            title: emptyText,
            subtitle: 'Belum dipilih',
            empty: true,
        };
    }

    const dateText = new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(date);

    const timeText = new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    }).format(date).replace(':', '.');

    return {
        title: dateText,
        subtitle: `${timeText} WIB`,
        empty: false,
    };
};

const bindCustomDateTime = (input) => {
    if (input.dataset.customDatetimeReady === 'true') {
        return;
    }

    const wrapper = document.createElement('div');
    wrapper.className = 'merdeka-datetime';
    wrapper.dataset.floatingControl = 'datetime';

    const label = input.dataset.customDatetimeLabel?.trim();
    const placeholder = input.dataset.customDatetimePlaceholder?.trim() || 'Pilih tanggal dan jam';

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'merdeka-control-button';
    button.innerHTML = `
        <span class="merdeka-control-value">
            ${label ? `<span class="merdeka-control-label">${label}</span>` : ''}
            <span class="merdeka-control-text"></span>
        </span>
        ${calendarIcon}
    `;

    const text = button.querySelector('.merdeka-control-text');
    const popover = document.createElement('div');
    popover.className = 'merdeka-popover merdeka-datetime-panel';
    popover.hidden = true;

    popover.innerHTML = `
        <div class="merdeka-datetime-header">
            <button type="button" class="merdeka-icon-button" data-prev-month aria-label="Bulan sebelumnya">${prevMonthIcon}</button>
            <div class="merdeka-datetime-month" data-month-label></div>
            <button type="button" class="merdeka-icon-button" data-next-month aria-label="Bulan berikutnya">${nextMonthIcon}</button>
        </div>
        <div class="merdeka-datetime-weekdays" data-weekdays></div>
        <div class="merdeka-datetime-grid" data-days-grid></div>
        <div class="merdeka-datetime-footer">
            <div>
                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Jam</label>
                <select data-time-part="hours" data-custom-select data-custom-select-placeholder="Jam"></select>
            </div>
            <div>
                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Menit</label>
                <select data-time-part="minutes" data-custom-select data-custom-select-placeholder="Menit"></select>
            </div>
        </div>
        <div class="merdeka-datetime-actions">
            <button type="button" class="merdeka-text-button" data-today-button>Hari ini</button>
            <button type="button" class="merdeka-text-button is-primary" data-close-button>Selesai</button>
        </div>
    `;

    const monthLabel = popover.querySelector('[data-month-label]');
    const daysGrid = popover.querySelector('[data-days-grid]');
    const hourSelect = popover.querySelector('[data-time-part="hours"]');
    const minuteSelect = popover.querySelector('[data-time-part="minutes"]');
    const weekdays = popover.querySelector('[data-weekdays]');

    ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'].forEach((name) => {
        const item = document.createElement('div');
        item.className = 'merdeka-datetime-weekday';
        item.textContent = name;
        weekdays.append(item);
    });

    for (let hour = 0; hour < 24; hour += 1) {
        hourSelect.add(new Option(String(hour).padStart(2, '0'), String(hour).padStart(2, '0')));
    }

    for (let minute = 0; minute < 60; minute += 1) {
        minuteSelect.add(new Option(String(minute).padStart(2, '0'), String(minute).padStart(2, '0')));
    }

    let selectedDate = parseLocalDateTime(input.value);
    let viewMonth = selectedDate ? new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1) : new Date(new Date().getFullYear(), new Date().getMonth(), 1);

    const positionPopover = () => {
        popover.style.left = '0';
        popover.style.right = 'auto';

        const rect = popover.getBoundingClientRect();
        const viewportWidth = window.innerWidth;

        if (rect.right > viewportWidth - 16) {
            popover.style.left = 'auto';
            popover.style.right = '0';
        }

        if (rect.left < 16) {
            popover.style.left = '0';
            popover.style.right = 'auto';
        }
    };

    const setInputValue = (date) => {
        selectedDate = date;
        input.value = date ? toLocalDateTimeValue(date) : '';
        dispatchNativeControlUpdate(input);
        updateButtonText();
        renderCalendar();
    };

    const updateButtonText = () => {
        const display = formatDateTimeDisplay(selectedDate, placeholder);
        text.textContent = display.title;
        text.classList.toggle('is-placeholder', display.empty);

        let subtitle = button.querySelector('[data-control-subtitle]');
        if (!subtitle) {
            subtitle = document.createElement('span');
            subtitle.dataset.controlSubtitle = 'true';
            subtitle.className = 'merdeka-control-label';
            button.querySelector('.merdeka-control-value')?.append(subtitle);
        }

        subtitle.textContent = display.subtitle;
    };

    const syncTimeSelects = () => {
        const hourValue = String(selectedDate?.getHours?.() ?? 0).padStart(2, '0');
        const minuteValue = String(selectedDate?.getMinutes?.() ?? 0).padStart(2, '0');

        hourSelect.value = hourValue;
        minuteSelect.value = minuteValue;
        hourSelect.dispatchEvent(new CustomEvent('merdeka:sync-select'));
        minuteSelect.dispatchEvent(new CustomEvent('merdeka:sync-select'));
    };

    const updateSelectedTime = () => {
        const baseDate = selectedDate ?? new Date();
        const nextDate = new Date(
            baseDate.getFullYear(),
            baseDate.getMonth(),
            baseDate.getDate(),
            Number(hourSelect.value || '0'),
            Number(minuteSelect.value || '0'),
            0,
            0
        );

        setInputValue(nextDate);
    };

    const renderCalendar = () => {
        const today = new Date();
        const monthText = new Intl.DateTimeFormat('id-ID', {
            month: 'long',
            year: 'numeric',
        }).format(viewMonth);

        monthLabel.textContent = monthText.charAt(0).toUpperCase() + monthText.slice(1);
        daysGrid.innerHTML = '';

        const firstDayOfMonth = new Date(viewMonth.getFullYear(), viewMonth.getMonth(), 1);
        const startOffset = (firstDayOfMonth.getDay() + 6) % 7;
        const gridStart = new Date(viewMonth.getFullYear(), viewMonth.getMonth(), 1 - startOffset);

        for (let index = 0; index < 42; index += 1) {
            const current = new Date(gridStart.getFullYear(), gridStart.getMonth(), gridStart.getDate() + index);
            const dayButton = document.createElement('button');
            dayButton.type = 'button';
            dayButton.className = 'merdeka-day';
            dayButton.textContent = String(current.getDate());

            const isMuted = current.getMonth() !== viewMonth.getMonth();
            const isToday = current.toDateString() === today.toDateString();
            const isSelected = selectedDate && current.toDateString() === selectedDate.toDateString();

            dayButton.classList.toggle('is-muted', isMuted);
            dayButton.classList.toggle('is-today', isToday);
            dayButton.classList.toggle('is-selected', Boolean(isSelected));

            dayButton.addEventListener('click', () => {
                const hour = Number(hourSelect.value || '0');
                const minute = Number(minuteSelect.value || '0');
                const nextDate = new Date(current.getFullYear(), current.getMonth(), current.getDate(), hour, minute, 0, 0);
                viewMonth = new Date(current.getFullYear(), current.getMonth(), 1);
                setInputValue(nextDate);
            });

            daysGrid.append(dayButton);
        }
    };

    const open = () => {
        closeFloatingControls(wrapper);
        wrapper.classList.add('is-open');
        button.classList.add('is-open');
        popover.hidden = false;
        positionPopover();
    };

    const close = () => {
        wrapper.classList.remove('is-open');
        button.classList.remove('is-open');
        popover.hidden = true;
    };

    button.addEventListener('click', () => {
        if (wrapper.classList.contains('is-open')) {
            close();
            return;
        }

        renderCalendar();
        syncTimeSelects();
        open();
    });

    popover.querySelector('[data-prev-month]')?.addEventListener('click', () => {
        viewMonth = new Date(viewMonth.getFullYear(), viewMonth.getMonth() - 1, 1);
        renderCalendar();
    });

    popover.querySelector('[data-next-month]')?.addEventListener('click', () => {
        viewMonth = new Date(viewMonth.getFullYear(), viewMonth.getMonth() + 1, 1);
        renderCalendar();
    });

    popover.querySelector('[data-today-button]')?.addEventListener('click', () => {
        const now = new Date();
        const nextDate = new Date(
            now.getFullYear(),
            now.getMonth(),
            now.getDate(),
            Number(hourSelect.value || now.getHours()),
            Number(minuteSelect.value || now.getMinutes()),
            0,
            0
        );
        viewMonth = new Date(nextDate.getFullYear(), nextDate.getMonth(), 1);
        setInputValue(nextDate);
    });

    popover.querySelector('[data-close-button]')?.addEventListener('click', close);
    hourSelect.addEventListener('change', updateSelectedTime);
    minuteSelect.addEventListener('change', updateSelectedTime);
    input.addEventListener('change', () => {
        selectedDate = parseLocalDateTime(input.value);
        if (selectedDate) {
            viewMonth = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
        }
        updateButtonText();
        syncTimeSelects();
        renderCalendar();
    });

    wrapper.addEventListener('merdeka:close-control', close);
    window.addEventListener('resize', () => {
        if (!popover.hidden) {
            positionPopover();
        }
    });

    input.classList.add('merdeka-native-control');
    input.after(wrapper);
    wrapper.append(button, popover);

    initCustomSelects(popover);
    updateButtonText();
    syncTimeSelects();
    renderCalendar();

    input.dataset.customDatetimeReady = 'true';
};

function initCustomSelects(root = document) {
    root.querySelectorAll('select[data-custom-select]').forEach(bindCustomSelect);
}

function initCustomDateTimes(root = document) {
    root.querySelectorAll('input[data-custom-datetime]').forEach(bindCustomDateTime);
}

const initInteractiveControls = (root = document) => {
    initRupiahInputs(root);
    initCustomSelects(root);
    initCustomDateTimes(root);
};

initInteractiveControls();

document.addEventListener('livewire:navigated', () => {
    initInteractiveControls();
});

document.addEventListener('click', (event) => {
    const target = event.target;

    if (!(target instanceof Element)) {
        return;
    }

    if (!target.closest('[data-floating-control]')) {
        closeFloatingControls();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeFloatingControls();
    }
});

const interactiveObserver = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
            if (!(node instanceof HTMLElement)) {
                return;
            }

            if (node.matches('[data-rupiah-input]')) {
                bindRupiahInput(node);
            }

            if (node.matches('select[data-custom-select]')) {
                bindCustomSelect(node);
            }

            if (node.matches('input[data-custom-datetime]')) {
                bindCustomDateTime(node);
            }

            initInteractiveControls(node);
        });
    });
});

interactiveObserver.observe(document.body, {
    childList: true,
    subtree: true,
});
