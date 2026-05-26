var categorySelect = document.querySelector('[data-category-select]');
var typeSelect = document.querySelector('[data-type-select]');

function updateVehicleTypes() {
    if (!categorySelect || !typeSelect) {
        return;
    }

    var selectedCategory = categorySelect.value;
    var firstVisibleValue = '';

    for (var i = 0; i < typeSelect.options.length; i++) {
        var option = typeSelect.options[i];
        var optionCategory = option.getAttribute('data-category');
        var show = option.value === '' || optionCategory === selectedCategory;

        option.hidden = !show;

        if (show && option.value !== '' && firstVisibleValue === '') {
            firstVisibleValue = option.value;
        }
    }

    var currentOption = typeSelect.options[typeSelect.selectedIndex];
    if (currentOption && currentOption.hidden) {
        typeSelect.value = firstVisibleValue;
    }
}

if (categorySelect) {
    categorySelect.onchange = updateVehicleTypes;
    updateVehicleTypes();
}

