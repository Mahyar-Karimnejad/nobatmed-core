(function () {
	const { createElement: h, useEffect, useState, useCallback } = window.wp.element;
	const apiFetch = window.wp.apiFetch;
	const cfg = window.nobatmedCoreConfig || {};
	const strings = cfg.strings || {};

	apiFetch.use(apiFetch.createNonceMiddleware(cfg.nonce));

	function request(path, options) {
		return apiFetch({ path: '/nobatmed-core/v1' + path, ...options });
	}

	const ICONS = { dashboard: '◉', modules: '▦', appearance: '◐', plugins: '⬡', booking: '◷', addons: '✦', notices: '◈' };

	const DEV_STATUS = {
		done: { label: 'آماده', className: 'nm-tag--done' },
		progress: { label: 'در حال توسعه', className: 'nm-tag--progress' },
		pending: { label: 'برنامه‌ریزی', className: 'nm-tag--pending' },
	};

	const JALALI_MONTHS = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
	const JALALI_WEEKDAYS = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];

	function gregorianToJalali(gy, gm, gd) {
		const g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
		let jy = gy <= 1600 ? 0 : 979;
		gy -= gy <= 1600 ? 621 : 1600;
		const gy2 = gm > 2 ? gy + 1 : gy;
		let days =
			365 * gy +
			Math.floor((gy2 + 3) / 4) -
			Math.floor((gy2 + 99) / 100) +
			Math.floor((gy2 + 399) / 400) -
			80 +
			gd +
			g_d_m[gm - 1];
		jy += 33 * Math.floor(days / 12053);
		days %= 12053;
		jy += 4 * Math.floor(days / 1461);
		days %= 1461;
		if (days > 365) {
			jy += Math.floor((days - 1) / 365);
			days = (days - 1) % 365;
		}
		const jm = days < 186 ? 1 + Math.floor(days / 31) : 7 + Math.floor((days - 186) / 30);
		const jd = 1 + (days < 186 ? days % 31 : (days - 186) % 30);
		return [jy, jm, jd];
	}

	function jalaliToGregorian(jy, jm, jd) {
		let days = 365 * jy + Math.floor(jy / 33) * 8 + Math.floor(((jy % 33) + 3) / 4) + 78 + jd + (jm < 7 ? (jm - 1) * 31 : (jm - 7) * 30 + 186);
		let gy = 1600 + 400 * Math.floor(days / 146097);
		days %= 146097;
		let leap = true;
		if (days >= 36525) {
			days--;
			gy += 100 * Math.floor(days / 36524);
			days %= 36524;
			if (days >= 365) days++;
			else leap = false;
		}
		gy += 4 * Math.floor(days / 1461);
		days %= 1461;
		if (days >= 366) {
			leap = false;
			days--;
			gy += Math.floor(days / 365);
			days %= 365;
		}
		const gd_m = [0, 31, leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
		let gm = 0;
		for (gm = 0; gm < 13 && days >= gd_m[gm]; gm++) days -= gd_m[gm];
		return [gy, gm, days + 1];
	}

	function isoFromGregorian(gy, gm, gd) {
		return gy + '-' + String(gm).padStart(2, '0') + '-' + String(gd).padStart(2, '0');
	}

	function jalaliMonthLength(jy, jm) {
		if (jm <= 6) return 31;
		if (jm <= 11) return 30;
		const r = (((jy - (jy > 0 ? 474 : 473)) % 2820) + 474 + 2820) % 2820;
		return (r + 38) * 682 % 2816 < 682 ? 30 : 29;
	}

	function jalaliWeekday(jy, jm, jd) {
		const g = jalaliToGregorian(jy, jm, jd);
		return (new Date(g[0], g[1] - 1, g[2]).getDay() + 1) % 7;
	}

	function formatJalaliLabel(iso) {
		if (!iso) return '';
		const p = iso.split('-').map(Number);
		const j = gregorianToJalali(p[0], p[1], p[2]);
		return j[0] + '/' + String(j[1]).padStart(2, '0') + '/' + String(j[2]).padStart(2, '0');
	}

	function JalaliDatePicker({ value, onChange }) {
		const todayG = new Date();
		const todayJ = gregorianToJalali(todayG.getFullYear(), todayG.getMonth() + 1, todayG.getDate());
		const initial = value
			? gregorianToJalali(...value.split('-').map(Number))
			: todayJ;

		const [viewYear, setViewYear] = useState(initial[0]);
		const [viewMonth, setViewMonth] = useState(initial[1]);

		useEffect(() => {
			if (!value) return;
			const j = gregorianToJalali(...value.split('-').map(Number));
			setViewYear(j[0]);
			setViewMonth(j[1]);
		}, [value]);

		const monthLen = jalaliMonthLength(viewYear, viewMonth);
		const firstDay = jalaliWeekday(viewYear, viewMonth, 1);
		const cells = [];
		for (let i = 0; i < firstDay; i++) cells.push(null);
		for (let d = 1; d <= monthLen; d++) cells.push(d);

		const selectedJ = value ? gregorianToJalali(...value.split('-').map(Number)) : null;

		const pickDay = (day) => {
			const g = jalaliToGregorian(viewYear, viewMonth, day);
			onChange(isoFromGregorian(g[0], g[1], g[2]));
		};

		const prevMonth = () => {
			if (viewMonth === 1) {
				setViewYear(viewYear - 1);
				setViewMonth(12);
			} else setViewMonth(viewMonth - 1);
		};

		const nextMonth = () => {
			if (viewMonth === 12) {
				setViewYear(viewYear + 1);
				setViewMonth(1);
			} else setViewMonth(viewMonth + 1);
		};

		return h('div', { className: 'nm-jalali-picker' }, [
			h('div', { className: 'nm-jalali-picker__head', key: 'head' }, [
				h('button', { type: 'button', className: 'nm-btn nm-btn--ghost nm-btn--sm', onClick: prevMonth }, '‹'),
				h('strong', {}, JALALI_MONTHS[viewMonth - 1] + ' ' + viewYear),
				h('button', { type: 'button', className: 'nm-btn nm-btn--ghost nm-btn--sm', onClick: nextMonth }, '›'),
			]),
			h('div', { className: 'nm-jalali-picker__weekdays', key: 'wd' }, JALALI_WEEKDAYS.map((d) => h('span', { key: d }, d))),
			h(
				'div',
				{ className: 'nm-jalali-picker__grid', key: 'grid' },
				cells.map((day, idx) =>
					day
						? h(
								'button',
								{
									key: idx,
									type: 'button',
									className:
										'nm-jalali-day' +
										(selectedJ &&
										selectedJ[0] === viewYear &&
										selectedJ[1] === viewMonth &&
										selectedJ[2] === day
											? ' is-active'
											: '') +
										(todayJ[0] === viewYear && todayJ[1] === viewMonth && todayJ[2] === day ? ' is-today' : ''),
									onClick: () => pickDay(day),
								},
								day
						  )
						: h('span', { key: idx, className: 'nm-jalali-day is-empty' })
				)
			),
			value ? h('p', { className: 'nm-jalali-label', key: 'lbl' }, 'میلادی: ' + value + ' · شمسی: ' + formatJalaliLabel(value)) : null,
		]);
	}

	function AppChrome({ children }) {
		return h('div', { className: 'nm-shell' }, [
			h('div', { className: 'nm-window', key: 'win' }, [
				h('div', { className: 'nm-window__titlebar', key: 'tb' }, [
					h('span', { className: 'nm-window__dot nm-window__dot--close' }),
					h('span', { className: 'nm-window__dot nm-window__dot--min' }),
					h('span', { className: 'nm-window__dot nm-window__dot--max' }),
				]),
				children,
			]),
		]);
	}

	function Toggle({ checked, disabled, onChange, label }) {
		return h(
			'label',
			{ className: 'nm-toggle' + (disabled ? ' is-disabled' : '') },
			h('input', {
				type: 'checkbox',
				checked: checked,
				disabled: disabled,
				onChange: (e) => onChange(e.target.checked),
				'aria-label': label,
			}),
			h('span', { className: 'nm-toggle__track' })
		);
	}

	function Sidebar({ active, onNavigate }) {
		const items = [
			{ id: 'dashboard', label: strings.dashboard || 'داشبورد' },
			{ id: 'modules', label: strings.modules || 'ماژول‌ها' },
			{ id: 'appearance', label: strings.appearance || 'ظاهر قالب', parent: 'modules' },
			{ id: 'plugins', label: strings.plugins || 'پلاگین‌ها' },
			{ id: 'booking', label: strings.booking || 'نوبت‌دهی' },
			{ id: 'notices', label: strings.notices || 'اعلان‌ها' },
			{ id: 'addons', label: strings.addons || 'افزونه‌ها' },
		];

		return h('aside', { className: 'nm-sidebar' }, [
			h('div', { className: 'nm-sidebar__brand', key: 'brand' }, [
				h('span', { className: 'nm-sidebar__logo' }, 'ن'),
				h('div', {}, [h('strong', {}, strings.title || 'نوبت‌مد'), h('small', {}, 'Core Panel')]),
			]),
			h(
				'nav',
				{ className: 'nm-sidebar__nav', key: 'nav' },
				items.map((item) =>
					h(
						'button',
						{
							key: item.id,
							type: 'button',
							className:
								'nm-sidebar__link' +
								(active === item.id ? ' is-active' : '') +
								(item.parent ? ' is-sub' : ''),
							onClick: () => onNavigate(item.id),
						},
						[
							h('span', { className: 'nm-sidebar__icon' }, item.parent ? '↳' : ICONS[item.id] || '•'),
							item.label,
						]
					)
				)
			),
			h('div', { className: 'nm-sidebar__footer', key: 'foot' }, 'v' + (cfg.version || '0.3.0')),
		]);
	}

	function DashboardPage({ data, onNavigate }) {
		const status = data.status || {};
		const stats = data.stats || {};
		const phase = data.phase || {};
		const progress = data.moduleProgress || {};
		const orbit = data.orbit || {};
		const notices = orbit.notices || [];
		const orbitState = orbit.state || {};

		return h('div', { className: 'nm-page' }, [
			!status.licenseEnabled
				? h('div', { className: 'nm-note', key: 'note' }, 'سیستم لایسنس فعلاً غیرفعال است — می‌توانید بدون فعال‌سازی توسعه دهید.')
				: null,
			h('div', { className: 'nm-stats', key: 'stats' }, [
				h('div', { className: 'nm-stat-card' }, [
					h('span', { className: 'nm-stat-card__label' }, 'ماژول فعال'),
					h('strong', { className: 'nm-stat-card__value' }, stats.modulesEnabled + ' / ' + stats.modulesTotal),
				]),
				h('div', { className: 'nm-stat-card' }, [
					h('span', { className: 'nm-stat-card__label' }, 'آماده / در حال توسعه'),
					h('strong', { className: 'nm-stat-card__value' }, (progress.done || 0) + ' / ' + (progress.progress || 0)),
				]),
				h('div', { className: 'nm-stat-card' }, [
					h('span', { className: 'nm-stat-card__label' }, 'برنامه‌ریزی'),
					h('strong', { className: 'nm-stat-card__value' }, progress.pending || 0),
				]),
				h('div', { className: 'nm-stat-card' }, [
					h('span', { className: 'nm-stat-card__label' }, 'پلاگین آماده'),
					h('strong', { className: 'nm-stat-card__value' }, stats.pluginsActive + ' / ' + stats.pluginsTotal),
				]),
				h('div', { className: 'nm-stat-card' }, [
					h('span', { className: 'nm-stat-card__label' }, 'نسخه Core'),
					h('strong', { className: 'nm-stat-card__value' }, status.version),
				]),
			]),
			h('div', { className: 'nm-grid-2', key: 'grid' }, [
				h('section', { className: 'nm-panel' }, [
					h('div', { className: 'nm-panel__head' }, h('h3', {}, 'وضعیت سیستم')),
					h('ul', { className: 'nm-checklist' }, [
						h('li', { className: status.woocommerce ? 'is-ok' : 'is-warn', key: 'w' }, [
							h('span', {}, 'WooCommerce'),
							h('em', {}, status.woocommerce ? 'فعال' : 'غیرفعال'),
						]),
						h('li', { className: status.elementor ? 'is-ok' : 'is-warn', key: 'e' }, [
							h('span', {}, 'Elementor'),
							h('em', {}, status.elementor ? 'فعال' : 'غیرفعال'),
						]),
						h('li', { className: 'is-ok', key: 'c' }, [h('span', {}, 'NobatMed Core'), h('em', {}, 'فعال')]),
						h('li', { className: status.dbReady ? 'is-ok' : 'is-warn', key: 'd' }, [
							h('span', {}, 'DB Schema'),
							h('em', {}, status.dbReady ? 'فعال' : 'نیاز به فعال‌سازی مجدد'),
						]),
					]),
				]),
				h('section', { className: 'nm-panel' }, [
					h('div', { className: 'nm-panel__head' }, h('h3', {}, 'دسترسی سریع')),
					h('div', { className: 'nm-quick-actions' }, [
						h('button', { type: 'button', className: 'nm-btn nm-btn--outline', onClick: () => onNavigate('modules') }, 'مدیریت ماژول‌ها'),
						h('button', { type: 'button', className: 'nm-btn nm-btn--outline', onClick: () => onNavigate('appearance') }, 'ظاهر قالب'),
						h('button', { type: 'button', className: 'nm-btn nm-btn--outline', onClick: () => onNavigate('plugins') }, 'پلاگین‌های پیشنهادی'),
						h('button', { type: 'button', className: 'nm-btn nm-btn--outline', onClick: () => onNavigate('booking') }, 'نوبت‌دهی'),
						h('button', { type: 'button', className: 'nm-btn nm-btn--outline', onClick: () => onNavigate('notices') }, 'اعلان‌های Orbit'),
						h('a', { className: 'nm-btn nm-btn--ghost', href: status.siteUrl, target: '_blank', rel: 'noreferrer' }, 'مشاهده سایت'),
					]),
				]),
			]),
			h('section', { className: 'nm-panel', key: 'orbit' }, [
				h('div', { className: 'nm-panel__head' }, [
					h('h3', {}, 'Orbit Hub'),
					h('span', { className: 'nm-panel__count' }, notices.length + ' اعلان'),
				]),
				h('p', { className: 'nm-panel__meta' }, orbitState.last_sync
					? 'آخرین همگام‌سازی: ' + orbitState.last_sync
					: 'هنوز همگام‌سازی نشده — از تب اعلان‌ها دریافت کنید.'),
				notices.length
					? h('ul', { className: 'nm-notice-list nm-notice-list--compact' }, notices.slice(0, 3).map((n) =>
							h('li', { key: n.id, className: 'nm-notice-item nm-notice-item--' + (n.type || 'info') }, [
								h('strong', {}, n.title),
								h('span', {}, n.message),
							])
					  ))
					: h('p', {}, 'اعلان مارکتینگ از Orbit Hub اینجا نمایش داده می‌شود.'),
			]),
			h('section', { className: 'nm-panel', key: 'roadmap' }, [
				h('div', { className: 'nm-panel__head' }, h('h3', {}, phase.label || 'مراحل توسعه')),
				h('ol', { className: 'nm-roadmap' }, [
					h('li', { className: 'is-done' }, 'ساختار قالب + Core ماژولار'),
					h('li', { className: 'is-done' }, 'CPT پزشک/کلینیک + DB نوبت'),
					h('li', { className: 'is-current' }, 'پنل React + UI نوبت‌دهی'),
					h('li', {}, 'ویجت Elementor + OTP + درگاه'),
					h('li', {}, 'دمو Import + ثبت‌نام پزشک'),
				]),
			]),
		]);
	}

	function ModulesPage({ modules, onUpdate, saving, setSaving }) {
		const grouped = modules.reduce((acc, mod) => {
			const key = mod.groupLabel || mod.group;
			if (!acc[key]) acc[key] = [];
			acc[key].push(mod);
			return acc;
		}, {});

		const progress = modules.reduce(
			(acc, mod) => {
				if (!mod.implemented) acc.pending += 1;
				else if (mod.devStatus === 'done') acc.done += 1;
				else acc.progress += 1;
				return acc;
			},
			{ done: 0, progress: 0, pending: 0 }
		);

		const [notice, setNotice] = useState(null);

		const handleToggle = (mod, enabled) => {
			if (mod.locked || !mod.canToggle || !mod.implemented) return;
			setSaving(mod.id);
			setNotice(null);
			request('/modules', { method: 'POST', data: { id: mod.id, enabled } })
				.then((res) => {
					if (res.success === false) {
						setNotice({ type: 'error', text: res.message || 'خطا در تغییر وضعیت.' });
						return;
					}
					if (res.modules) onUpdate(res.modules);
				})
				.finally(() => setSaving(null));
		};

		const devTag = (mod) => {
			const key = mod.implemented ? mod.devStatus || 'progress' : 'pending';
			const meta = DEV_STATUS[key] || DEV_STATUS.pending;
			return h('span', { className: 'nm-tag ' + meta.className }, meta.label);
		};

		return h('div', { className: 'nm-page' }, [
			h('header', { className: 'nm-page-header', key: 'head' }, [
				h('h2', {}, 'ماژول‌ها'),
				h('p', {}, 'فقط ماژول‌های آماده قابل فعال‌سازی هستند — بقیه تا پایان توسعه قفل می‌مانند.'),
			]),
			notice ? h('div', { className: 'nm-notice nm-notice--' + notice.type, key: 'n' }, notice.text) : null,
			h('section', { className: 'nm-panel nm-panel--progress', key: 'prog' }, [
				h('div', { className: 'nm-progress-bar' }, [
					h('span', { className: 'nm-progress-bar__seg nm-progress-bar__seg--done', style: { flex: progress.done || 0.001 } }),
					h('span', { className: 'nm-progress-bar__seg nm-progress-bar__seg--progress', style: { flex: progress.progress || 0.001 } }),
					h('span', { className: 'nm-progress-bar__seg nm-progress-bar__seg--pending', style: { flex: progress.pending || 0.001 } }),
				]),
				h('div', { className: 'nm-progress-legend' }, [
					h('span', {}, 'آماده: ' + progress.done),
					h('span', {}, 'در حال توسعه: ' + progress.progress),
					h('span', {}, 'برنامه‌ریزی: ' + progress.pending),
				]),
			]),
			...Object.entries(grouped).map(([group, items]) =>
				h('section', { className: 'nm-panel', key: group }, [
					h('div', { className: 'nm-panel__head' }, [
						h('h3', {}, group),
						h('span', { className: 'nm-panel__count' }, items.length + ' ماژول'),
					]),
					h(
						'div',
						{ className: 'nm-module-grid' },
						items.map((mod) =>
							h(
								'article',
								{
									key: mod.id,
									className:
										'nm-module-card' +
										(mod.enabled ? ' is-on' : '') +
										(!mod.implemented ? ' is-soon' : '') +
										(mod.devStatus === 'progress' ? ' is-dev' : ''),
								},
								[
									h('div', { className: 'nm-module-card__top' }, [
										h('span', { className: 'dashicons dashicons-' + mod.icon, 'aria-hidden': 'true' }),
										h('div', { className: 'nm-module-card__meta' }, [
											devTag(mod),
											mod.locked ? h('span', { className: 'nm-tag nm-tag--lock' }, 'ضروری') : null,
											mod.type === 'addon' ? h('span', { className: 'nm-tag nm-tag--addon' }, 'Add-on') : null,
											!mod.available && mod.implemented
												? h('span', { className: 'nm-tag nm-tag--warn' }, 'نیاز به پلاگین')
												: null,
										]),
										mod.implemented
											? h(Toggle, {
													checked: mod.enabled,
													disabled: mod.locked || !mod.canToggle || !mod.available || saving === mod.id,
													onChange: (v) => handleToggle(mod, v),
													label: mod.name,
											  })
											: h('span', { className: 'nm-module-card__locked' }, '🔒'),
									]),
									h('h4', {}, mod.name),
									h('p', {}, mod.description),
									h('footer', { className: 'nm-module-card__foot' }, 'فاز ' + mod.phase + (mod.orbitProduct ? ' · ' + mod.orbitProduct : '')),
								]
							)
						)
					),
				])
			),
		]);
	}

	function NoticesPage({ orbit, onOrbitUpdate }) {
		const [loading, setLoading] = useState(false);
		const [syncing, setSyncing] = useState(false);
		const [notice, setNotice] = useState(null);
		const [local, setLocal] = useState(orbit || { notices: [], state: {} });

		useEffect(() => {
			setLocal(orbit || { notices: [], state: {} });
		}, [orbit]);

		const refresh = () => {
			setLoading(true);
			request('/orbit/notices')
				.then((res) => {
					if (res.data) {
						setLocal(res.data);
						onOrbitUpdate(res.data);
					}
				})
				.finally(() => setLoading(false));
		};

		const sync = () => {
			setSyncing(true);
			setNotice(null);
			request('/orbit/sync', { method: 'POST' })
				.then((res) => {
					setNotice({
						type: res.success ? 'success' : 'error',
						text: res.message || (res.success ? 'همگام‌سازی انجام شد.' : 'خطا در همگام‌سازی.'),
					});
					if (res.data) {
						setLocal(res.data);
						onOrbitUpdate(res.data);
					}
				})
				.catch(() => setNotice({ type: 'error', text: 'ارتباط با Orbit Hub برقرار نشد.' }))
				.finally(() => setSyncing(false));
		};

		const notices = local.notices || [];
		const state = local.state || {};

		return h('div', { className: 'nm-page' }, [
			h('header', { className: 'nm-page-header nm-page-header--row', key: 'head' }, [
				h('div', {}, [
					h('h2', {}, 'اعلان‌های Orbit'),
					h('p', {}, 'دریافت نوتیس مارکتینگ و ارسال وضعیت ماژول/add-on به Orbit Hub.'),
				]),
				h('div', { className: 'nm-quick-actions' }, [
					h('button', { type: 'button', className: 'nm-btn nm-btn--outline', disabled: loading, onClick: refresh }, loading ? '...' : 'بروزرسانی'),
					h('button', { type: 'button', className: 'nm-btn nm-btn--primary', disabled: syncing, onClick: sync }, syncing ? 'در حال همگام‌سازی...' : 'همگام‌سازی با Orbit'),
				]),
			]),
			notice ? h('div', { className: 'nm-notice nm-notice--' + notice.type, key: 'n' }, notice.text) : null,
			h('section', { className: 'nm-panel', key: 'state' }, [
				h('div', { className: 'nm-panel__head' }, h('h3', {}, 'وضعیت اتصال')),
				h('ul', { className: 'nm-checklist' }, [
					h('li', { className: 'is-ok', key: 'api' }, [h('span', {}, 'API Core'), h('em', {}, '/orbit/notices · /orbit/sync')]),
					h('li', { className: state.last_sync ? 'is-ok' : 'is-warn', key: 'sync' }, [
						h('span', {}, 'آخرین همگام‌سازی'),
						h('em', {}, state.last_sync || '—'),
					]),
					state.last_error
						? h('li', { className: 'is-warn', key: 'err' }, [h('span', {}, 'خطا'), h('em', {}, state.last_error)])
						: null,
				]),
			]),
			h('section', { className: 'nm-panel', key: 'list' }, [
				h('div', { className: 'nm-panel__head' }, [
					h('h3', {}, 'اعلان‌ها'),
					h('span', { className: 'nm-panel__count' }, notices.length + ' مورد'),
				]),
				notices.length
					? h(
							'ul',
							{ className: 'nm-notice-list' },
							notices.map((n) =>
								h('li', { key: n.id, className: 'nm-notice-item nm-notice-item--' + (n.type || 'info') }, [
									h('strong', {}, n.title),
									h('p', {}, n.message),
									n.product ? h('small', {}, 'محصول: ' + n.product) : null,
								])
							)
					  )
					: h('p', {}, 'اعلانی دریافت نشده — پس از راه‌اندازی Orbit Hub، همگام‌سازی کنید.'),
			]),
		]);
	}

	function pickFontFile(onSelect) {
		if (!window.wp || !window.wp.media) {
			window.alert('کتابخانه رسانه وردپرس در دسترس نیست.');
			return;
		}
		const frame = window.wp.media({
			title: 'انتخاب فایل فونت',
			button: { text: 'انتخاب' },
			multiple: false,
		});
		frame.on('select', () => {
			const att = frame.state().get('selection').first().toJSON();
			onSelect({ id: att.id, url: att.url, filename: att.filename || att.title || 'font' });
		});
		frame.open();
	}

	function AppearancePage({ appearance, onUpdate }) {
		const [settings, setSettings] = useState((appearance && appearance.settings) || {});
		const [presets, setPresets] = useState((appearance && appearance.presets) || {});
		const [fontPresets, setFontPresets] = useState((appearance && appearance.fontPresets) || {});
		const [fontFiles, setFontFiles] = useState((appearance && appearance.fontFiles) || {});
		const [themeActive, setThemeActive] = useState(appearance ? appearance.themeActive : true);
		const [saving, setSaving] = useState(false);
		const [notice, setNotice] = useState(null);

		useEffect(() => {
			if (!appearance) {
				request('/appearance').then((res) => {
					if (res.data) {
						setSettings(res.data.settings || {});
						setPresets(res.data.presets || {});
						setFontPresets(res.data.fontPresets || {});
						setFontFiles(res.data.fontFiles || {});
						setThemeActive(res.data.themeActive);
						onUpdate(res.data);
					}
				});
				return;
			}
			setSettings(appearance.settings || {});
			setPresets(appearance.presets || {});
			setFontPresets(appearance.fontPresets || {});
			setFontFiles(appearance.fontFiles || {});
			setThemeActive(appearance.themeActive);
		}, [appearance]);

		const fields = [
			{ key: 'brand', label: 'رنگ اصلی' },
			{ key: 'brand_2', label: 'رنگ ثانویه' },
			{ key: 'accent', label: 'رنگ accent' },
			{ key: 'text', label: 'متن' },
			{ key: 'muted', label: 'متن فرعی' },
			{ key: 'bg', label: 'پس‌زمینه' },
			{ key: 'surface', label: 'سطح کارت' },
			{ key: 'border', label: 'حاشیه' },
		];

		const previewStyle = {
			'--cl-brand': settings.brand,
			'--cl-brand-2': settings.brand_2,
			'--cl-text': settings.text,
			'--cl-muted': settings.muted,
			'--cl-bg': settings.bg,
			'--cl-surface': settings.surface,
			'--cl-border': settings.border,
			'--cl-radius': (settings.radius || 14) + 'px',
			'--cl-gradient': 'linear-gradient(140deg, ' + settings.brand + ' 0%, ' + settings.brand_2 + ' 55%, ' + settings.accent + ' 100%)',
			'--cl-font-family': settings.font_mode === 'upload' || settings.font_mode === 'external'
				? (settings.font_family_name || 'CustomFont') + ', Tahoma, sans-serif'
				: settings.font_mode === 'preset' && fontPresets[settings.font_preset]
				? fontPresets[settings.font_preset].family
				: 'Vazir, Tahoma, sans-serif',
			fontFamily: settings.font_mode === 'upload' || settings.font_mode === 'external'
				? (settings.font_family_name || 'CustomFont') + ', Tahoma, sans-serif'
				: settings.font_mode === 'preset' && fontPresets[settings.font_preset]
				? fontPresets[settings.font_preset].family
				: 'Vazir, Tahoma, sans-serif',
		};

		const save = () => {
			setSaving(true);
			setNotice(null);
			request('/appearance', { method: 'POST', data: { settings } })
				.then((res) => {
					setNotice({ type: res.success ? 'success' : 'error', text: res.message || 'ذخیره شد.' });
					if (res.data) {
						setSettings(res.data.settings || {});
						setFontFiles(res.data.fontFiles || {});
						onUpdate(res.data);
					}
				})
				.finally(() => setSaving(false));
		};

		const reset = () => {
			setSaving(true);
			request('/appearance', { method: 'POST', data: { reset: true } })
				.then((res) => {
					setNotice({ type: 'success', text: res.message || 'بازنشانی شد.' });
					if (res.data) {
						setSettings(res.data.settings || {});
						onUpdate(res.data);
					}
				})
				.finally(() => setSaving(false));
		};

		const applyPreset = (preset) => {
			setSettings({ ...settings, ...preset });
		};

		return h('div', { className: 'nm-page' }, [
			h('header', { className: 'nm-page-header nm-page-header--row', key: 'head' }, [
				h('div', {}, [
					h('h2', {}, 'ظاهر قالب'),
					h('p', {}, 'رنگ‌ها، فونت و گردی گوشه‌ها — روی قالب، پنل ادمین و ویجت‌های Elementor.'),
				]),
				h('div', { className: 'nm-quick-actions' }, [
					h('button', { type: 'button', className: 'nm-btn nm-btn--outline', disabled: saving, onClick: reset }, 'بازنشانی'),
					h('button', { type: 'button', className: 'nm-btn nm-btn--primary', disabled: saving, onClick: save }, saving ? 'در حال ذخیره...' : 'ذخیره'),
				]),
			]),
			!themeActive
				? h('div', { className: 'nm-notice nm-notice--warning', key: 'warn' }, 'قالب NobatMed فعال نیست — تنظیمات پس از فعال‌سازی قالب روی سایت اعمال می‌شوند.')
				: null,
			notice ? h('div', { className: 'nm-notice nm-notice--' + notice.type, key: 'n' }, notice.text) : null,
			h('div', { className: 'nm-grid-2', key: 'grid' }, [
				h('section', { className: 'nm-panel', key: 'colors' }, [
					h('div', { className: 'nm-panel__head' }, h('h3', {}, 'رنگ‌ها و استایل')),
					h('div', { className: 'nm-color-grid' }, [
						...fields.map((f) =>
							h('label', { key: f.key, className: 'nm-color-field' }, [
								h('span', {}, f.label),
								h('input', {
									type: 'color',
									value: settings[f.key] || '#000000',
									onChange: (e) => setSettings({ ...settings, [f.key]: e.target.value }),
								}),
							])
						),
						h('label', { className: 'nm-color-field', key: 'radius' }, [
							h('span', {}, 'گردی گوشه (' + (settings.radius || 14) + 'px)'),
							h('input', {
								type: 'range',
								min: 4,
								max: 32,
								value: settings.radius || 14,
								onChange: (e) => setSettings({ ...settings, radius: parseInt(e.target.value, 10) }),
							}),
						]),
					]),
					h('div', { className: 'nm-preset-row', key: 'presets' }, [
						h('span', {}, 'پالت آماده:'),
						...Object.entries(presets).map(([id, preset]) =>
							h(
								'button',
								{
									key: id,
									type: 'button',
									className: 'nm-btn nm-btn--outline nm-btn--sm',
									onClick: () => applyPreset(preset),
								},
								preset.label || id
							)
						),
					]),
				]),
				h('section', { className: 'nm-panel', key: 'fonts' }, [
					h('div', { className: 'nm-panel__head' }, h('h3', {}, 'فونت')),
					h('div', { className: 'nm-form-row nm-form-row--stack' }, [
						h('label', { className: 'nm-field-label' }, 'منبع فونت'),
						h(
							'select',
							{
								value: settings.font_mode || 'default',
								onChange: (e) => setSettings({ ...settings, font_mode: e.target.value }),
							},
							[
								h('option', { value: 'default' }, 'پیش‌فرض (Vazir)'),
								h('option', { value: 'preset' }, 'فونت آماده'),
								h('option', { value: 'upload' }, 'آپلود فونت (woff/woff2/ttf)'),
								h('option', { value: 'external' }, 'لینک CSS خارجی'),
							]
						),
					]),
					settings.font_mode === 'preset'
						? h('div', { className: 'nm-form-row nm-form-row--stack', key: 'fp' }, [
								h('label', { className: 'nm-field-label' }, 'فونت آماده'),
								h(
									'select',
									{
										value: settings.font_preset || 'vazir',
										onChange: (e) => setSettings({ ...settings, font_preset: e.target.value }),
									},
									Object.entries(fontPresets).map(([id, p]) => h('option', { key: id, value: id }, p.label || id))
								),
						  ])
						: null,
					settings.font_mode === 'upload'
						? h('div', { className: 'nm-font-upload', key: 'fu' }, [
								h('label', { className: 'nm-field-label' }, 'نام فونت (font-family)'),
								h('input', {
									type: 'text',
									value: settings.font_family_name || '',
									placeholder: 'مثلاً MyClinicFont',
									onChange: (e) => setSettings({ ...settings, font_family_name: e.target.value }),
								}),
								h('div', { className: 'nm-font-upload__row' }, [
									h('div', {}, [
										h('strong', {}, 'Regular'),
										h('p', {}, (fontFiles.regular && fontFiles.regular.filename) || 'انتخاب نشده'),
										h(
											'button',
											{
												type: 'button',
												className: 'nm-btn nm-btn--outline nm-btn--sm',
												onClick: () =>
													pickFontFile((file) => {
														setFontFiles({ ...fontFiles, regular: file });
														setSettings({ ...settings, font_regular_id: file.id });
													}),
											},
											'انتخاب فایل'
										),
									]),
									h('div', {}, [
										h('strong', {}, 'Bold (اختیاری)'),
										h('p', {}, (fontFiles.bold && fontFiles.bold.filename) || 'انتخاب نشده'),
										h(
											'button',
											{
												type: 'button',
												className: 'nm-btn nm-btn--outline nm-btn--sm',
												onClick: () =>
													pickFontFile((file) => {
														setFontFiles({ ...fontFiles, bold: file });
														setSettings({ ...settings, font_bold_id: file.id });
													}),
											},
											'انتخاب فایل'
										),
									]),
								]),
						  ])
						: null,
					settings.font_mode === 'external'
						? h('div', { className: 'nm-form-row nm-form-row--stack', key: 'fe' }, [
								h('label', { className: 'nm-field-label' }, 'نام فونت'),
								h('input', {
									type: 'text',
									value: settings.font_family_name || '',
									onChange: (e) => setSettings({ ...settings, font_family_name: e.target.value }),
								}),
								h('label', { className: 'nm-field-label' }, 'URL فایل CSS (@font-face)'),
								h('input', {
									type: 'url',
									value: settings.font_external_url || '',
									placeholder: 'https://...',
									onChange: (e) => setSettings({ ...settings, font_external_url: e.target.value }),
								}),
						  ])
						: null,
					h('div', { className: 'nm-font-apply', key: 'apply' }, [
						h('span', { className: 'nm-field-label' }, 'اعمال فونت روی:'),
						h('label', { className: 'nm-check-inline' }, [
							h('input', {
								type: 'checkbox',
								checked: settings.font_apply_frontend !== false,
								onChange: (e) => setSettings({ ...settings, font_apply_frontend: e.target.checked }),
							}),
							'قالب (فرانت)',
						]),
						h('label', { className: 'nm-check-inline' }, [
							h('input', {
								type: 'checkbox',
								checked: settings.font_apply_admin !== false,
								onChange: (e) => setSettings({ ...settings, font_apply_admin: e.target.checked }),
							}),
							'پنل ادمین نوبت‌مد',
						]),
						h('label', { className: 'nm-check-inline' }, [
							h('input', {
								type: 'checkbox',
								checked: settings.font_apply_elementor !== false,
								onChange: (e) => setSettings({ ...settings, font_apply_elementor: e.target.checked }),
							}),
							'ویجت Elementor',
						]),
					]),
					h('p', { className: 'nm-panel__meta' }, 'برای ویجت‌های آینده از تابع nobatmed_get_appearance_font_family() استفاده کنید.'),
				]),
				h('section', { className: 'nm-panel nm-appearance-preview', key: 'preview', style: previewStyle }, [
					h('div', { className: 'nm-panel__head' }, h('h3', {}, 'پیش‌نمایش')),
					h('div', { className: 'nm-preview-card' }, [
						h('span', { className: 'nm-preview-card__dot' }),
						h('strong', {}, 'نوبت‌مد'),
						h('p', {}, 'نمونه متن و دکمه با رنگ و فونت انتخاب‌شده'),
						h('button', { type: 'button', className: 'nm-preview-btn' }, 'رزرو نوبت'),
					]),
				]),
			]),
		]);
	}

	function PluginsPage({ plugins, onPluginsUpdate }) {
		const [busy, setBusy] = useState(null);
		const [bulkBusy, setBulkBusy] = useState(false);
		const [notice, setNotice] = useState(null);

		const required = plugins.filter((p) => p.required);
		const optional = plugins.filter((p) => !p.required);
		const requiredPending = required.some((p) => p.status !== 'active');
		const STATUS = { active: 'فعال', inactive: 'نصب شده', missing: 'نصب نشده' };

		const handleInstall = (plugin) => {
			if (plugin.status === 'active' || plugin.action === 'none') return;
			setBusy(plugin.id);
			setNotice(null);
			request('/plugins/install', { method: 'POST', data: { id: plugin.id } })
				.then((res) => {
					if (res.plugins) onPluginsUpdate(res.plugins);
					setNotice({ type: 'success', text: plugin.name + ': ' + (res.message || 'انجام شد') });
				})
				.catch((err) => setNotice({ type: 'error', text: plugin.name + ': ' + (err.message || 'خطا') }))
				.finally(() => setBusy(null));
		};

		const handleBulk = () => {
			setBulkBusy(true);
			request('/plugins/install-required', { method: 'POST' })
				.then((res) => {
					if (res.plugins) onPluginsUpdate(res.plugins);
					setNotice({
						type: res.success ? 'success' : 'error',
						text: res.success ? 'همه پلاگین‌های ضروری نصب شدند.' : 'برخی پلاگین‌ها نصب نشدند.',
					});
				})
				.finally(() => setBulkBusy(false));
		};

		const renderCard = (plugin) =>
			h('article', { key: plugin.id, className: 'nm-plugin-card nm-plugin-card--' + plugin.status }, [
				h('div', { className: 'nm-plugin-card__head' }, [
					h('span', { className: 'nm-plugin-card__icon' }, plugin.name.charAt(0)),
					h('div', {}, [
						h('h4', {}, [plugin.name, plugin.required ? h('span', { className: 'nm-tag nm-tag--req' }, 'ضروری') : null]),
						h('span', { className: 'nm-status nm-status--' + plugin.status }, STATUS[plugin.status] || plugin.status),
					]),
				]),
				h('p', {}, plugin.description),
				plugin.status === 'active'
					? h('span', { className: 'nm-plugin-card__active' }, '✓ فعال و آماده')
					: plugin.action === 'install' || plugin.action === 'activate'
					? h(
							'button',
							{
								type: 'button',
								className: 'nm-btn nm-btn--primary nm-btn--sm',
								disabled: busy === plugin.id || bulkBusy,
								onClick: () => handleInstall(plugin),
							},
							busy === plugin.id ? 'در حال نصب...' : plugin.actionLabel
					  )
					: h('span', { className: 'nm-plugin-card__muted' }, 'دسترسی نصب ندارید'),
			]);

		return h('div', { className: 'nm-page' }, [
			h('header', { className: 'nm-page-header nm-page-header--row', key: 'head' }, [
				h('div', {}, [
					h('h2', {}, 'پلاگین‌های پیشنهادی'),
					h('p', {}, 'نصب مستقیم از مخزن وردپرس — بدون خروج از پنل.'),
				]),
				requiredPending
					? h(
							'button',
							{
								type: 'button',
								className: 'nm-btn nm-btn--primary',
								disabled: bulkBusy || !!busy,
								onClick: handleBulk,
							},
							bulkBusy ? 'در حال نصب...' : 'نصب همه ضروری‌ها'
					  )
					: null,
			]),
			notice ? h('div', { className: 'nm-notice nm-notice--' + notice.type, key: 'n' }, notice.text) : null,
			h('section', { className: 'nm-panel', key: 'req' }, [
				h('div', { className: 'nm-panel__head' }, h('h3', {}, 'ضروری')),
				h('div', { className: 'nm-plugin-grid' }, required.map(renderCard)),
			]),
			h('section', { className: 'nm-panel', key: 'opt' }, [
				h('div', { className: 'nm-panel__head' }, h('h3', {}, 'پیشنهادی')),
				h('div', { className: 'nm-plugin-grid' }, optional.map(renderCard)),
			]),
		]);
	}

	function BookingPage() {
		const [schedules, setSchedules] = useState([]);
		const [appointments, setAppointments] = useState([]);
		const [options, setOptions] = useState({ doctors: [], clinics: [], services: [] });
		const [loading, setLoading] = useState(true);
		const [slots, setSlots] = useState([]);
		const [jalali, setJalali] = useState('');
		const [notice, setNotice] = useState(null);
		const [scheduleForm, setScheduleForm] = useState({
			doctor_id: '',
			clinic_id: '',
			day_of_week: '0',
			start_time: '09:00',
			end_time: '17:00',
			slot_duration: '30',
		});
		const [bookForm, setBookForm] = useState({
			doctor_id: '',
			clinic_id: '',
			service_id: '',
			date: '',
			slot: null,
			visit_type: 'in_person',
			notes: '',
		});

		const load = useCallback(() => {
			setLoading(true);
			Promise.all([
				request('/booking/schedules'),
				request('/booking/appointments'),
				request('/profiles/options'),
			])
				.then(([s, a, o]) => {
					setSchedules((s.data && s.data.schedules) || []);
					setAppointments((a.data && a.data.appointments) || []);
					setOptions((o.data) || { doctors: [], clinics: [], services: [] });
				})
				.finally(() => setLoading(false));
		}, []);

		useEffect(() => {
			load();
		}, [load]);

		const days = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'];

		const selectOptions = (items, placeholder) => [
			h('option', { key: '', value: '' }, placeholder),
			...(items || []).map((item) => h('option', { key: item.id, value: String(item.id) }, item.title)),
		];

		const createSchedule = () => {
			if (!scheduleForm.doctor_id) {
				setNotice({ type: 'error', text: 'پزشک را انتخاب کنید.' });
				return;
			}
			request('/booking/schedules', {
				method: 'POST',
				data: {
					doctor_id: parseInt(scheduleForm.doctor_id, 10),
					clinic_id: parseInt(scheduleForm.clinic_id, 10) || 0,
					day_of_week: parseInt(scheduleForm.day_of_week, 10),
					start_time: scheduleForm.start_time + ':00',
					end_time: scheduleForm.end_time + ':00',
					slot_duration: parseInt(scheduleForm.slot_duration, 10) || 30,
				},
			}).then(() => {
				setNotice({ type: 'success', text: 'برنامه کاری ثبت شد.' });
				load();
			});
		};

		const loadSlots = () => {
			if (!bookForm.doctor_id || !bookForm.date) {
				setNotice({ type: 'error', text: 'پزشک و تاریخ را انتخاب کنید.' });
				return;
			}
			const q = '?doctor_id=' + encodeURIComponent(bookForm.doctor_id) + '&date=' + encodeURIComponent(bookForm.date);
			request('/booking/slots' + q).then((res) => {
				const data = res.data || {};
				setSlots(data.slots || []);
				setJalali(data.jalali || '');
				setBookForm({ ...bookForm, slot: null });
			});
		};

		const cancelAppointment = (row) => {
			if (row.status === 'cancelled') return;
			request('/booking/appointments/' + row.id + '/cancel', { method: 'POST' })
				.then((res) => {
					setNotice({ type: 'success', text: res.message || 'نوبت لغو شد.' });
					load();
				})
				.catch(() => setNotice({ type: 'error', text: 'لغو نوبت با خطا مواجه شد.' }));
		};

		const createAppointment = () => {
			if (!bookForm.doctor_id || !bookForm.date || !bookForm.slot) {
				setNotice({ type: 'error', text: 'پزشک، تاریخ و اسلات را انتخاب کنید.' });
				return;
			}
			request('/booking/appointments', {
				method: 'POST',
				data: {
					doctor_id: parseInt(bookForm.doctor_id, 10),
					clinic_id: parseInt(bookForm.clinic_id, 10) || 0,
					service_id: parseInt(bookForm.service_id, 10) || 0,
					appointment_date: bookForm.date,
					start_time: bookForm.slot.start,
					end_time: bookForm.slot.end,
					visit_type: bookForm.visit_type,
					notes: bookForm.notes,
					status: 'confirmed',
				},
			}).then((res) => {
				setNotice({ type: 'success', text: res.message || 'نوبت ثبت شد.' });
				setBookForm({ ...bookForm, slot: null, notes: '' });
				setSlots([]);
				load();
			});
		};

		return h('div', { className: 'nm-page' }, [
			h('header', { className: 'nm-page-header', key: 'head' }, [
				h('h2', {}, 'نوبت‌دهی'),
				h('p', {}, 'برنامه کاری هفتگی، انتخاب اسلات و ثبت نوبت — تاریخ شمسی کنار میلادی.'),
			]),
			notice ? h('div', { className: 'nm-notice nm-notice--' + notice.type, key: 'n' }, notice.text) : null,
			h('section', { className: 'nm-panel', key: 'sch-form' }, [
				h('div', { className: 'nm-panel__head' }, h('h3', {}, 'برنامه کاری هفتگی')),
				h('div', { className: 'nm-form-row' }, [
					h(
						'select',
						{ value: scheduleForm.doctor_id, onChange: (e) => setScheduleForm({ ...scheduleForm, doctor_id: e.target.value }) },
						selectOptions(options.doctors, 'انتخاب پزشک')
					),
					h(
						'select',
						{ value: scheduleForm.clinic_id, onChange: (e) => setScheduleForm({ ...scheduleForm, clinic_id: e.target.value }) },
						selectOptions(options.clinics, 'کلینیک (اختیاری)')
					),
					h(
						'select',
						{ value: scheduleForm.day_of_week, onChange: (e) => setScheduleForm({ ...scheduleForm, day_of_week: e.target.value }) },
						days.map((d, i) => h('option', { key: i, value: String(i) }, d))
					),
					h('input', { type: 'time', value: scheduleForm.start_time, onChange: (e) => setScheduleForm({ ...scheduleForm, start_time: e.target.value }) }),
					h('input', { type: 'time', value: scheduleForm.end_time, onChange: (e) => setScheduleForm({ ...scheduleForm, end_time: e.target.value }) }),
					h('input', {
						type: 'number',
						min: 5,
						step: 5,
						placeholder: 'مدت اسلات',
						value: scheduleForm.slot_duration,
						onChange: (e) => setScheduleForm({ ...scheduleForm, slot_duration: e.target.value }),
					}),
					h('button', { type: 'button', className: 'nm-btn nm-btn--primary nm-btn--sm', onClick: createSchedule }, 'ثبت برنامه'),
				]),
			]),
			h('section', { className: 'nm-panel', key: 'book-form' }, [
				h('div', { className: 'nm-panel__head' }, h('h3', {}, 'رزرو نوبت')),
				h('div', { className: 'nm-form-row' }, [
					h(
						'select',
						{ value: bookForm.doctor_id, onChange: (e) => setBookForm({ ...bookForm, doctor_id: e.target.value }) },
						selectOptions(options.doctors, 'انتخاب پزشک')
					),
					h(
						'select',
						{ value: bookForm.clinic_id, onChange: (e) => setBookForm({ ...bookForm, clinic_id: e.target.value }) },
						selectOptions(options.clinics, 'کلینیک')
					),
					h(
						'select',
						{ value: bookForm.service_id, onChange: (e) => setBookForm({ ...bookForm, service_id: e.target.value }) },
						selectOptions(options.services, 'خدمت')
					),
					h(
						'select',
						{ value: bookForm.visit_type, onChange: (e) => setBookForm({ ...bookForm, visit_type: e.target.value }) },
						[
							h('option', { value: 'in_person' }, 'حضوری'),
							h('option', { value: 'online' }, 'آنلاین'),
						]
					),
					h('button', { type: 'button', className: 'nm-btn nm-btn--outline nm-btn--sm', onClick: loadSlots }, 'نمایش اسلات‌ها'),
				]),
				h('div', { className: 'nm-jalali-picker-wrap', key: 'cal' }, [
					h('p', { className: 'nm-panel__meta' }, 'تاریخ نوبت (تقویم شمسی)'),
					h(JalaliDatePicker, {
						value: bookForm.date,
						onChange: (iso) => {
							setBookForm({ ...bookForm, date: iso, slot: null });
							setSlots([]);
							setJalali(formatJalaliLabel(iso));
						},
					}),
				]),
				slots.length
					? h(
							'div',
							{ className: 'nm-slots', key: 'slots' },
							slots.map((slot) =>
								h(
									'button',
									{
										key: slot.start,
										type: 'button',
										className: 'nm-slot-btn' + (bookForm.slot && bookForm.slot.start === slot.start ? ' is-active' : ''),
										onClick: () => setBookForm({ ...bookForm, slot }),
									},
									slot.label
								)
							)
					  )
					: bookForm.date && bookForm.doctor_id
					? h('p', { key: 'empty' }, 'اسلات آزاد نیست — ابتدا برنامه کاری برای آن روز ثبت کنید.')
					: null,
				h('textarea', {
					placeholder: 'یادداشت (اختیاری)',
					value: bookForm.notes,
					onChange: (e) => setBookForm({ ...bookForm, notes: e.target.value }),
					rows: 2,
					style: { width: '100%', marginTop: '8px' },
				}),
				h('button', { type: 'button', className: 'nm-btn nm-btn--primary nm-btn--sm', style: { marginTop: '8px' }, onClick: createAppointment }, 'ثبت نوبت'),
			]),
			loading
				? h('p', { key: 'load' }, 'در حال بارگذاری...')
				: [
						h('section', { className: 'nm-panel', key: 'sch' }, [
							h('div', { className: 'nm-panel__head' }, [
								h('h3', {}, 'برنامه‌های ثبت‌شده'),
								h('span', { className: 'nm-panel__count' }, schedules.length + ' مورد'),
							]),
							schedules.length
								? h('table', { className: 'nm-table' }, [
										h('thead', {}, h('tr', {}, [h('th', {}, 'پزشک'), h('th', {}, 'روز'), h('th', {}, 'ساعت')])),
										h(
											'tbody',
											{},
											schedules.map((row) =>
												h('tr', { key: row.id }, [
													h('td', {}, row.doctor_title || row.doctor_id),
													h('td', {}, days[row.day_of_week] || row.day_of_week),
													h('td', {}, row.start_time + ' – ' + row.end_time),
												])
											)
										),
								  ])
								: h('p', {}, 'هنوز برنامه‌ای ثبت نشده.'),
						]),
						h('section', { className: 'nm-panel', key: 'ap' }, [
							h('div', { className: 'nm-panel__head' }, [
								h('h3', {}, 'نوبت‌های ثبت‌شده'),
								h('span', { className: 'nm-panel__count' }, appointments.length + ' مورد'),
							]),
							appointments.length
								? h('table', { className: 'nm-table' }, [
										h('thead', {}, h('tr', {}, [h('th', {}, 'تاریخ'), h('th', {}, 'پزشک'), h('th', {}, 'ساعت'), h('th', {}, 'وضعیت'), h('th', {}, '')])),
										h(
											'tbody',
											{},
											appointments.map((row) =>
												h('tr', { key: row.id }, [
													h('td', {}, row.appointment_date + (row.appointment_date ? ' · ' + formatJalaliLabel(row.appointment_date) : '')),
													h('td', {}, row.doctor_title || row.doctor_id),
													h('td', {}, row.start_time),
													h('td', {}, row.status),
													row.status !== 'cancelled'
														? h('td', {}, h('button', {
																type: 'button',
																className: 'nm-btn nm-btn--ghost nm-btn--sm',
																onClick: () => cancelAppointment(row),
														  }, 'لغو'))
														: h('td', {}, '—'),
												])
											)
										),
								  ])
								: h('p', {}, 'هنوز نوبتی ثبت نشده.'),
						]),
				  ],
		]);
	}

	function AddonsPage({ modules }) {
		const addons = modules.filter((m) => m.type === 'addon' || m.group === 'addons');

		return h('div', { className: 'nm-page' }, [
			h('header', { className: 'nm-page-header', key: 'head' }, [
				h('h2', {}, 'افزونه‌ها (Add-on)'),
				h('p', {}, 'هر add-on پس از توسعه در Orbit Hub ثبت می‌شود — تا آن زمان غیرفعال و قفل است.'),
			]),
			h('section', { className: 'nm-panel' }, [
				h('div', { className: 'nm-module-grid' }, [
					...addons.map((mod) => {
						const key = mod.implemented ? mod.devStatus || 'progress' : 'pending';
						const meta = DEV_STATUS[key] || DEV_STATUS.pending;
						return h(
							'article',
							{ key: mod.id, className: 'nm-module-card is-soon' + (mod.implemented ? ' is-dev' : '') },
							[
								h('div', { className: 'nm-module-card__meta' }, [
									h('span', { className: 'nm-tag ' + meta.className }, meta.label),
									h('span', { className: 'nm-tag nm-tag--addon' }, 'Add-on'),
								]),
								h('h4', {}, mod.name),
								h('p', {}, mod.description),
								h('footer', { className: 'nm-module-card__foot' }, 'فاز ' + mod.phase + (mod.orbitProduct ? ' · ' + mod.orbitProduct : '')),
							]
						);
					}),
					h('article', { className: 'nm-module-card is-soon', key: 'hint' }, [
						h('h4', {}, 'ثبت Add-on سفارشی'),
						h('p', {}, 'از hook «nobatmed_core_init» ماژول خود را register کنید.'),
					]),
				]),
			]),
		]);
	}

	function LicensePanel() {
		const [state, setState] = useState({ status: 'inactive', license_key: '', notices: [] });
		const [loading, setLoading] = useState(true);

		useEffect(() => {
			request('/state')
				.then((res) => setState((res.data && res.data.state) || {}))
				.finally(() => setLoading(false));
		}, []);

		return h('div', { className: 'nm-app nm-app--centered' }, [
			h('h2', {}, 'فعال‌سازی لایسنس'),
			loading ? h('p', {}, 'در حال بارگذاری...') : h('p', {}, state.message || state.status),
		]);
	}

	function App() {
		if (cfg.licenseEnabled) {
			return h(AppChrome, {}, h(LicensePanel));
		}

		const [page, setPage] = useState('dashboard');
		const [data, setData] = useState(null);
		const [loading, setLoading] = useState(true);
		const [error, setError] = useState(null);
		const [saving, setSaving] = useState(null);

		const load = useCallback(() => {
			setLoading(true);
			setError(null);
			request('/dashboard')
				.then((res) => setData(res))
				.catch(() => setError('بارگذاری پنل با خطا مواجه شد.'))
				.finally(() => setLoading(false));
		}, []);

		useEffect(() => {
			load();
		}, [load]);

		if (loading) {
			return h(AppChrome, {}, h('div', { className: 'nm-app nm-app--centered' }, [h('div', { className: 'nm-spinner' }), h('p', {}, 'در حال بارگذاری...')]));
		}

		if (error || !data) {
			return h(AppChrome, {}, h('div', { className: 'nm-app nm-app--centered' }, [
				h('p', {}, error),
				h('button', { type: 'button', className: 'nm-btn nm-btn--primary', onClick: load }, 'تلاش مجدد'),
			]));
		}

		const titles = {
			dashboard: strings.dashboard || 'داشبورد',
			modules: strings.modules || 'ماژول‌ها',
			appearance: strings.appearance || 'ظاهر قالب',
			plugins: strings.plugins || 'پلاگین‌ها',
			booking: strings.booking || 'نوبت‌دهی',
			notices: strings.notices || 'اعلان‌ها',
			addons: strings.addons || 'افزونه‌ها',
		};

		return h(AppChrome, {}, h('div', { className: 'nm-app' }, [
			h(Sidebar, { active: page, onNavigate: setPage, key: 'sb' }),
			h('main', { className: 'nm-main', key: 'main' }, [
				h('header', { className: 'nm-topbar' }, h('h1', {}, titles[page] || page)),
				h('div', { className: 'nm-main__scroll' }, [
					page === 'dashboard' ? h(DashboardPage, { data, onNavigate: setPage }) : null,
					page === 'modules'
						? h(ModulesPage, {
								modules: data.modules || [],
								onUpdate: (mods) => setData({ ...data, modules: mods }),
								saving,
								setSaving,
						  })
						: null,
					page === 'appearance'
						? h(AppearancePage, {
								appearance: data.appearance,
								onUpdate: (appearance) => setData({ ...data, appearance }),
						  })
						: null,
					page === 'plugins'
						? h(PluginsPage, {
								plugins: data.plugins || [],
								onPluginsUpdate: (plugins) => {
									const active = plugins.filter((p) => p.status === 'active').length;
									setData({
										...data,
										plugins,
										stats: { ...data.stats, pluginsActive: active },
									});
								},
						  })
						: null,
					page === 'booking' ? h(BookingPage) : null,
					page === 'notices'
						? h(NoticesPage, {
								orbit: data.orbit,
								onOrbitUpdate: (orbit) => setData({ ...data, orbit }),
						  })
						: null,
					page === 'addons' ? h(AddonsPage, { modules: data.modules || [] }) : null,
				]),
			]),
		]));
	}

	const root = document.getElementById('nobatmed-core-admin');
	if (root) {
		window.wp.element.render(h(App), root);
	}
})();
