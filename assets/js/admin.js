(function () {
	const { createElement: h, useEffect, useState, useCallback } = window.wp.element;
	const apiFetch = window.wp.apiFetch;
	const cfg = window.nobatmedCoreConfig || {};
	const strings = cfg.strings || {};

	apiFetch.use(apiFetch.createNonceMiddleware(cfg.nonce));

	function request(path, options) {
		return apiFetch({ path: '/nobatmed-core/v1' + path, ...options });
	}

	const ICONS = { dashboard: '◉', modules: '▦', plugins: '⬡', booking: '◷', addons: '✦', notices: '◈' };

	const DEV_STATUS = {
		done: { label: 'آماده', className: 'nm-tag--done' },
		progress: { label: 'در حال توسعه', className: 'nm-tag--progress' },
		pending: { label: 'برنامه‌ریزی', className: 'nm-tag--pending' },
	};

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
							className: 'nm-sidebar__link' + (active === item.id ? ' is-active' : ''),
							onClick: () => onNavigate(item.id),
						},
						[h('span', { className: 'nm-sidebar__icon' }, ICONS[item.id] || '•'), item.label]
					)
				)
			),
			h('div', { className: 'nm-sidebar__footer', key: 'foot' }, 'v' + (cfg.version || '0.2.0')),
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
					h('input', {
						type: 'date',
						value: bookForm.date,
						onChange: (e) => setBookForm({ ...bookForm, date: e.target.value, slot: null }),
					}),
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
				jalali ? h('p', { className: 'nm-jalali-label', key: 'jl' }, 'تاریخ شمسی: ' + jalali) : null,
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
													h('td', {}, row.appointment_date),
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
