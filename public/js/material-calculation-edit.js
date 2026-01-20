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

    function truncateNumber(value, decimals = 2) {
        const num = Number(value);
        if (!isFinite(num)) return NaN;
        const factor = 10 ** decimals;
        const truncated = num >= 0 ? Math.floor(num * factor) : Math.ceil(num * factor);
        return truncated / factor;
    }

    function formatPlainNumber(value, decimals = 2) {
        if (value === '' || value === null || value === undefined) return '';
        const num = Number(value);
        if (!isFinite(num)) return '';
        const factor = 10 ** decimals;
        const truncated = num >= 0 ? Math.floor(num * factor) : Math.ceil(num * factor);
        const sign = truncated < 0 ? '-' : '';
        const abs = Math.abs(truncated);
        const intPart = Math.floor(abs / factor).toString();
        const decPart = (abs % factor).toString().padStart(decimals, '0');
        return `${sign}${intPart}.${decPart}`;
    }

    function formatDynamicPlain(value) {
        if (value === '' || value === null || value === undefined) return '';
        const num = Number(value);
        if (!isFinite(num)) return '';
        if (num === 0) return '0';

        const absValue = Math.abs(num);
        const epsilon = Math.min(absValue * 1e-12, 1e-6);
        const adjusted = num + (num >= 0 ? epsilon : -epsilon);
        const sign = adjusted < 0 ? '-' : '';
        const abs = Math.abs(adjusted);
        const intPart = Math.trunc(abs);

        if (intPart > 0) {
            const scaled = Math.trunc(abs * 100);
            const intDisplay = Math.trunc(scaled / 100).toString();
            let decPart = String(scaled % 100).padStart(2, '0');
            decPart = decPart.replace(/0+$/, '');
            return decPart ? `${sign}${intDisplay}.${decPart}` : `${sign}${intDisplay}`;
        }

        let fraction = abs;
        let digits = '';
        let firstNonZeroIndex = null;
        const maxDigits = 30;

        for (let i = 0; i < maxDigits; i++) {
            fraction *= 10;
            const digit = Math.floor(fraction + 1e-12);
            fraction -= digit;
            digits += String(digit);

            if (digit !== 0 && firstNonZeroIndex === null) {
                firstNonZeroIndex = i;
            }

            if (firstNonZeroIndex !== null && i >= firstNonZeroIndex + 1) {
                break;
            }
        }

        digits = digits.replace(/0+$/, '');
        if (!digits) return '0';
        return `${sign}0.${digits}`;
    }

    function normalizeSmartDecimal(value) {
        const plain = formatDynamicPlain(value);
        const num = plain ? Number(plain) : NaN;
        return isFinite(num) ? num : NaN;
    }

    function formatSmartDecimal(value) {
        const plain = formatDynamicPlain(value);
        if (!plain) return '';
        return plain.replace('.', ',');
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
