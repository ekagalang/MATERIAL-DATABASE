(function () {
    window.materialCalcCreateAdditionalWorkItemFormEngine = function (config) {
        const safeConfig = config && typeof config === 'object' ? config : {};
        const deps = safeConfig.deps && typeof safeConfig.deps === 'object' ? safeConfig.deps : {};
        const initial = safeConfig.initial && typeof safeConfig.initial === 'object' ? safeConfig.initial : {};
        const afterElement = safeConfig.afterElement || null;
        const options = safeConfig.options && typeof safeConfig.options === 'object' ? safeConfig.options : {};

        const additionalWorkItemsList = deps.additionalWorkItemsList || null;
        const normalizeBundleRowKind = typeof deps.normalizeBundleRowKind === 'function'
            ? deps.normalizeBundleRowKind
            : function (value) { return String(value || '').trim().toLowerCase(); };
        const normalizeBundleItem = typeof deps.normalizeBundleItem === 'function'
            ? deps.normalizeBundleItem
            : function (item) { return item || {}; };
        const getAllAdditionalWorkRows = typeof deps.getAllAdditionalWorkRows === 'function'
            ? deps.getAllAdditionalWorkRows
            : function () { return []; };
        const escapeHtml = typeof deps.escapeHtml === 'function'
            ? deps.escapeHtml
            : function (raw) { return String(raw || ''); };
        const buildBundleMaterialFilterSectionHtml = typeof deps.buildBundleMaterialFilterSectionHtml === 'function'
            ? deps.buildBundleMaterialFilterSectionHtml
            : function () { return ''; };
        const resolveAdditionalInsertionTarget = typeof deps.resolveAdditionalInsertionTarget === 'function'
            ? deps.resolveAdditionalInsertionTarget
            : function () {
                return {
                    parent: additionalWorkItemsList,
                    referenceNode: null,
                };
            };
        const bindDimensionExpressionInputs = typeof deps.bindDimensionExpressionInputs === 'function'
            ? deps.bindDimensionExpressionInputs
            : function () {};
        const setAdditionalWorkItemRowKind = typeof deps.setAdditionalWorkItemRowKind === 'function'
            ? deps.setAdditionalWorkItemRowKind
            : function () {};
        const refreshAdditionalTaxonomyActionFooters = typeof deps.refreshAdditionalTaxonomyActionFooters === 'function'
            ? deps.refreshAdditionalTaxonomyActionFooters
            : function () {};
        const initAdditionalWorkTaxonomyAutocomplete = typeof deps.initAdditionalWorkTaxonomyAutocomplete === 'function'
            ? deps.initAdditionalWorkTaxonomyAutocomplete
            : function () {};
        const initAdditionalWorkTypeAutocomplete = typeof deps.initAdditionalWorkTypeAutocomplete === 'function'
            ? deps.initAdditionalWorkTypeAutocomplete
            : function () {};
        const initAdditionalMaterialTypeFilters = typeof deps.initAdditionalMaterialTypeFilters === 'function'
            ? deps.initAdditionalMaterialTypeFilters
            : function () {};
        const applyMaterialCustomizeFiltersToPanels = typeof deps.applyMaterialCustomizeFiltersToPanels === 'function'
            ? deps.applyMaterialCustomizeFiltersToPanels
            : function () {};
        const attachAdditionalWorkItemEvents = typeof deps.attachAdditionalWorkItemEvents === 'function'
            ? deps.attachAdditionalWorkItemEvents
            : function () {};
        const applyAdditionalWorkItemVisibility = typeof deps.applyAdditionalWorkItemVisibility === 'function'
            ? deps.applyAdditionalWorkItemVisibility
            : function () {};
        const refreshAdditionalWorkItemHeader = typeof deps.refreshAdditionalWorkItemHeader === 'function'
            ? deps.refreshAdditionalWorkItemHeader
            : function () {};
        const syncBundleFromForms = typeof deps.syncBundleFromForms === 'function'
            ? deps.syncBundleFromForms
            : function () {};

        let localAdditionalAutocompleteSeq = 0;
        const nextBundleAdditionalAutocompleteSeq = typeof deps.nextBundleAdditionalAutocompleteSeq === 'function'
            ? deps.nextBundleAdditionalAutocompleteSeq
            : function () {
                localAdditionalAutocompleteSeq += 1;
                return localAdditionalAutocompleteSeq;
            };
        function createAdditionalWorkItemForm(initial = {}, afterElement = null, options = {}) {
            if (!additionalWorkItemsList) {
                return null;
            }

            const requestedRowKind = normalizeBundleRowKind(options.rowKind || initial.row_kind);
            const item = normalizeBundleItem(
                {
                    ...initial,
                    row_kind: requestedRowKind,
                },
                getAllAdditionalWorkRows().length + 1,
            );
            const wrapper = document.createElement('div');
            wrapper.className = 'additional-work-item';
            wrapper.setAttribute('data-additional-work-item', 'true');
            wrapper.setAttribute('data-row-kind', item.row_kind);
            wrapper.innerHTML = `
                <div class="additional-work-item-grid">
                    <input type="hidden" data-field="title" value="${escapeHtml(item.title)}">
                    <input type="hidden" data-field="row_kind" value="${escapeHtml(item.row_kind)}">
                    <div class="additional-taxonomy-header">
                        <div class="additional-taxonomy-cell" data-taxonomy-cell="floor">
                            <label class="additional-taxonomy-cell-label">Lantai</label>
                            <div class="additional-taxonomy-cell-body">
                                <div class="work-type-autocomplete">
                                    <div class="work-type-input">
                                        <input type="text"
                                               class="autocomplete-input"
                                               data-field-display="work_floor"
                                               placeholder="Pilih lantai..."
                                               autocomplete="off"
                                               value="">
                                    </div>
                                    <div class="autocomplete-list" data-field-list="work_floor" id="additionalWorkFloor-list-${nextBundleAdditionalAutocompleteSeq()}"></div>
                                </div>
                            </div>
                            <input type="hidden" data-field="work_floor" value="${escapeHtml(item.work_floor)}">
                        </div>
                        <div class="additional-taxonomy-cell" data-taxonomy-cell="area">
                            <label class="additional-taxonomy-cell-label">Area</label>
                            <div class="additional-taxonomy-cell-body">
                                <div class="work-type-autocomplete">
                                    <div class="work-type-input">
                                        <input type="text"
                                               class="autocomplete-input"
                                               data-field-display="work_area"
                                               placeholder="Pilih area..."
                                               autocomplete="off"
                                               value="">
                                    </div>
                                    <div class="autocomplete-list" data-field-list="work_area" id="additionalWorkArea-list-${nextBundleAdditionalAutocompleteSeq()}"></div>
                                </div>
                            </div>
                            <input type="hidden" data-field="work_area" value="${escapeHtml(item.work_area)}">
                        </div>
                        <div class="additional-taxonomy-cell" data-taxonomy-cell="field">
                            <label class="additional-taxonomy-cell-label">Bidang</label>
                            <div class="additional-taxonomy-cell-body">
                                <div class="work-type-autocomplete">
                                    <div class="work-type-input">
                                        <input type="text"
                                               class="autocomplete-input"
                                               data-field-display="work_field"
                                               placeholder="Pilih bidang..."
                                               autocomplete="off"
                                               value="">
                                    </div>
                                    <div class="autocomplete-list" data-field-list="work_field" id="additionalWorkField-list-${nextBundleAdditionalAutocompleteSeq()}"></div>
                                </div>
                                <button type="button"
                                        class="taxonomy-level-btn taxonomy-toggle-item-btn"
                                        data-action="toggle-item-visibility"
                                        title="Sembunyikan Item Pekerjaan pada bidang ini"
                                        aria-label="Sembunyikan Item Pekerjaan pada bidang ini"
                                        aria-pressed="false">
                                    <i class="bi bi-chevron-up" aria-hidden="true"></i>
                                </button>
                            </div>
                            <input type="hidden" data-field="work_field" value="${escapeHtml(item.work_field)}">
                        </div>
                    </div>
                    <div class="form-group work-type-group additional-worktype-group taxonomy-inline-item">
                        <label data-additional-worktype-label>Item Pekerjaan</label>
                        <div class="input-wrapper">
                            <div class="work-type-autocomplete">
                                <div class="work-type-input additional-worktype-input">
                                    <input type="text"
                                           class="autocomplete-input"
                                           data-field-display="work_type"
                                           placeholder="Pilih atau ketik item pekerjaan..."
                                           autocomplete="off"
                                           value="">
                                    <button type="button"
                                            class="additional-worktype-suffix-btn"
                                            data-action="remove"
                                            title="Hapus item pekerjaan ini">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <div class="autocomplete-list" data-field-list="work_type" id="additionalWorkType-list-${nextBundleAdditionalAutocompleteSeq()}"></div>
                            </div>
                            <input type="hidden" data-field="work_type" value="${escapeHtml(item.work_type)}">
                            <div class="dimension-area-summary worktype-inline-summary" data-dimension-area-summary>
                                <div class="dimension-area-summary-value" data-dimension-area-value>-</div>
                            </div>
                        </div>
                    </div>
                    <div class="dimensions-container-vertical additional-dimensions-container">
                        <div class="additional-parameter-split">
                        <div class="additional-parameter-size-col">
                        <div class="dimension-area-layout additional-dimension-area-layout" data-dimension-area-layout>
                            <div class="dimension-area-inputs">
                                <div class="dimension-item" data-wrap="wall_length">
                                    <label>Panjang</label>
                                    <div class="input-with-unit">
                                        <input type="text" inputmode="text" data-allow-expression="1" step="0.01" min="0.01" data-field="wall_length" value="${escapeHtml(item.wall_length)}">
                                        <span class="unit">M</span>
                                        <span class="dimension-expression-hint" data-expression-hint hidden></span>
                                    </div>
                                </div>
                                <div class="dimension-item" data-wrap="wall_height">
                                    <label data-wall-height-label>Tinggi</label>
                                    <div class="input-with-unit">
                                        <input type="text" inputmode="text" data-allow-expression="1" step="0.01" min="0.01" data-field="wall_height" value="${escapeHtml(item.wall_height)}">
                                        <span class="unit">M</span>
                                        <span class="dimension-expression-hint" data-expression-hint hidden></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="dimension-item" data-wrap="mortar_thickness">
                            <label>Tebal Adukan</label>
                            <div class="input-with-unit">
                                <input type="text" inputmode="decimal" step="0.01" min="0" data-field="mortar_thickness" value="${escapeHtml(item.mortar_thickness || '2')}">
                                <span class="unit">cm</span>
                            </div>
                        </div>
                        <div class="dimension-item" data-wrap="layer_count">
                            <label>Lapis / Tingkat</label>
                            <div class="input-with-unit" style="background-color: #fffbeb; border-color: #fcd34d;">
                                <input type="text" inputmode="decimal" step="1" min="0" data-field="layer_count" value="${escapeHtml(item.layer_count || '1')}">
                                <span class="unit" style="background-color: #fef3c7;">Lapis</span>
                            </div>
                        </div>
                        <div class="dimension-item" data-wrap="plaster_sides">
                            <label>Sisi Plesteran</label>
                            <div class="input-with-unit" style="background-color: #e0f2fe; border-color: #7dd3fc;">
                                <input type="text" inputmode="decimal" step="1" min="0" data-field="plaster_sides" value="${escapeHtml(item.plaster_sides || '1')}">
                                <span class="unit" style="background-color: #bae6fd;">Sisi</span>
                            </div>
                        </div>
                        <div class="dimension-item" data-wrap="skim_sides">
                            <label>Sisi Acian</label>
                            <div class="input-with-unit" style="background-color: #e0e7ff; border-color: #a5b4fc;">
                                <input type="text" inputmode="decimal" step="1" min="0" data-field="skim_sides" value="${escapeHtml(item.skim_sides || '1')}">
                                <span class="unit" style="background-color: #c7d2fe;">Sisi</span>
                            </div>
                        </div>
                        <div class="dimension-item" data-wrap="grout_thickness">
                            <label>Tebal Nat</label>
                            <div class="input-with-unit" style="background-color: #f1f5f9; border-color: #cbd5e1;">
                                <input type="text" inputmode="decimal" step="0.01" min="0" data-field="grout_thickness" value="${escapeHtml(item.grout_thickness || '2')}">
                                <span class="unit" style="background-color: #e2e8f0;">mm</span>
                            </div>
                        </div>
                        <div class="dimension-item" data-wrap="ceramic_length">
                            <label>Panjang Keramik</label>
                            <div class="input-with-unit" style="background-color: #fef3c7; border-color: #fde047;">
                                <input type="text" inputmode="decimal" step="0.01" min="0" data-field="ceramic_length" value="${escapeHtml(item.ceramic_length || '30')}">
                                <span class="unit" style="background-color: #fef08a;">cm</span>
                            </div>
                        </div>
                        <div class="dimension-item" data-wrap="ceramic_width">
                            <label>Lebar Keramik</label>
                            <div class="input-with-unit" style="background-color: #fef3c7; border-color: #fde047;">
                                <input type="text" inputmode="decimal" step="0.01" min="0" data-field="ceramic_width" value="${escapeHtml(item.ceramic_width || '30')}">
                                <span class="unit" style="background-color: #fef08a;">cm</span>
                            </div>
                        </div>
                        <div class="dimension-item" data-wrap="ceramic_thickness">
                            <label>Tebal Keramik</label>
                            <div class="input-with-unit" style="background-color: #fef3c7; border-color: #fde047;">
                                <input type="text" inputmode="decimal" step="0.01" min="0" data-field="ceramic_thickness" value="${escapeHtml(item.ceramic_thickness || '8')}">
                                <span class="unit" style="background-color: #fef08a;">mm</span>
                            </div>
                        </div>
                        </div>
                        <div class="additional-parameter-material-col">
                        ${buildBundleMaterialFilterSectionHtml(item)}
                        </div>
                        </div>
                    </div>
                    <div class="additional-taxonomy-actions-row">
                        <button type="button" class="taxonomy-level-btn" data-action="add-item">
                            + Item Pekerjaan
                        </button>
                        <button type="button" class="taxonomy-level-btn" data-action="add-area">
                            + Area
                        </button>
                        <button type="button" class="taxonomy-level-btn" data-action="add-field">
                            + Bidang
                        </button>
                    </div>
                    <div class="additional-area-children" data-area-children></div>
                    <div class="additional-floor-children" data-floor-children></div>
                </div>
            `;

            const initialModeWorkType = String(item.work_type || '').trim();
            const initialMortarInput = wrapper.querySelector('[data-field="mortar_thickness"]');
            const hasInitialMortarValue = String(item.mortar_thickness || '').trim() !== '';
            const isInitialAci = ['skim_coating', 'coating_floor'].includes(initialModeWorkType);
            if (initialMortarInput && initialModeWorkType) {
                // Preserve the original unit context on restored rows so unit conversion
                // does not run with a wrong assumption (which could append an extra zero).
                initialMortarInput.dataset.unit = isInitialAci ? 'mm' : 'cm';
            }
            if (initialMortarInput && initialModeWorkType && hasInitialMortarValue) {
                initialMortarInput.dataset.mode = isInitialAci ? 'acian' : 'adukan';
            }

            const target = resolveAdditionalInsertionTarget(item, afterElement, options);
            if (target.referenceNode && target.referenceNode.parentNode === target.parent) {
                target.parent.insertBefore(wrapper, target.referenceNode);
            } else {
                target.parent.appendChild(wrapper);
            }
            bindDimensionExpressionInputs(wrapper);

            setAdditionalWorkItemRowKind(wrapper, item.row_kind);
            refreshAdditionalTaxonomyActionFooters(wrapper);
            initAdditionalWorkTaxonomyAutocomplete(wrapper, item);
            initAdditionalWorkTypeAutocomplete(wrapper, item);
            if (typeof wrapper.__refreshWorkTypeOptions === 'function') {
                wrapper.__refreshWorkTypeOptions();
            }
            initAdditionalMaterialTypeFilters(wrapper, item.material_type_filters || {});
            applyMaterialCustomizeFiltersToPanels(wrapper, item.material_customize_filters || {});
            attachAdditionalWorkItemEvents(wrapper);
            applyAdditionalWorkItemVisibility(wrapper);
            refreshAdditionalWorkItemHeader();
            syncBundleFromForms();

            const hasInitialWorkType = String(item.work_type || '').trim() !== '';
            const shouldAutoFocusWorkType = normalizeBundleRowKind(item.row_kind) === 'item';
            if (shouldAutoFocusWorkType && !hasInitialWorkType) {
                const workTypeDisplay = wrapper.querySelector('[data-field-display="work_type"]');
                if (workTypeDisplay) {
                    setTimeout(() => {
                        workTypeDisplay.focus();
                        if (typeof workTypeDisplay.__openAdditionalWorkTypeList === 'function') {
                            workTypeDisplay.__openAdditionalWorkTypeList();
                        }
                    }, 0);
                }
            }

            return wrapper;
        }



        return createAdditionalWorkItemForm(initial, afterElement, options);
    };
})();