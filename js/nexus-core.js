/**
 * Nexus Core PHP Transition
 * Redirects all data operations to your Hostinger server.
 */

const NexusCore = {
    apiUrl: '/api/sync.php',

    init: async function (clientId) {
        try {
            const resp = await fetch(`${this.apiUrl}?action=get_data&project=os-chennai`);
            const data = await resp.json();
            return data;
        } catch (e) {
            console.error("Initialization Failed:", e);
            return null;
        }
    },

    saveProduct: async function (product) {
        try {
            const resp = await fetch(`${this.apiUrl}?action=save_product`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(product)
            });
            return await resp.json();
        } catch (e) {
            console.error("Save Failed:", e);
            return { error: e.message };
        }
    },

    deleteProduct: async function (id) {
        await fetch(`${this.apiUrl}?action=delete_product&id=${id}`);
        return { success: true };
    },

    save: async function (clientId, data) {
        try {
            const resp = await fetch(`${this.apiUrl}?action=save_settings&project=os-chennai`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            return await resp.json();
        } catch (e) {
            console.error("Settings Save Failed:", e);
            return { error: e.message };
        }
    },

    uploadFile: async function (file) {
        const formData = new FormData();
        formData.append('file', file);

        try {
            const resp = await fetch(`${this.apiUrl}?action=upload`, {
                method: 'POST',
                body: formData
            });
            return await resp.json();
        } catch (e) {
            return { error: "Upload connection failed" };
        }
    },

    getClients: function () {
        return [
            { id: 'OS CHENNAI', name: 'OS Chennai', domain: 'OS CHENNAI.com', status: 'active' }
        ];
    }
};

window.NexusCore = NexusCore;
