import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const limbahMonthElement = document.getElementById('limbah-month-total');

    if (!limbahMonthElement) {
        return;
    }

    const loadLimbahMonthTotal = async () => {
        try {
            const response = await window.axios.get('/api/limbah-summary');
            const total = response?.data?.total_kg_bulan_ini ?? 0;

            limbahMonthElement.textContent = Number(total).toLocaleString('id-ID');
        } catch (error) {
            console.error('Gagal memuat data limbah:', error);
        }
    };

    loadLimbahMonthTotal();
    setInterval(loadLimbahMonthTotal, 10000);
});
