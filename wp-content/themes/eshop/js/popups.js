document.addEventListener("DOMContentLoaded", function () {

    document.querySelectorAll("[data-popup]").forEach(button => {

        button.addEventListener("click", function (e) {

            e.preventDefault();

            const popupId = this.dataset.popup;

            if (!popupId) {
                return;
            }

            const targetSelector = `#${popupId}`;
            const target = document.querySelector(targetSelector);

            if (!target) {
                console.error("Попап не найден:", targetSelector);
                return;
            }

            /*
             * Попап "О товаре"
             */
            if (popupId === "popup-about-product") {

                const productName = this.dataset.productName || "";
                const productSku = this.dataset.productSku || "—";

                const title = target.querySelector(".product-question-title");
                const nameField = target.querySelector('[name="product-name"]');
                const skuField = target.querySelector('[name="product-sku"]');

                if (title) {
                    title.textContent =
                        `Задайте свой вопрос о товаре ${productName} (ID: ${productSku})`;
                }

                if (nameField) {
                    nameField.value = productName;
                }

                if (skuField) {
                    skuField.value = productSku;
                }
            }


            /*
             * Попап "Купить в один клик"
             */
            if (popupId === "buy-one-click-popup") {

                const productName = this.dataset.productName || "";
                const productSku = this.dataset.productSku || "—";
                const productPrice = this.dataset.productPrice || "";
                const productUrl = this.dataset.productUrl || "";
                const productId = this.dataset.productId || "";

                console.log("Купить в 1 клик:", {
                    productName,
                    productSku,
                    productPrice,
                    productUrl,
                    productId
                });

                /*
                 * Визуальная информация о товаре
                 */
                const nameElement = target.querySelector(
                    ".buy-one-click__product-name"
                );

                const skuElement = target.querySelector(
                    ".buy-one-click__product-sku"
                );

                const priceElement = target.querySelector(
                    ".buy-one-click__product-price"
                );

                if (nameElement) {
                    nameElement.textContent = productName;
                }

                if (skuElement) {
                    skuElement.textContent = `Артикул: ${productSku}`;
                }

                if (priceElement) {
                    priceElement.textContent = productPrice;
                }


                /*
                 * Поля CF7
                 */
                const form = target.querySelector("form.wpcf7-form");

                if (!form) {
                    console.error("CF7 форма не найдена внутри попапа");
                } else {

                    const setField = (name, value) => {

                        const field = form.querySelector(
                            `[name="${name}"]`
                        );

                        if (!field) {
                            console.error(`Поле CF7 не найдено: ${name}`);
                            return;
                        }

                        field.value = value;
                    };

                    setField("product-name", productName);
                    setField("product-sku", productSku);
                    setField("product-id", productId);
                    setField("product-url", productUrl);
                    setField("product-price", productPrice);

                    console.log("CF7 поля заполнены");
                }
            }


            /*
             * Открываем Fancybox
             */
            if (typeof Fancybox === "undefined") {
                console.error("Fancybox не загружен");
                return;
            }

            Fancybox.show([
                {
                    src: targetSelector,
                    type: "inline"
                }
            ]);

        });

    });


    /*
     * CF7 после успешной отправки
     */
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