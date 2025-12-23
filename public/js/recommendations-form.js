function initRecommendationsForm(container, rawData) {
    const list = container.querySelector('#recommendationList');
    const template = container.querySelector('#rowTemplate').firstElementChild;
    const btnAdd = container.querySelector('#btnAddRow');
    
    if (!list || !template || !btnAdd) return;

    // Helper to get unique count for index
    const getNextIndex = () => container.querySelectorAll('.recommendation-card').length;

    // Initialize existing rows
    container.querySelectorAll('.recommendation-card').forEach(row => {
        initRow(row);
    });

    // Removed auto-add row when list is empty - user can manually add via button
    // if (list.children.length === 0) {
    //     addNewRow();
    // }

    btnAdd.addEventListener('click', addNewRow);

    function addNewRow() {
        const index = getNextIndex();
        const clone = template.cloneNode(true);
        
        // Replace placeholders
        clone.innerHTML = clone.innerHTML.replace(/INDEX_PLACEHOLDER/g, index);
        
        // Update name attributes manually to ensure index is correct
        clone.querySelectorAll('[name*="INDEX_PLACEHOLDER"]').forEach(el => {
            el.name = el.name.replace('INDEX_PLACEHOLDER', index);
        });

        list.appendChild(clone);
        initRow(clone);
    }

    // Initialize logic for a single row
    function initRow(row) {
        // -- Remove Handler --
        const btnRemove = row.querySelector('.btn-remove');
        if (btnRemove) {
            btnRemove.addEventListener('click', () => {
                row.remove();
            });
        }

        // -- BRICK LOGIC --
        const brickBrandSelect = row.querySelector('.brick-brand-select');
        const brickDimSelect = row.querySelector('.brick-dim-select');
        
        if (brickBrandSelect && brickDimSelect) {
            const uniqueBrickBrands = [...new Set(rawData.bricks.map(b => b.brand))];
            populateSelect(brickBrandSelect, uniqueBrickBrands, brickBrandSelect.dataset.selected);

            brickBrandSelect.addEventListener('change', () => {
                const brand = brickBrandSelect.value;
                const filtered = rawData.bricks.filter(b => b.brand === brand);
                
                brickDimSelect.innerHTML = '<option value="">-- Pilih Dimensi --</option>';
                filtered.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b.id;
                    opt.textContent = `${b.type} (${b.dimension_length}x${b.dimension_width}x${b.dimension_height}) - Rp ${Number(b.price_per_piece).toLocaleString('id-ID')}`;
                    if (b.id == brickDimSelect.dataset.selected) opt.selected = true;
                    brickDimSelect.appendChild(opt);
                });
            });
            if (brickBrandSelect.value) brickBrandSelect.dispatchEvent(new Event('change'));
        }

        // -- CEMENT LOGIC --
        const cementTypeSelect = row.querySelector('.cement-type-select');
        const cementBrandSelect = row.querySelector('.cement-brand-select');

        if (cementTypeSelect && cementBrandSelect) {
            const uniqueCementTypes = [...new Set(rawData.cements.map(c => c.cement_name))];
            populateSelect(cementTypeSelect, uniqueCementTypes, cementTypeSelect.dataset.selected);

            cementTypeSelect.addEventListener('change', () => {
                const type = cementTypeSelect.value;
                const filtered = rawData.cements.filter(c => c.cement_name === type);
                
                cementBrandSelect.innerHTML = '<option value="">-- Pilih Merk --</option>';
                filtered.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = `${c.brand} (${c.package_weight_net} kg) - Rp ${Number(c.package_price).toLocaleString('id-ID')}`;
                    if (c.id == cementBrandSelect.dataset.selected) opt.selected = true;
                    cementBrandSelect.appendChild(opt);
                });
            });
            if (cementTypeSelect.value) cementTypeSelect.dispatchEvent(new Event('change'));
        }

        // -- SAND LOGIC --
        const sandTypeSelect = row.querySelector('.sand-type-select');
        const sandBrandSelect = row.querySelector('.sand-brand-select');
        const sandPkgSelect = row.querySelector('.sand-pkg-select');

        if (sandTypeSelect && sandBrandSelect && sandPkgSelect) {
            const uniqueSandTypes = [...new Set(rawData.sands.map(s => s.sand_name))];
            populateSelect(sandTypeSelect, uniqueSandTypes, sandTypeSelect.dataset.selected);

            sandTypeSelect.addEventListener('change', () => {
                const type = sandTypeSelect.value;
                const filtered = rawData.sands.filter(s => s.sand_name === type);
                const brands = [...new Set(filtered.map(s => s.brand))];
                
                sandBrandSelect.innerHTML = '<option value="">-- Pilih Merk --</option>';
                brands.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b;
                    opt.textContent = b;
                    if (b == sandBrandSelect.dataset.selected) opt.selected = true;
                    sandBrandSelect.appendChild(opt);
                });
                sandPkgSelect.innerHTML = '<option value="">-- Pilih Kemasan --</option>';
            });

            sandBrandSelect.addEventListener('change', () => {
                const type = sandTypeSelect.value;
                const brand = sandBrandSelect.value;
                const filtered = rawData.sands.filter(s => s.sand_name === type && s.brand === brand);

                sandPkgSelect.innerHTML = '<option value="">-- Pilih Kemasan --</option>';
                filtered.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    const vol = s.package_volume > 0 ? `${s.package_volume} m³` : `${s.package_weight_net} kg`;
                    opt.textContent = `${vol} - Rp ${Number(s.package_price).toLocaleString('id-ID')}`;
                    if (s.id == sandPkgSelect.dataset.selected) opt.selected = true;
                    sandPkgSelect.appendChild(opt);
                });
            });

            if (sandTypeSelect.value) {
                sandTypeSelect.dispatchEvent(new Event('change'));
                if (sandBrandSelect.dataset.selected) {
                     sandBrandSelect.dispatchEvent(new Event('change'));
                }
            }
        }
    }

    function populateSelect(select, values, selectedValue) {
        const first = select.firstElementChild;
        select.innerHTML = '';
        select.appendChild(first);
        values.forEach(v => {
            const opt = document.createElement('option');
            opt.value = v;
            opt.textContent = v;
            if (v == selectedValue) opt.selected = true;
            select.appendChild(opt);
        });
    }
}
