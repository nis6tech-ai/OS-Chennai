/**
 * OS Chennai Data Bridge - PHP Version
 * Fetches live data from MySQL instead of Firebase/Hardcoded arrays.
 */

window.liveData = {
    settings: {},
    categories: [],
    products: []
};

async function loadLiveSiteData() {
    try {
        const response = await fetch('/api/sync.php?action=get_data');
        const data = await response.json();

        if (data.error) {
            console.error("Database Error:", data.error);
            return;
        }

        window.liveData = data;

        // Override identity for this clone site
        if (data && data.settings) {
            data.settings.siteName = "OS Chennai";
            data.settings.siteTagline = "Certified IT Hardware Dealer";
        }

        // Update global shortcuts
        window.products = data.products || [];
        window.categories = data.categories || [];
        window.settings = data.settings || {};

        // Custom event so components can start rendering
        window.dispatchEvent(new Event('dataLoaded'));
        console.log("PHP Data Load: Success");
        setTimeout(() => {
            applyDynamicSettings(data.settings);
            if (window.lucide) lucide.createIcons();
        }, 100);

    } catch (e) {
        console.error("PHP Data Load: Failed", e);
    }
}

function applyDynamicSettings(settings) {
    if (!settings) return;

    function updateTextNodes(node, search, replaceStr) {
        if (replaceStr === undefined || replaceStr === null) return;
        const replaceVal = String(replaceStr);
        if (replaceVal.trim() === '') return;
        if (node.nodeType === 3) {
            if (node.nodeValue.includes(search)) {
                node.nodeValue = node.nodeValue.split(search).join(replaceStr);
            }
        } else if (node.nodeType === 1 && node.nodeName !== 'SCRIPT' && node.nodeName !== 'STYLE') {
            node.childNodes.forEach(child => updateTextNodes(child, search, replaceStr));
        }
    }

    const defaultPhone = '9940428882';
    const defaultEmail = 'sales@oschennai.com';
    const defaultWa = '919940428882';

    // Address defaults
    const addressDefault1 = 'No 1/2, Janakiraman St, West Jafferkhanpet, Chennai — 600083, Tamil Nadu';
    const addressDefault2 = 'West Jafferkhanpet, Chennai';
    const addressDefault3 = 'No 1/2, Janakiraman St, West Jafferkhanpet';

    // 1. Update Phone Numbers
    const phone = settings.contactPhone || settings.contact_phone;
    if (phone) {
        document.querySelectorAll('a[href^="tel:"]').forEach(a => a.href = 'tel:' + phone.replace(/[^0-9+]/g, ''));
        updateTextNodes(document.body, defaultPhone, phone);
        updateTextNodes(document.body, '+91 9940428882', '+91 ' + phone);
    }

    // 2. Update WhatsApp Numbers
    const wa = settings.whatsappNumber || settings.whatsapp_number;
    if (wa) {
        const cleanWa = wa.replace(/[^0-9]/g, '');
        document.querySelectorAll('a[href^="https://wa.me/"]').forEach(a => {
            a.href = a.href.replace(/wa\.me\/\d+/, 'wa.me/' + cleanWa);
        });
        updateTextNodes(document.body, defaultWa, cleanWa);
    }

    // 3. Update Email
    const email = settings.contactEmail || settings.contact_email;
    if (email) {
        document.querySelectorAll('a[href^="mailto:"]').forEach(a => a.href = 'mailto:' + email);
        updateTextNodes(document.body, defaultEmail, email);
    }

    // 4. Update Address
    const address = settings.contactAddress || settings.contact_address;
    if (address) {
        updateTextNodes(document.body, addressDefault1, address);
        updateTextNodes(document.body, addressDefault2, address);
        updateTextNodes(document.body, addressDefault3, address);
    }
}

// Start loading immediately
loadLiveSiteData();
