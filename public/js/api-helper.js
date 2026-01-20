/**
 * API Helper - Reusable utility for REST API calls
 * Usage: const api = new ApiHelper();
 *        const data = await api.get('/bricks');
 */

class ApiHelper {
    constructor(baseUrl = '/api/v1') {
        this.baseUrl = baseUrl;
    }

    /**
     * GET request
     * @param {string} endpoint - API endpoint (e.g., '/bricks')
     * @param {object} params - Query parameters
     * @returns {Promise<object>} Response data
     */
    async get(endpoint, params = {}) {
        try {
            const url = new URL(this.baseUrl + endpoint, window.location.origin);

            // Add query parameters
            Object.keys(params).forEach(key => {
                if (params[key] !== null && params[key] !== undefined && params[key] !== '') {
                    url.searchParams.append(key, params[key]);
                }
            });

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                }
            });

            return await response.json();
        } catch (error) {
            console.error('API GET Error:', error);
            return { success: false, message: error.message };
        }
    }

    /**
     * POST request
     * @param {string} endpoint - API endpoint
     * @param {object} data - Request body data
     * @returns {Promise<object>} Response data
     */
    async post(endpoint, data) {
        try {
            const response = await fetch(this.baseUrl + endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken()
                },
                body: JSON.stringify(data)
            });

            return await response.json();
        } catch (error) {
            console.error('API POST Error:', error);
            return { success: false, message: error.message };
        }
    }

    /**
     * PUT request (Update)
     * @param {string} endpoint - API endpoint
     * @param {object} data - Request body data
     * @returns {Promise<object>} Response data
     */
    async put(endpoint, data) {
        try {
            const response = await fetch(this.baseUrl + endpoint, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken()
                },
                body: JSON.stringify(data)
            });

            return await response.json();
        } catch (error) {
            console.error('API PUT Error:', error);
            return { success: false, message: error.message };
        }
    }

    /**
     * DELETE request
     * @param {string} endpoint - API endpoint
     * @returns {Promise<object>} Response data
     */
    async delete(endpoint) {
        try {
            const response = await fetch(this.baseUrl + endpoint, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken()
                }
            });

            return await response.json();
        } catch (error) {
            console.error('API DELETE Error:', error);
            return { success: false, message: error.message };
        }
    }

    /**
     * Get CSRF token from meta tag
     * @returns {string} CSRF token
     */
    getCsrfToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        if (!token) {
            console.warn('CSRF token not found. Make sure <meta name="csrf-token"> exists in layout.');
            return '';
        }
        return token.getAttribute('content');
    }

    /**
     * Show loading indicator
     * @param {string} elementId - ID of element to show loading
     */
    showLoading(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            element.innerHTML = '<tr><td colspan="10" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';
        }
    }

    /**
     * Show error message
     * @param {string} elementId - ID of element to show error
     * @param {string} message - Error message
     */
    showError(elementId, message) {
        const element = document.getElementById(elementId);
        if (element) {
            element.innerHTML = `<tr><td colspan="10" class="text-center text-danger"><i class="fas fa-exclamation-triangle"></i> ${message}</td></tr>`;
        }
    }

    /**
     * Format number to Indonesian rupiah number (without prefix)
     * @param {number} number - Number to format
     * @returns {string} Formatted rupiah number
     */
    formatRupiah(number) {
        const value = Number(number);
        if (!isFinite(value)) {
            return '0';
        }
        const truncated = value >= 0 ? Math.floor(value) : Math.ceil(value);
        const sign = truncated < 0 ? '-' : '';
        const abs = Math.abs(truncated);
        const withThousands = abs.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return `${sign}${withThousands}`;
    }

    /**
     * Format date to Indonesian format
     * @param {string} dateString - Date string
     * @returns {string} Formatted date
     */
    formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
}

// Create global instance
const api = new ApiHelper();
