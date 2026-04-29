import Alpine from 'alpinejs';
import Sortable from 'sortablejs';

window.Alpine = Alpine;
window.Sortable = Sortable;

Alpine.data('structureSorter', () => ({
    init() {
        if (!this.$refs.list) {
            return;
        }

        Sortable.create(this.$refs.list, {
            animation: 180,
            handle: '.drag-handle',
        });
    },
}));

Alpine.start();
