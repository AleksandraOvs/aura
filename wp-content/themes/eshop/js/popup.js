document.addEventListener("DOMContentLoaded", function () {

    const fancyboxTargets = [
        "#consultation-popup",
        "#partners-popup"
    ];

    document.querySelectorAll('a[href^="#"]').forEach(link => {

        link.addEventListener("click", function (e) {

            const targetSelector = this.getAttribute("href");

            if (!targetSelector || targetSelector === "#") {
                return;
            }

            // Fancybox popup
            if (fancyboxTargets.includes(targetSelector)) {

                e.preventDefault();

                if (typeof Fancybox !== "undefined") {
                    Fancybox.show([
                        {
                            src: targetSelector,
                            type: "inline"
                        }
                    ]);
                }

                return;
            }

            // Обычный якорь
            e.preventDefault();

            smoothScrollToElement(targetSelector, 800);
        });
    });


    // CF7 после успешной отправки
    document.addEventListener("wpcf7mailsent", function (event) {

        const mainForm = document.querySelector("#main-form");

        if (mainForm && mainForm.contains(event.target)) {

            event.target.style.display = "none";

            if (mainForm.querySelector(".main-form-success")) {
                return;
            }

            const successMessage = document.createElement("div");

            successMessage.className = "main-form-success";

            successMessage.innerHTML = `
                <h3>Спасибо, форма отправлена.</h3>
                <p>Мы свяжемся с вами в ближайшее время.</p>
            `;

            mainForm.appendChild(successMessage);
        }

    }, false);

});