            }
            return data;
        }

        function saveCalculationSession(payload) {
            if (!form) return;
            const sessionPayload = payload || serializeCalculationSession(form);
            if (!sessionPayload) return;
            try {
                localStorage.setItem(calcSessionKey, JSON.stringify({
                    updatedAt: Date.now(),
                    data: sessionPayload,
                    autoSubmit: false,
                }));
            } catch (error) {
                console.warn('Failed to save calculation session', error);
            }
        }

        function normalizeSessionPayload(value) {
            if (Array.isArray(value)) {
                const normalizedList = value.map(normalizeSessionPayload);
                normalizedList.sort();
                return normalizedList;
            }
            if (value && typeof value === 'object') {
                const normalized = {};
                Object.keys(value).sort().forEach(key => {
                    normalized[key] = normalizeSessionPayload(value[key]);
                });
                return normalized;
            }
            return value;
        }

        function buildSessionFingerprint(payload) {
            return JSON.stringify(normalizeSessionPayload(payload));
        }

        function isSameAsLastSession(currentPayload) {
            const raw = localStorage.getItem(calcSessionKey);
            if (!raw) return false;
            try {
                const parsed = JSON.parse(raw);
                if (!parsed || typeof parsed !== 'object' || !parsed.data) return false;
                return buildSessionFingerprint(parsed.data) === buildSessionFingerprint(currentPayload);
            } catch (error) {
                return false;
            }
        }

        function buildPreviewShortcutComparablePayload(formEl) {
            if (!(formEl instanceof HTMLFormElement)) {
                return null;
            }

            const payload = {};
            const formData = new FormData(formEl);

            formData.forEach((value, key) => {
                if (key === '_token' || key === 'confirm_save') {
                    return;
                }

                const normalizedKey = key.endsWith('[]') ? key.slice(0, -2) : key;
                const normalizedValue = typeof value === 'string' ? value : String(value ?? '');

                if (key.endsWith('[]')) {
                    if (!Array.isArray(payload[normalizedKey])) {
                        payload[normalizedKey] = [];
                    }
                    payload[normalizedKey].push(normalizedValue);
                    return;
                }

                // Match Laravel request behavior for duplicate scalar names:
                // later values overwrite earlier ones (e.g. hidden fallback + checkbox checked).
                payload[normalizedKey] = normalizedValue;
            });

            if (
                Object.prototype.hasOwnProperty.call(payload, 'mortar_thickness') &&
                mortarThicknessInput instanceof HTMLInputElement &&
                mortarThicknessInput.dataset.unit === 'mm'
            ) {
                const currentValue = parseFloat(String(payload.mortar_thickness || '').replace(',', '.'));
                if (!isNaN(currentValue)) {
                    payload.mortar_thickness = formatThicknessValue(currentValue / 10);
                }
            }

            // Exclude client-only session helper state that is not part of the server request payload.
            delete payload.customize_panel_state;

            ['work_items_payload', 'material_customize_filters_payload'].forEach(jsonKey => {
                const raw = payload[jsonKey];
                if (typeof raw !== 'string' || !raw.trim()) {
                    return;
                }
                try {
                    payload[jsonKey] = JSON.parse(raw);
                } catch (error) {
                    // Keep original string if not valid JSON
                }
            });

            return payload;
        }

        function getFastPreviewNavigationUrl(currentPayload) {
            if (!currentPayload) {
                return null;
            }

            const toFastPreviewComparablePayload = payload => {
                if (!payload || typeof payload !== 'object') {
                    return payload;
                }

                let clonedPayload = null;
                try {
                    clonedPayload = JSON.parse(JSON.stringify(payload));
                } catch (error) {
                    clonedPayload = { ...payload };
                }

                if (clonedPayload && typeof clonedPayload === 'object') {
                    delete clonedPayload.customize_panel_state;
                    ['work_items_payload', 'material_customize_filters_payload'].forEach(jsonKey => {
                        const raw = clonedPayload[jsonKey];
                        if (typeof raw !== 'string' || !raw.trim()) {
                            return;
                        }
                        try {
                            clonedPayload[jsonKey] = JSON.parse(raw);
                        } catch (error) {
                            // keep raw if malformed
                        }
                    });
                }

                return clonedPayload;
            };

            let parsed = null;
            try {
                parsed = JSON.parse(localStorage.getItem('materialCalculationPreview') || 'null');
            } catch (error) {
                return null;
            }

            if (!parsed || typeof parsed !== 'object') {
                return null;
            }

            const previewData = parsed.data;
            if (!previewData || typeof previewData !== 'object') {
                return null;
            }
            const comparablePreviewData = toFastPreviewComparablePayload(previewData);
            const comparableCurrentPayload = toFastPreviewComparablePayload(currentPayload);
            if (buildSessionFingerprint(comparablePreviewData) !== buildSessionFingerprint(comparableCurrentPayload)) {
                return null;
            }

            const previewUrl = String(parsed.url || '').trim();
            if (!previewUrl) {
                return null;
            }

            const updatedAt = Number(parsed.updatedAt || 0);
            const maxAgeMs = 1000 * 60 * 60 * 6; // 6 hours (match server preview cache TTL)
            if (Number.isFinite(updatedAt) && updatedAt > 0 && Date.now() - updatedAt > maxAgeMs) {
                return null;
            }

