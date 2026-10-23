document.addEventListener('DOMContentLoaded', () => {
    const authTabs = document.querySelector('#customer_login');

    if (!authTabs) return;

    const tabs = authTabs.querySelectorAll('.auth-tabs__title');
    const panels = authTabs.querySelectorAll('.auth-tabs__panel');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.tab;

            // Снимаем активный класс со всех табов
            tabs.forEach(item => item.classList.remove('is-active'));

            // Снимаем активный класс со всех панелей
            panels.forEach(panel => panel.classList.remove('is-active'));

            // Активируем выбранный таб
            tab.classList.add('is-active');

            // Активируем соответствующую панель
            const activePanel = authTabs.querySelector(
                `.auth-tabs__panel[data-panel="${target}"]`
            );

            if (activePanel) {
                activePanel.classList.add('is-active');
            }
        });
    });
});