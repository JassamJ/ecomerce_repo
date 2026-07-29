$(document).ready(function () {

    // --- 1. Inicializar Slick para el Slider de Subcategorías ---
    $('.subcategory-slider').slick({
        infinite: false,
        slidesToShow: 6,
        slidesToScroll: 2,
        dots: false,
        arrows: true,
        responsive: [
            { breakpoint: 1024, settings: { slidesToShow: 4 } },
            { breakpoint: 768, settings: { slidesToShow: 3 } },
            { breakpoint: 480, settings: { slidesToShow: 2 } }
        ]
    });

    // --- 2. Funciones de Utilidad ---
    
    // Normaliza texto para que "Cintas" coincida con "cintas" o "cintás"
    function normalizeText(text) {
        return text.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").trim();
    }

    // Convierte "Cintas Especiales" en "cintas-especiales" para comparar con la URL
    function slugify(text) {
        return text.toString().toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .replace(/\s+/g, '-')
            .replace(/[^\w\-]+/g, '')
            .replace(/\-\-+/g, '-')
            .trim();
    }

    // --- 3. Lógica de Filtrado Principal ---
    function filterProducts() {
        const searchText = normalizeText($("#searchInput").val());

        // Recolectar valores seleccionados
        const selectedCategories = $(".category_item:checked").map((_, el) => el.value).get();
        const selectedSubcategories = $(".subcategory_item:checked").map((_, el) => el.value).get();
        const selectedIndustries = $(".industry_item:checked").map((_, el) => el.value).get();

        $(".product-item").each(function () {
            // Datos del producto (vienen del PHP data-attribute)
            const productCategories = ($(this).data("category") || "").toString().split(", ").filter(Boolean);
            const productSubcategories = ($(this).data("subcategory") || "").toString().split(", ").filter(Boolean);
            const productIndustries = ($(this).data("industry") || "").toString().split(", ").filter(Boolean);

            const title = normalizeText($(this).find(".card-title").text());
            const subtitle = normalizeText($(this).find(".card-text").text());

            // Validaciones de coincidencia
            const matchSearch = !searchText || title.includes(searchText) || subtitle.includes(searchText);
            
            const matchCategory = selectedCategories.length === 0 || 
                                 productCategories.some(c => selectedCategories.includes(c));
            
            const matchSubcategory = selectedSubcategories.length === 0 || 
                                    productSubcategories.some(s => selectedSubcategories.includes(s));
            
            const matchIndustry = selectedIndustries.length === 0 || 
                                 productIndustries.some(i => selectedIndustries.includes(i));

            // Mostrar u ocultar
            if (matchSearch && matchCategory && matchSubcategory && matchIndustry) {
                $(this).removeClass('d-none').addClass('d-flex');
            } else {
                $(this).removeClass('d-flex').addClass('d-none');
            }
        });

        // Mensaje de "No se encontraron resultados"
        if ($(".product-item:not(.d-none)").length === 0) {
            if ($("#no-results").length === 0) {
                $("#productList").append('<div id="no-results" class="col-12 text-center mt-5 py-5"><i class="bi bi-search" style="font-size: 3rem; color: #ccc;"></i><p class="text-muted mt-3">No encontramos productos que coincidan con tu búsqueda.</p></div>');
            }
        } else {
            $("#no-results").remove();
        }
    }

    // --- 4. Manejo de Parámetros de URL al Cargar ---
    const params = new URLSearchParams(window.location.search);
    const urlCategory = params.get("category");
    const urlSubcategory = params.get("subcategory");
    const urlIndustry = params.get("industry");

    if (urlCategory) {
        $(".category_item").each(function () {
            if (slugify($(this).val()) === urlCategory) $(this).prop("checked", true);
        });
    }

    if (urlIndustry) {
        $(".industry_item").each(function () {
            if (slugify($(this).val()) === urlIndustry) $(this).prop("checked", true);
        });
    }

    if (urlSubcategory) {
        $(".subcategory_item").each(function () {
            if (slugify($(this).val()) === urlSubcategory) {
                $(this).prop("checked", true);
                $(this).closest('.sub-card').addClass('active');
            }
        });
    }

    // --- 5. Eventos de Usuario ---

    // Input de búsqueda
    $("#searchInput").on("input", filterProducts);

    // Checkboxes laterales (Industria y Categoría)
    $(".category_item, .industry_item").on("change", function() {
        $(".all_item").prop("checked", false);
        filterProducts();
    });

    // Clic en los cuadros del Slider (Subcategorías)
    $(document).on('click', '.sub-card', function() {
        const checkbox = $(this).find('.subcategory_item');
        checkbox.prop('checked', !checkbox.prop('checked'));
        $(this).toggleClass('active');
        $(".all_item").prop("checked", false);
        filterProducts();
    });

    // Checkbox "Todo"
    $(".all_item").on("change", function() {
        if($(this).is(":checked")) {
            $(".category_item, .industry_item, .subcategory_item").prop("checked", false);
            $(".sub-card").removeClass("active");
            $("#searchInput").val("");
            filterProducts();
        }
    });

    // --- 6. Ejecución Inicial ---
    filterProducts();
});