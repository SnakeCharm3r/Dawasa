import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

window.idleTimer = function (minutes) {
    return {
        timer: null,
        start() {
            this.reset();
        },
        reset() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => {
                fetch('/logout', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                    },
                }).finally(() => {
                    window.location.href = '/login?idle=1';
                });
            }, minutes * 60 * 1000);
        },
    };
};

Alpine.start();
