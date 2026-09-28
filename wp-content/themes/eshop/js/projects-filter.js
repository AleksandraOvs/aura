function animateProjects() {
    const items = document.querySelectorAll(
        '.projects-list .project-item:not(.is-visible)'
    );

    items.forEach((item, index) => {

        setTimeout(() => {
            item.classList.add('is-visible');
        }, index * 100);

    });
}

document.addEventListener('DOMContentLoaded', () => {

    const filter = document.querySelector('[data-projects-filter]');
    const projectsList = document.querySelector('[data-projects-list]');

    if (!filter || !projectsList) {
        return;
    }

    const buttons = filter.querySelectorAll('.project-tag');

    // Анимация проектов при первой загрузке
    animateProjects();

    buttons.forEach((button) => {

        button.addEventListener('click', async () => {

            const tagId = button.dataset.projectTag;

            if (button.classList.contains('active')) {
                return;
            }

            buttons.forEach((item) => {
                item.classList.remove('active');
            });

            button.classList.add('active');

            projectsList.classList.add('is-loading');

            const formData = new FormData();

            formData.append('action', 'filter_projects');
            formData.append('nonce', projectsFilter.nonce);
            formData.append(
                'tag_id',
                tagId === 'all' ? 0 : tagId
            );

            try {

                const response = await fetch(
                    projectsFilter.ajaxUrl,
                    {
                        method: 'POST',
                        body: formData,
                    }
                );

                const result = await response.json();

                if (!result.success) {
                    throw new Error('Ошибка фильтрации');
                }

                projectsList.innerHTML = result.data.html;

                // Анимация новых проектов
                animateProjects();

            } catch (error) {

                console.error(error);

            } finally {

                projectsList.classList.remove('is-loading');

            }
        });

    });

});