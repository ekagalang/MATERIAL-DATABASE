(function () {
    window.materialCalcCreateDimensionExpressionEngine = function (config) {
        const safeConfig = config && typeof config === 'object' ? config : {};
        const deps = safeConfig.deps && typeof safeConfig.deps === 'object' ? safeConfig.deps : {};

        const getAllAdditionalWorkRows = typeof deps.getAllAdditionalWorkRows === 'function'
            ? deps.getAllAdditionalWorkRows
            : function () { return []; };
        const getMainWallLengthInput = typeof deps.getMainWallLengthInput === 'function'
            ? deps.getMainWallLengthInput
            : function () { return null; };
        const getMainWallHeightInput = typeof deps.getMainWallHeightInput === 'function'
            ? deps.getMainWallHeightInput
            : function () { return null; };
        const getCalcExpressionStateKey = typeof deps.getCalcExpressionStateKey === 'function'
            ? deps.getCalcExpressionStateKey
            : function () { return ''; };

function formatFixedPlain(value, decimals = 2) {
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

function formatThicknessValue(value) {
    return formatFixedPlain(value, 2);
}

const dimensionExpressionFieldKeys = new Set(['wall_length', 'wall_height']);

function getDimensionExpressionFieldKey(inputEl) {
    if (!(inputEl instanceof HTMLInputElement)) {
        return '';
    }
    if (inputEl.id === 'wallLength') {
        return 'wall_length';
    }
    if (inputEl.id === 'wallHeight') {
        return 'wall_height';
    }
    const fieldKey = String(inputEl.getAttribute('data-field') || inputEl.name || '').trim().toLowerCase();
    return dimensionExpressionFieldKeys.has(fieldKey) ? fieldKey : '';
}

function isDimensionExpressionInput(inputEl) {
    return Boolean(getDimensionExpressionFieldKey(inputEl));
}

function hasArithmeticOperator(rawValue) {
    return /[+\-*/xXÃƒÆ’Ã¢â‚¬â€ÃƒÆ’Ã‚Â·:]/.test(String(rawValue || ''));
}

function sanitizeDimensionExpression(rawValue) {
    return String(rawValue || '')
        .trim()
        .replace(/\s+/g, '')
        .replace(/,/g, '.')
        .replace(/[xXÃƒÆ’Ã¢â‚¬â€]/g, '*')
        .replace(/[ÃƒÆ’Ã‚Â·:]/g, '/');
}

function formatDimensionExpressionPreview(rawValue) {
    return String(rawValue || '')
        .trim()
        .replace(/[xXÃƒÆ’Ã¢â‚¬â€*]/g, ' x ')
        .replace(/[ÃƒÆ’Ã‚Â·:/]/g, ' / ')
        .replace(/\+/g, ' + ')
        .replace(/-/g, ' - ')
        .replace(/\s+/g, ' ')
        .replace(/\(\s+/g, '(')
        .replace(/\s+\)/g, ')')
        .trim();
}

function tokenizeDimensionExpression(expression) {
    const tokens = [];
    let index = 0;
    while (index < expression.length) {
        const char = expression[index];
        if ('+-*/()'.includes(char)) {
            tokens.push({ type: 'op', value: char });
            index += 1;
            continue;
        }
        if (/[0-9.]/.test(char)) {
            let number = '';
            let dotCount = 0;
            while (index < expression.length && /[0-9.]/.test(expression[index])) {
                if (expression[index] === '.') {
                    dotCount += 1;
                }
                number += expression[index];
                index += 1;
            }
            if (dotCount > 1 || !/^\d+(\.\d+)?$/.test(number)) {
                return null;
            }
            tokens.push({ type: 'number', value: Number(number) });
            continue;
        }
        return null;
    }
    return tokens;
}

function parseDimensionExpression(rawValue) {
    const normalized = sanitizeDimensionExpression(rawValue);
    if (!normalized) {
        return { ok: false, error: 'empty' };
    }
    if (!/^[0-9+\-*/().]+$/.test(normalized)) {
        return { ok: false, error: 'invalid_chars' };
    }
    const tokens = tokenizeDimensionExpression(normalized);
    if (!Array.isArray(tokens)) {
        return { ok: false, error: 'invalid_tokens' };
    }
    let cursor = 0;

    const parseExpression = () => {
        let value = parseTerm();
        while (cursor < tokens.length && ['+', '-'].includes(tokens[cursor].value)) {
            const operator = tokens[cursor].value;
            cursor += 1;
            const right = parseTerm();
            value = operator === '+' ? value + right : value - right;
        }
        return value;
    };

    const parseTerm = () => {
        let value = parseFactor();
        while (cursor < tokens.length && ['*', '/'].includes(tokens[cursor].value)) {
            const operator = tokens[cursor].value;
            cursor += 1;
            const right = parseFactor();
            if (operator === '/') {
                if (right === 0) {
                    throw new Error('division_by_zero');
                }
                value /= right;
            } else {
                value *= right;
            }
        }
        return value;
    };

    const parseFactor = () => {
        const token = tokens[cursor];
        if (!token) {
            throw new Error('unexpected_end');
        }
        if (token.type === 'op' && token.value === '(') {
            cursor += 1;
            const value = parseExpression();
            const closeToken = tokens[cursor];
            if (!closeToken || closeToken.type !== 'op' || closeToken.value !== ')') {
                throw new Error('missing_closing_paren');
            }
            cursor += 1;
            return value;
        }
        if (token.type === 'op' && (token.value === '+' || token.value === '-')) {
            cursor += 1;
            const value = parseFactor();
            return token.value === '-' ? -value : value;
        }
        if (token.type === 'number') {
            cursor += 1;
            return token.value;
        }
        throw new Error('unexpected_token');
    };

    try {
        const result = parseExpression();
        if (cursor !== tokens.length || !Number.isFinite(result)) {
            return { ok: false, error: 'parse_incomplete' };
        }
        return { ok: true, value: result, normalized };
    } catch (error) {
        return { ok: false, error: String(error?.message || 'parse_error') };
    }
}

function formatDimensionNumericValue(value, decimals = 6) {
    const parsed = Number(value);
    if (!Number.isFinite(parsed)) return '';
    return parsed
        .toFixed(decimals)
        .replace(/(\.\d*?[1-9])0+$/, '$1')
        .replace(/\.0+$/, '');
}

function getDimensionExpressionHint(inputEl) {
    if (!(inputEl instanceof HTMLInputElement)) {
        return null;
    }
    const wrap = inputEl.closest('.input-with-unit');
    if (!(wrap instanceof HTMLElement)) {
        return null;
    }
    const hint = wrap.querySelector('[data-expression-hint]');
    return hint instanceof HTMLElement ? hint : null;
}

function showDimensionExpressionHint(inputEl, expressionText) {
    const hintEl = getDimensionExpressionHint(inputEl);
    if (!(hintEl instanceof HTMLElement)) {
        return;
    }
    const cleaned = String(expressionText || '').trim();
    if (!cleaned) {
        hideDimensionExpressionHint(inputEl);
        return;
    }
    hintEl.textContent = `(= ${cleaned})`;
    hintEl.hidden = false;
}

function hideDimensionExpressionHint(inputEl) {
    const hintEl = getDimensionExpressionHint(inputEl);
    if (!(hintEl instanceof HTMLElement)) {
        return;
    }
    hintEl.hidden = true;
    hintEl.textContent = '';
}

function evaluateDimensionInputRawValue(rawValue) {
    const raw = String(rawValue || '').trim();
    if (!raw) {
        return { ok: true, empty: true, value: '', expression: '' };
    }

    if (hasArithmeticOperator(raw)) {
        const parsedExpression = parseDimensionExpression(raw);
        if (!parsedExpression.ok) {
            return { ok: false, error: parsedExpression.error || 'invalid_expression' };
        }
        return {
            ok: true,
            empty: false,
            value: formatDimensionNumericValue(parsedExpression.value),
            expression: formatDimensionExpressionPreview(raw),
        };
    }

    const numeric = Number(raw.replace(',', '.'));
    if (!Number.isFinite(numeric)) {
        return { ok: false, error: 'invalid_number' };
    }

    return {
        ok: true,
        empty: false,
        value: formatDimensionNumericValue(numeric),
        expression: '',
    };
}

function parseDimensionNumericFromInput(inputEl) {
    if (!(inputEl instanceof HTMLInputElement)) {
        return { ok: false };
    }
    const evaluated = evaluateDimensionInputRawValue(inputEl.value);
    if (!evaluated.ok || evaluated.empty) {
        return { ok: false };
    }
    const numeric = Number(String(evaluated.value || '').replace(',', '.'));
    if (!Number.isFinite(numeric)) {
        return { ok: false };
    }
    return { ok: true, value: numeric, text: formatDimensionNumericValue(numeric, 3) };
}

function isDimensionInputCurrentlyVisible(inputEl) {
    if (!(inputEl instanceof HTMLInputElement)) {
        return false;
    }
    if (inputEl.disabled) {
        return false;
    }
    const wrapEl = inputEl.closest('.dimension-item');
    if (!(wrapEl instanceof HTMLElement)) {
        return true;
    }
    return getComputedStyle(wrapEl).display !== 'none';
}

function resolveDimensionAreaSummaryContainer(layoutEl) {
    if (!(layoutEl instanceof HTMLElement)) {
        return null;
    }

    const additionalRow = layoutEl.closest('.additional-work-item[data-additional-work-item="true"]');
    if (additionalRow instanceof HTMLElement) {
        const additionalSummary = additionalRow.querySelector(
            '.additional-worktype-group [data-dimension-area-summary]',
        );
        return additionalSummary instanceof HTMLElement ? additionalSummary : null;
    }

    if (layoutEl.id === 'mainDimensionAreaLayout') {
        const mainInlineSummary = document.getElementById('mainDimensionAreaSummary');
        return mainInlineSummary instanceof HTMLElement ? mainInlineSummary : null;
    }

    const mainWorkTypeForm = layoutEl.closest('.work-type-form');
    if (mainWorkTypeForm instanceof HTMLElement) {
        const mainSummary = mainWorkTypeForm.querySelector('#mainDimensionAreaSummary');
        return mainSummary instanceof HTMLElement ? mainSummary : null;
    }

    const fallback = layoutEl.querySelector('#mainDimensionAreaSummary, [data-dimension-area-summary]');
    return fallback instanceof HTMLElement ? fallback : null;
}

function setDimensionAreaSummary(summaryEl, formulaText = '-', valueText = '-') {
    if (!(summaryEl instanceof HTMLElement)) {
        return;
    }
    const formulaEl = summaryEl.querySelector('#mainDimensionAreaFormula, [data-dimension-area-formula]');
    const valueEl = summaryEl.querySelector('#mainDimensionAreaValue, [data-dimension-area-value]');
    const cleanedValue = String(valueText || '-').trim();
    const displayValue = cleanedValue && cleanedValue !== '-' ? `${cleanedValue} M2` : '-';
    if (formulaEl instanceof HTMLElement) {
        formulaEl.textContent = String(formulaText || '-');
    }
    if (valueEl instanceof HTMLElement) {
        valueEl.textContent = displayValue;
    }
}

function updateDimensionAreaSummary(layoutEl) {
    if (!(layoutEl instanceof HTMLElement)) {
        return;
    }
    const summaryEl = resolveDimensionAreaSummaryContainer(layoutEl);
    if (!(summaryEl instanceof HTMLElement)) {
        return;
    }

    const lengthInput = layoutEl.querySelector('#wallLength, [data-field="wall_length"]');
    const heightInput = layoutEl.querySelector('#wallHeight, [data-field="wall_height"]');

    if (!(lengthInput instanceof HTMLInputElement) || !(heightInput instanceof HTMLInputElement)) {
        setDimensionAreaSummary(summaryEl, '-', '-');
        return;
    }

    if (!isDimensionInputCurrentlyVisible(heightInput)) {
        setDimensionAreaSummary(summaryEl, '-', '-');
        return;
    }

    const lengthValue = parseDimensionNumericFromInput(lengthInput);
    const heightValue = parseDimensionNumericFromInput(heightInput);

    if (!lengthValue.ok || !heightValue.ok) {
        setDimensionAreaSummary(summaryEl, '-', '-');
        return;
    }

    const areaValue = lengthValue.value * heightValue.value;
    const areaText = formatDimensionNumericValue(areaValue, 3);
    setDimensionAreaSummary(summaryEl, `${lengthValue.text} x ${heightValue.text}`, areaText);
}

function refreshDimensionAreaSummaries() {
    updateDimensionAreaSummary(document.getElementById('mainDimensionAreaLayout'));
    document
        .querySelectorAll('.additional-dimension-area-layout[data-dimension-area-layout]')
        .forEach(layoutEl => updateDimensionAreaSummary(layoutEl));
}

function evaluateDimensionExpressionInput(inputEl, options = {}) {
    if (!(inputEl instanceof HTMLInputElement) || !isDimensionExpressionInput(inputEl)) {
        return { ok: true, skipped: true };
    }
    const commitValue = options.commitValue === true;
    const strictMode = options.strictMode === true;
    const evaluated = evaluateDimensionInputRawValue(inputEl.value);

    if (!evaluated.ok) {
        if (strictMode) {
            return { ok: false, error: evaluated.error || 'invalid_expression', inputEl };
        }
        return { ok: true, unresolved: true };
    }

    if (evaluated.empty) {
        inputEl.dataset.dimensionExpressionRaw = '';
        inputEl.dataset.dimensionExpressionValue = '';
        hideDimensionExpressionHint(inputEl);
        return { ok: true, empty: true, value: '' };
    }

    const previousExpression = String(inputEl.dataset.dimensionExpressionRaw || '').trim();
    const previousExpressionValue = String(inputEl.dataset.dimensionExpressionValue || '').trim();

    if (evaluated.expression) {
        inputEl.dataset.dimensionExpressionRaw = evaluated.expression;
        inputEl.dataset.dimensionExpressionValue = evaluated.value;
        showDimensionExpressionHint(inputEl, evaluated.expression);
    } else {
        const keepPreviousHint = previousExpression
            && previousExpressionValue
            && evaluated.value === previousExpressionValue;
        if (keepPreviousHint) {
            inputEl.dataset.dimensionExpressionRaw = previousExpression;
            inputEl.dataset.dimensionExpressionValue = previousExpressionValue;
            showDimensionExpressionHint(inputEl, previousExpression);
        } else {
            inputEl.dataset.dimensionExpressionRaw = '';
            inputEl.dataset.dimensionExpressionValue = '';
            hideDimensionExpressionHint(inputEl);
        }
    }

    if (commitValue) {
        inputEl.value = evaluated.value;
    }

    return { ok: true, value: evaluated.value, expression: evaluated.expression };
}

function bindDimensionExpressionInput(inputEl) {
    if (!(inputEl instanceof HTMLInputElement) || !isDimensionExpressionInput(inputEl)) {
        return;
    }
    if (inputEl.__dimensionExpressionBound) {
        return;
    }
    inputEl.__dimensionExpressionBound = true;

    const commitEvaluation = (strictMode = false) => {
        if (inputEl.__dimensionExpressionSyncing) {
            return { ok: true };
        }
        inputEl.__dimensionExpressionSyncing = true;
        try {
            return evaluateDimensionExpressionInput(inputEl, { commitValue: true, strictMode });
        } finally {
            inputEl.__dimensionExpressionSyncing = false;
        }
    };

    const evaluateWithoutCommit = (strictMode = false) => {
        if (inputEl.__dimensionExpressionSyncing) {
            return { ok: true };
        }
        inputEl.__dimensionExpressionSyncing = true;
        try {
            return evaluateDimensionExpressionInput(inputEl, { commitValue: false, strictMode });
        } finally {
            inputEl.__dimensionExpressionSyncing = false;
        }
    };

    inputEl.addEventListener('input', function() {
        if (inputEl.__dimensionExpressionSyncing) {
            return;
        }
        evaluateWithoutCommit(false);
        refreshDimensionAreaSummaries();
    });

    inputEl.addEventListener('change', function() {
        commitEvaluation(false);
        refreshDimensionAreaSummaries();
    });

    inputEl.addEventListener('blur', function() {
        commitEvaluation(false);
        refreshDimensionAreaSummaries();
    });

    inputEl.addEventListener('keydown', function(event) {
        if (event.key !== 'Enter') {
            return;
        }
        const result = commitEvaluation(true);
        if (!result.ok) {
            event.preventDefault();
        }
        refreshDimensionAreaSummaries();
    });

    evaluateWithoutCommit(false);
    refreshDimensionAreaSummaries();
}

function bindDimensionExpressionInputs(scope = document) {
    if (!(scope instanceof HTMLElement) && scope !== document) {
        return;
    }
    const root = scope === document ? document : scope;
    root.querySelectorAll('input[type="text"], input:not([type])').forEach(inputEl => {
        if (inputEl instanceof HTMLInputElement) {
            bindDimensionExpressionInput(inputEl);
        }
    });
}

function normalizeDimensionExpressionInputsForSubmit(scope = document, options = {}) {
    if (!(scope instanceof HTMLElement) && scope !== document) {
        return { ok: true };
    }
    const root = scope === document ? document : scope;
    const commitValue = options.commitValue === true;
    const candidateInputs = Array.from(root.querySelectorAll('input[type="text"], input:not([type])'))
        .filter(inputEl => inputEl instanceof HTMLInputElement && isDimensionExpressionInput(inputEl));
    const normalizedValues = [];

    for (const inputEl of candidateInputs) {
        if (inputEl.disabled) {
            continue;
        }
        const result = evaluateDimensionExpressionInput(inputEl, { commitValue, strictMode: true });
        if (!result.ok) {
            const fieldName = getDimensionExpressionFieldKey(inputEl) === 'wall_height'
                ? 'Tinggi/Lebar'
                : 'Panjang';
            return {
                ok: false,
                message: `Format ${fieldName} tidak valid. Gunakan angka atau ekspresi seperti 6,2 x 2.`,
                focusEl: inputEl,
            };
        }
        normalizedValues.push({ inputEl, value: String(result.value || '') });
    }
    return { ok: true, values: normalizedValues };
}

function collectDimensionExpressionState() {
    const mainState = {};
    const mainLengthExpression = String(getMainWallLengthInput()?.dataset?.dimensionExpressionRaw || '').trim();
    const mainHeightExpression = String(getMainWallHeightInput()?.dataset?.dimensionExpressionRaw || '').trim();
    if (mainLengthExpression) {
        mainState.wall_length = mainLengthExpression;
    }
    if (mainHeightExpression) {
        mainState.wall_height = mainHeightExpression;
    }

    const additionalState = {};
    const additionalRows = getAllAdditionalWorkRows();
    additionalRows.forEach((rowEl, index) => {
        if (!(rowEl instanceof HTMLElement)) {
            return;
        }
        const rowState = {};
        const lengthInput = rowEl.querySelector('[data-field="wall_length"]');
        const heightInput = rowEl.querySelector('[data-field="wall_height"]');
        const rowLengthExpression = String(lengthInput?.dataset?.dimensionExpressionRaw || '').trim();
        const rowHeightExpression = String(heightInput?.dataset?.dimensionExpressionRaw || '').trim();
        if (rowLengthExpression) {
            rowState.wall_length = rowLengthExpression;
        }
        if (rowHeightExpression) {
            rowState.wall_height = rowHeightExpression;
        }
        if (Object.keys(rowState).length > 0) {
            additionalState[String(index)] = rowState;
        }
    });

    if (!Object.keys(mainState).length && !Object.keys(additionalState).length) {
        return null;
    }

    return {
        main: mainState,
        additional: additionalState,
    };
}

function applyDimensionExpressionState(state) {
    const expressionState = state && typeof state === 'object' ? state : {};
    const mainState = expressionState.main && typeof expressionState.main === 'object'
        ? expressionState.main
        : {};
    const additionalState = expressionState.additional && typeof expressionState.additional === 'object'
        ? expressionState.additional
        : {};

    const applyExpressionToInput = (inputEl, expressionRaw) => {
        if (!(inputEl instanceof HTMLInputElement) || !isDimensionExpressionInput(inputEl)) {
            return;
        }
        const expression = String(expressionRaw || '').trim();
        if (!expression) {
            inputEl.dataset.dimensionExpressionRaw = '';
            inputEl.dataset.dimensionExpressionValue = '';
            hideDimensionExpressionHint(inputEl);
            return;
        }
        inputEl.dataset.dimensionExpressionRaw = expression;
        inputEl.dataset.dimensionExpressionValue = String(inputEl.value || '').trim();
        showDimensionExpressionHint(inputEl, expression);
    };

    applyExpressionToInput(getMainWallLengthInput(), mainState.wall_length || '');
    applyExpressionToInput(getMainWallHeightInput(), mainState.wall_height || '');

    const additionalRows = getAllAdditionalWorkRows();
    additionalRows.forEach((rowEl, index) => {
        if (!(rowEl instanceof HTMLElement)) {
            return;
        }
        const rowState = additionalState[String(index)] && typeof additionalState[String(index)] === 'object'
            ? additionalState[String(index)]
            : {};
        const lengthInput = rowEl.querySelector('[data-field="wall_length"]');
        const heightInput = rowEl.querySelector('[data-field="wall_height"]');
        applyExpressionToInput(lengthInput, rowState.wall_length || '');
        applyExpressionToInput(heightInput, rowState.wall_height || '');
    });
}

function getStoredDimensionExpressionState() {
    try {
        const expressionStateRaw = localStorage.getItem(getCalcExpressionStateKey());
        const expressionStateParsed = expressionStateRaw ? JSON.parse(expressionStateRaw) : null;
        const expressionStateData = expressionStateParsed && typeof expressionStateParsed === 'object'
            ? expressionStateParsed.data
            : null;
        return expressionStateData && typeof expressionStateData === 'object' ? expressionStateData : null;
    } catch (error) {
        return null;
    }
}


        return {
            formatFixedPlain: formatFixedPlain,
            formatThicknessValue: formatThicknessValue,
            getDimensionExpressionFieldKey: getDimensionExpressionFieldKey,
            isDimensionExpressionInput: isDimensionExpressionInput,
            hasArithmeticOperator: hasArithmeticOperator,
            sanitizeDimensionExpression: sanitizeDimensionExpression,
            formatDimensionExpressionPreview: formatDimensionExpressionPreview,
            tokenizeDimensionExpression: tokenizeDimensionExpression,
            parseDimensionExpression: parseDimensionExpression,
            formatDimensionNumericValue: formatDimensionNumericValue,
            getDimensionExpressionHint: getDimensionExpressionHint,
            showDimensionExpressionHint: showDimensionExpressionHint,
            hideDimensionExpressionHint: hideDimensionExpressionHint,
            evaluateDimensionInputRawValue: evaluateDimensionInputRawValue,
            parseDimensionNumericFromInput: parseDimensionNumericFromInput,
            isDimensionInputCurrentlyVisible: isDimensionInputCurrentlyVisible,
            resolveDimensionAreaSummaryContainer: resolveDimensionAreaSummaryContainer,
            setDimensionAreaSummary: setDimensionAreaSummary,
            updateDimensionAreaSummary: updateDimensionAreaSummary,
            refreshDimensionAreaSummaries: refreshDimensionAreaSummaries,
            evaluateDimensionExpressionInput: evaluateDimensionExpressionInput,
            bindDimensionExpressionInput: bindDimensionExpressionInput,
            bindDimensionExpressionInputs: bindDimensionExpressionInputs,
            normalizeDimensionExpressionInputsForSubmit: normalizeDimensionExpressionInputsForSubmit,
            collectDimensionExpressionState: collectDimensionExpressionState,
            applyDimensionExpressionState: applyDimensionExpressionState,
            getStoredDimensionExpressionState: getStoredDimensionExpressionState,
        };
    };
})();