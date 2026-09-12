<script>
(function () {
    'use strict';

    var sheet = document.getElementById('receiptLayoutSheet');
    var fields = Array.prototype.slice.call(document.querySelectorAll('.receipt-adjustable-field[data-field-key]'));
    var readout = document.getElementById('receiptPositionReadout');
    var selectedLabel = document.getElementById('selectedReceiptField');
    var fontInput = document.getElementById('receiptFontSize');
    var fontFamilyInput = document.getElementById('receiptFontFamily');
    var boldInput = document.getElementById('receiptBold');
    var positionXInput = document.getElementById('receiptPositionX');
    var positionYInput = document.getElementById('receiptPositionY');
    var resetButton = document.getElementById('resetReceiptPositions');
    var deleteButton = document.getElementById('deleteReceiptField');
    var gridToggle = document.getElementById('toggleReceiptGrid');
    var lockToggle = document.getElementById('toggleReceiptLock');
    var visibilityControls = Array.prototype.slice.call(document.querySelectorAll('.receipt-field-visibility[data-field-key]'));
    var selectedField = null;
    var drag = null;

    if (!sheet || !fields.length) return;

    function rounded(value) {
        return Math.round(value * 100) / 100;
    }

    function positionInput(axis, key) {
        return document.getElementById('layout_' + axis + '_' + key);
    }

    function fontSizeInput(key) {
        return document.getElementById('font_size_' + key);
    }

    function fontFamilyField(key) {
        return document.getElementById('font_family_' + key);
    }

    function boldField(key) {
        return document.getElementById('bold_field_' + key);
    }

    function point(field) {
        var key = field.dataset.fieldKey;
        return {
            x: parseFloat(positionInput('x', key).value || 0),
            y: parseFloat(positionInput('y', key).value || 0)
        };
    }

    function updateControls(field) {
        var key = field.dataset.fieldKey;
        var current = point(field);
        selectedLabel.textContent = field.dataset.fieldLabel || key;
        fontInput.disabled = false;
        fontFamilyInput.disabled = false;
        boldInput.disabled = false;
        positionXInput.disabled = false;
        positionYInput.disabled = false;
        fontInput.value = fontSizeInput(key).value;
        fontFamilyInput.value = fontFamilyField(key).value;
        boldInput.checked = boldField(key).checked;
        positionXInput.value = current.x;
        positionYInput.value = current.y;
    }

    function applyPosition(field, x, y) {
        var key = field.dataset.fieldKey;
        x = Math.max(-100, Math.min(100, rounded(x)));
        y = Math.max(-100, Math.min(100, rounded(y)));
        positionInput('x', key).value = x;
        positionInput('y', key).value = y;
        field.style.transform = 'translate(' + x + 'mm, ' + y + 'mm)';
        if (selectedField === field) {
            positionXInput.value = x;
            positionYInput.value = y;
        }
        if (readout) {
            readout.textContent = (field.dataset.fieldLabel || key) + ': X ' + x.toFixed(2) + ' mm, Y ' + y.toFixed(2) + ' mm';
        }
    }

    function applyFontSize(field, size) {
        var key = field.dataset.fieldKey;
        size = Math.max(6, Math.min(36, rounded(size)));
        fontSizeInput(key).value = size;
        field.style.fontSize = size + 'pt';
        if (selectedField === field) fontInput.value = size;
    }

    function applyFontFamily(field, family) {
        var key = field.dataset.fieldKey;
        var option = Array.prototype.find.call(fontFamilyInput.options, function (item) { return item.value === family; });
        if (!option) return;
        fontFamilyField(key).value = family;
        field.style.fontFamily = option.dataset.stack;
        if (selectedField === field) fontFamilyInput.value = family;
    }

    function applyBold(field, bold) {
        var key = field.dataset.fieldKey;
        boldField(key).checked = bold;
        field.style.setProperty('font-weight', bold ? '700' : '400', 'important');
        if (selectedField === field) boldInput.checked = bold;
    }

    function selectField(field) {
        fields.forEach(function (item) { item.classList.toggle('is-active', item === field); });
        selectedField = field;
        if (deleteButton) deleteButton.disabled = false;
        updateControls(field);
        var current = point(field);
        applyPosition(field, current.x, current.y);
    }

    function fieldByKey(key) {
        return fields.find(function (field) { return field.dataset.fieldKey === key; });
    }

    function visibilityControl(key) {
        return visibilityControls.find(function (control) { return control.dataset.fieldKey === key; });
    }

    function setFieldVisibility(key, visible) {
        var field = fieldByKey(key);
        var control = visibilityControl(key);
        if (!field) return;
        field.classList.toggle('receipt-field-hidden', !visible);
        field.style.display = visible ? '' : 'none';
        if (control) control.checked = visible;

        if (!visible && selectedField === field) {
            field.classList.remove('is-active');
            selectedField = null;
            selectedLabel.textContent = 'Select a field';
            fontInput.disabled = true;
            fontFamilyInput.disabled = true;
            boldInput.disabled = true;
            positionXInput.disabled = true;
            positionYInput.disabled = true;
            if (deleteButton) deleteButton.disabled = true;
            if (readout) readout.textContent = 'Field hidden. Use Choose printable fields to restore it.';
        }
    }

    fields.forEach(function (field) {
        field.addEventListener('focus', function () { selectField(field); });
        field.addEventListener('pointerdown', function (event) {
            if (event.button !== 0) return;
            event.preventDefault();
            selectField(field);
            field.focus();
            if (lockToggle && lockToggle.checked) return;
            var current = point(field);
            drag = {
                field: field,
                pointerId: event.pointerId,
                startX: event.clientX,
                startY: event.clientY,
                x: current.x,
                y: current.y
            };
            field.setPointerCapture(event.pointerId);
        });

        field.addEventListener('pointermove', function (event) {
            if (!drag || drag.field !== field || drag.pointerId !== event.pointerId) return;
            var sheetWidthMm = parseFloat(sheet.dataset.sheetWidthMm || '210');
            var pixelsPerMm = sheet.getBoundingClientRect().width / sheetWidthMm;
            applyPosition(field, drag.x + ((event.clientX - drag.startX) / pixelsPerMm), drag.y + ((event.clientY - drag.startY) / pixelsPerMm));
        });

        function endDrag(event) {
            if (!drag || drag.field !== field || drag.pointerId !== event.pointerId) return;
            field.releasePointerCapture(event.pointerId);
            drag = null;
        }

        field.addEventListener('pointerup', endDrag);
        field.addEventListener('pointercancel', endDrag);

        field.addEventListener('keydown', function (event) {
            if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].indexOf(event.key) === -1) return;
            if (lockToggle && lockToggle.checked) return;
            event.preventDefault();
            selectField(field);
            var current = point(field);
            var step = event.shiftKey ? 0.1 : 0.5;
            if (event.key === 'ArrowUp') current.y -= step;
            if (event.key === 'ArrowDown') current.y += step;
            if (event.key === 'ArrowLeft') current.x -= step;
            if (event.key === 'ArrowRight') current.x += step;
            applyPosition(field, current.x, current.y);
        });
    });

    fontInput.addEventListener('input', function () {
        var size = parseFloat(this.value);
        if (!selectedField || isNaN(size)) return;
        applyFontSize(selectedField, size);
    });

    fontFamilyInput.addEventListener('change', function () {
        if (!selectedField) return;
        applyFontFamily(selectedField, this.value);
    });

    boldInput.addEventListener('change', function () {
        if (!selectedField) return;
        applyBold(selectedField, this.checked);
    });

    function applyCoordinateInput() {
        if (!selectedField) return;
        var x = parseFloat(positionXInput.value);
        var y = parseFloat(positionYInput.value);
        if (isNaN(x) || isNaN(y)) return;
        applyPosition(selectedField, x, y);
    }

    positionXInput.addEventListener('input', applyCoordinateInput);
    positionYInput.addEventListener('input', applyCoordinateInput);

    if (gridToggle) {
        gridToggle.addEventListener('change', function () {
            sheet.classList.toggle('show-grid', this.checked);
        });
    }

    if (lockToggle) {
        lockToggle.addEventListener('change', function () {
            sheet.classList.toggle('fields-locked', this.checked);
        });
    }

    visibilityControls.forEach(function (control) {
        control.addEventListener('change', function () {
            setFieldVisibility(this.dataset.fieldKey, this.checked);
        });
        setFieldVisibility(control.dataset.fieldKey, control.checked);
    });

    if (deleteButton) {
        deleteButton.addEventListener('click', function () {
            if (!selectedField) return;
            setFieldVisibility(selectedField.dataset.fieldKey, false);
        });
    }

    if (resetButton) {
        resetButton.addEventListener('click', function () {
            fields.forEach(function (field) {
                var fontControl = fontSizeInput(field.dataset.fieldKey);
                applyPosition(field, 0, 0);
                applyFontSize(field, parseFloat(fontControl.dataset.default || 9));
                applyFontFamily(field, fontFamilyField(field.dataset.fieldKey).dataset.default || 'arial');
                applyBold(field, false);
                setFieldVisibility(field.dataset.fieldKey, true);
            });
            selectField(fields[0]);
            if (readout) readout.textContent = 'Default positions and font styles restored. Click Save Layout to keep them.';
        });
    }

    var firstVisibleField = fields.find(function (field) {
        var control = visibilityControl(field.dataset.fieldKey);
        return !control || control.checked;
    });
    if (firstVisibleField) selectField(firstVisibleField);
})();
</script>
