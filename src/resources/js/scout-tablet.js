import { enqueueShot, flushQueue, playFeedback, registerScoutPwa, loadQueue } from './scout-pwa';

document.addEventListener('alpine:init', () => {
    Alpine.data('scoutTablet', (config) => ({
        matchId: config.matchId,
        csrf: config.csrf,
        holdTimer: null,
        queueCount: loadQueue(config.matchId).length,
        soundOn: localStorage.getItem('scount-scout-sound') !== '0',
        online: navigator.onLine,

        init() {
            registerScoutPwa();
            window.addEventListener('online', () => this.onOnline());
            window.addEventListener('offline', () => {
                this.online = false;
            });
            const bindLivewire = () => {
                Livewire.on('scout-feedback', (payload) => {
                    const kind = payload?.kind ?? payload?.[0]?.kind ?? 'success';
                    playFeedback(kind === 'undo' ? 'undo' : 'success');
                });
                Livewire.on('scout-toast', (payload) => {
                    const message = payload?.message ?? payload?.[0]?.message ?? '';
                    const type = payload?.type ?? payload?.[0]?.type ?? 'info';
                    if (message) {
                        this.toast(message, type);
                    }
                });
            };
            if (window.Livewire) {
                bindLivewire();
            } else {
                document.addEventListener('livewire:init', bindLivewire);
            }
            this.syncQueueBadge();
        },

        toast(message, type = 'info') {
            const el = document.getElementById('scout-toast');
            if (!el) {
                return;
            }
            el.textContent = message;
            el.className = `fixed bottom-20 left-1/2 z-50 -translate-x-1/2 rounded-full px-4 py-2 text-sm font-semibold shadow-lg ${
                type === 'success' ? 'bg-emerald-500 text-zinc-900' : type === 'warning' ? 'bg-amber-500 text-zinc-900' : 'bg-zinc-700 text-white'
            }`;
            el.classList.remove('hidden');
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => el.classList.add('hidden'), 2200);
        },

        toggleSound() {
            this.soundOn = !this.soundOn;
            localStorage.setItem('scount-scout-sound', this.soundOn ? '1' : '0');
        },

        formatClock(seconds) {
            const m = Math.floor(seconds / 60);
            const s = seconds % 60;
            return `${m}:${String(s).padStart(2, '0')}`;
        },

        tryRegister(shotType, result) {
            if (!navigator.onLine) {
                const athleteId = this.$wire.selectedAthleteId;
                if (!athleteId) {
                    this.toast('Selecione um atleta.', 'warning');
                    return;
                }
                const payload = {
                    athlete_id: athleteId,
                    shot_type: shotType,
                    result,
                    period: this.$wire.period,
                    court_x: this.$wire.courtX,
                    court_y: this.$wire.courtY,
                };
                enqueueShot(this.matchId, payload);
                this.syncQueueBadge();
                playFeedback('success');
                this.toast('Salvo na fila offline.', 'warning');
                return;
            }
            this.$wire.register(shotType, result);
        },

        async onOnline() {
            this.online = true;
            const result = await flushQueue(this.matchId, this.csrf);
            this.syncQueueBadge();
            if (result.processed > 0) {
                Livewire.dispatch('flush-offline-queue');
                this.toast(`${result.processed} lançamento(s) sincronizados.`, 'success');
            }
        },

        syncQueueBadge() {
            this.queueCount = loadQueue(this.matchId).length;
        },

        startHoldUndo() {
            this.holdTimer = setTimeout(() => {
                this.$wire.undoLast();
            }, 650);
        },

        cancelHoldUndo() {
            if (this.holdTimer) {
                clearTimeout(this.holdTimer);
                this.holdTimer = null;
            }
        },
    }));
});