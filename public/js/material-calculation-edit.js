function initMaterialCalculationEdit(root, config) {
    const scope = root || document;
    const form = scope.querySelector('#calculatorForm') || document.getElementById('calculatorForm');
    if (!form || form.__materialCalculationEditInited) {
        return;
    }
    form.__materialCalculationEditInited = true;

    const installationTypes = (config && config.installationTypes) || [];
    const previewUrl = (config && config.previewUrl) || '';
    const csrfToken = (config && config.csrfToken) || '';

    const presetRadio = scope.querySelector('#ratio_preset') || document.getElementById('ratio_preset');
    const customRadio = scope.querySelector('#ratio_custom') || document.getElementById('ratio_custom');
    const presetSection = scope.querySelector('#preset_section') || document.getElementById('preset_section');
    const customSection = scope.querySelector('#custom_section') || document.getElementById('custom_section');
    const useCustomRatioInput = scope.querySelector('#use_custom_ratio') || document.getElementById('use_custom_ratio');

    const wallLengthInput = scope.querySelector('#wall_length') || document.getElementById('wall_length');
    const wallHeightInput = scope.querySelector('#wall_height') || document.getElementById('wall_height');
    const wallAreaDisplay = scope.querySelector('#wall_area_display') || document.getElementById('wall_area_display');

    const installationSelect = scope.querySelector('#installation_type_id') || document.getElementById('installation_type_id');
    const installationDescription = scope.querySelector('#installation_description') || document.getElementById('installation_description');

    const mortarFormulaSelect = scope.querySelector('#mortar_formula_id') || document.getElementById('mortar_formula_id');
    const customCementInput = scope.querySelector('#custom_cement_ratio') || document.getElementById('custom_cement_ratio');
    const customSandInput = scope.querySelector('#custom_sand_ratio') || document.getElementById('custom_sand_ratio');
    const ratioDisplay = scope.querySelector('#ratio_display') || document.getElementById('ratio_display');

    const previewButton = scope.querySelector('#btnPreview') || document.getElementById('btnPreview');
    const resultPanel = scope.querySelector('#resultPanel') || document.getElementById('resultPanel');
    const resultContent = scope.querySelector('#resultContent') || document.getElementById('resultContent');

    function getSmartPrecision(num) {
        if (!isFinite(num)) return 0;
        if (Math.floor(num) === num) return 0;

        const str = num.toFixed(30);
        const decimalPart = (str.split('.')[1] || '');
        let firstNonZero = decimalPart.length;
        for (let i = 0; i < decimalPart.length; i++) {
            if (decimalPart[i] !== '0') {
                firstNonZero = i;
                break;
            }
        }

        if (firstNonZero === decimalPart.length) return 0;
        return firstNonZero + 2;
    }

    function normalizeSmartDecimal(value) {
        const num = Number(value);
        if (!isFinite(num)) return NaN;
        const precision = getSmartPrecision(num);
        return precision ? Number(num.toFixed(precision)) : num;
    }

    function formatSmartDecimal(value) {
        const num = Number(value);
        if (!isFinite(num)) return '';
        const precision = getSmartPrecision(num);
        if (!precision) return num.toString();
        return num.toFixed(precision).replace(/\.?0+$/, '');
    }

    function toggleRatioMethod() {
        const customSelected = customRadio && customRadio.checked;

        if (presetSection) {
            presetSection.style.display = customSelected ? 'none' : 'block';
        }
        if (customSection) {
            customSection.style.display = customSelected ? 'block' : 'none';
        }
        if (useCustomRatioInput) {
            useCustomRatioInput.value = customSelected ? '1' : '0';
        }

        if (mortarFormulaSelect) {
            mortarFormulaSelect.required = !customSelected;
        }
        if (customCementInput) {
            customCementInput.required = !!customSelected;
        }
        if (customSandInput) {
            customSandInput.required = !!customSelected;
        }

        updateRatioDisplay();
    }

    function updateRatioDisplay() {
        let ratioText = '';
        const customSelected = customRadio && customRadio.checked;

        if (customSelected) {
            const cement = customCementInput ? (customCementInput.value || 1) : 1;
            const sand = customSandInput ? (customSandInput.value || 4) : 4;
            ratioText = `${cement}:${sand}`;
        } else if (mortarFormulaSelect) {
            const selected = mortarFormulaSelect.options[mortarFormulaSelect.selectedIndex];
            if (selected) {
                const cement = selected.getAttribute('data-cement');
                const sand = selected.getAttribute('data-sand');
                ratioText = `${cement}:${sand}`;
            }
        }

        if (ratioDisplay && ratioText) {
            ratioDisplay.textContent = ratioText;
        }
    }

    function updateWallArea() {
        const length = wallLengthInput ? parseFloat(wallLengthInput.value) || 0 : 0;
        const height = wallHeightInput ? parseFloat(wallHeightInput.value) || 0 : 0;
        const area = length * height;
        if (wallAreaDisplay) {
            const normalizedArea = normalizeSmartDecimal(area);
            wallAreaDisplay.textContent = normalizedArea > 0 ? formatSmartDecimal(normalizedArea) : '';
        }
    }

    function updateInstallationDescription() {
        if (!installationSelect || !installationDescription) {
            return;
        }
        const selectedId = installationSelect.value;
        const type = installationTypes.find(function(item) { return String(item.id) === String(selectedId); });
        installationDescription.textContent = type ? type.description : '';
    }

    function displayResult(summary) {
        if (!summary || !resultContent) {
            return;
        }

        const html = `
            <div class="row">
                <div class="col-md-6 mb-3">
                    <h6 class="text-success"><i class="fas fa-ruler"></i> Informasi Dinding</h6>
                    <table class="table table-sm">
                        <tr><td>Panjang:</td><td class="text-end"><strong>${summary.wall_info.length}</strong></td></tr>
                        <tr><td>Tinggi:</td><td class="text-end"><strong>${summary.wall_info.height}</strong></td></tr>
                        <tr><td>Luas:</td><td class="text-end"><strong>${summary.wall_info.area}</strong></td></tr>
                    </table>
                </div>
                <div class="col-md-6 mb-3">
                    <h6 class="text-success"><i class="fas fa-th"></i> Kebutuhan Bata</h6>
                    <table class="table table-sm">
                        <tr><td>Jumlah:</td><td class="text-end"><strong>${summary.brick_info.quantity}</strong></td></tr>
                        <tr><td>Jenis:</td><td class="text-end"><strong>${summary.brick_info.type}</strong></td></tr>
                        <tr><td>Biaya:</td><td class="text-end text-success"><strong>${summary.brick_info.cost}</strong></td></tr>
                    </table>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h6 class="text-success"><i class="fas fa-box"></i> Semen</h6>
                    <table class="table table-sm">
                        <tr><td>${summary.materials.cement.package_weight} kg:</td><td class="text-end"><strong>${summary.materials.cement.quantity_sak}</strong></td></tr>
                        <tr><td>Total kg:</td><td class="text-end"><strong>${summary.materials.cement.kg}</strong></td></tr>
                        <tr><td>Biaya:</td><td class="text-end text-success"><strong>${summary.materials.cement.cost}</strong></td></tr>
                    </table>
                </div>
                <div class="col-md-4 mb-3">
                    <h6 class="text-success"><i class="fas fa-mountain"></i> Pasir</h6>
                    <table class="table table-sm">
                        <tr><td>Karung:</td><td class="text-end"><strong>${summary.materials.sand.sak}</strong></td></tr>
                        <tr><td>Berat:</td><td class="text-end"><strong>${summary.materials.sand.kg}</strong></td></tr>
                        <tr><td>Volume:</td><td class="text-end"><strong>${summary.materials.sand.m3}</strong></td></tr>
                        <tr><td>Biaya:</td><td class="text-end text-success"><strong>${summary.materials.sand.cost}</strong></td></tr>
                    </table>
                </div>
                <div class="col-md-4 mb-3">
                    <h6 class="text-success"><i class="fas fa-tint"></i> Air</h6>
                    <table class="table table-sm">
                        <tr><td>Kebutuhan:</td><td class="text-end"><strong>${summary.materials.water.liters}</strong></td></tr>
                    </table>
                </div>
            </div>
            <hr>
            <div class="alert alert-success mb-0">
                <h5 class="mb-0">
                    <i class="fas fa-money-bill-wave"></i> 
                    Total Estimasi Biaya Baru: <strong>${summary.total_cost}</strong>
                </h5>
            </div>
        `;

        resultContent.innerHTML = html;
        if (resultPanel) {
            resultPanel.style.display = 'block';
            resultPanel.scrollIntoView({ behavior: 'smooth' });
        }
    }

    function handlePreview() {
        if (!previewUrl || !window.fetch) {
            return;
        }

        const formData = new FormData(form);

        fetch(previewUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: formData,
        })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data && data.success) {
                    displayResult(data.summary);
                } else {
                    const message = 'Error: ' + (data && data.message ? data.message : 'Tidak diketahui');
                    window.showToast(message, 'error');
                }
            })
            .catch(function(error) {
                console.error('Error:', error);
                const message = 'Terjadi kesalahan saat menghitung';
                window.showToast(message, 'error');
            });
    }

    if (presetRadio) {
        presetRadio.addEventListener('change', toggleRatioMethod);
    }
    if (customRadio) {
        customRadio.addEventListener('change', toggleRatioMethod);
    }
    if (mortarFormulaSelect) {
        mortarFormulaSelect.addEventListener('change', updateRatioDisplay);
    }
    if (customCementInput) {
        customCementInput.addEventListener('input', updateRatioDisplay);
    }
    if (customSandInput) {
        customSandInput.addEventListener('input', updateRatioDisplay);
    }
    if (wallLengthInput) {
        wallLengthInput.addEventListener('input', updateWallArea);
    }
    if (wallHeightInput) {
        wallHeightInput.addEventListener('input', updateWallArea);
    }
    if (installationSelect) {
        installationSelect.addEventListener('change', updateInstallationDescription);
    }
    if (previewButton) {
        previewButton.addEventListener('click', handlePreview);
    }

    updateWallArea();
    updateInstallationDescription();
    toggleRatioMethod();
    updateRatioDisplay();
}
