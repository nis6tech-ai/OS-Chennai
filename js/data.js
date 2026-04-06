/**
 * OS Chennai Data Bridge - PHP Version
 * Fetches live data from MySQL instead of Firebase/Hardcoded arrays.
 */

// ─── Global Asset Resolver ────────────────────────────────────────────────────
// All image paths in the DB are relative paths stored on nutpa.in.
// This function ensures they are always correctly prefixed for OS Chennai.
window.resolveAsset = function(url) {
    if (!url) return 'assets/logo.png';
    if (url.startsWith('http') || url.startsWith('data:')) return url;
    return 'https://www.nutpa.in/' + url.replace(/^\//, '');
};

// Global WhatsApp Formatter - Ensures 91 prefix for India
window.formatWhatsappNumber = function(num) {
    if (!num) return '919940428882';
    let clean = String(num).replace(/[^0-9]/g, '');
    // If it's a 10-digit number, assume it's India and prepend 91
    if (clean.length === 10) return '91' + clean;
    return clean;
};
// ─────────────────────────────────────────────────────────────────────────────

window.liveData = {
    settings: {},
    categories: [],
    products: []
};

async function loadLiveSiteData() {
    try {
        const response = await fetch('/api/sync.php?action=get_data&project=os-chennai');
        const data = await response.json();

        if (data.error) {
            console.error("Database Error:", data.error);
            return;
        }

        window.liveData = data;

        window.products = data.products || [];
        window.categories = data.categories || [];
        window.settings = data.settings || {};
        window.blogs = data.blogs || [];

        // Custom event so components can start rendering
        window.dispatchEvent(new Event('dataLoaded'));
        console.log("PHP Data Load: Success");
        // Wait for all specific page dataLoaded listeners to finish setting innerHTML
        setTimeout(() => {
            applyDynamicSettings(data.settings);
        }, 500);

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
            // PROTECTION: If this node already contains the target replacement string, skip it to avoid doubling.
            if (node.nodeValue.includes(replaceVal)) return;

            if (node.nodeValue.includes(search)) {
                node.nodeValue = node.nodeValue.split(search).join(replaceStr);
            }
        } else if (node.nodeType === 1 && node.nodeName !== 'SCRIPT' && node.nodeName !== 'STYLE') {
            node.childNodes.forEach(child => updateTextNodes(child, search, replaceStr));
        }
    }

    // 0. Update Site Branding (Name & Tagline)
    const siteName = settings.siteName || settings.site_name;
    const siteTagline = settings.siteTagline || settings.site_tagline;

    if (siteName) {
        // Replace hardcoded strings
        updateTextNodes(document.body, 'Nutpa Electronics', siteName);
        updateTextNodes(document.body, 'Nutpa', siteName);
        updateTextNodes(document.body, 'OS Chennai', siteName);
        document.title = siteName + (siteTagline ? ' | ' + siteTagline : '');
    }

    if (siteTagline) {
        updateTextNodes(document.body, 'Enterprise Software & Hardware Solutions', siteTagline);
        updateTextNodes(document.body, 'Authorized Enterprise Partner for Tamil Nadu', siteTagline);
    }

    const defaultPhone = '9940428882';
    const defaultEmail = 'sales@nutpa.com';
    const defaultWa = '919940428882';
    const defaultInsta = 'https://instagram.com';
    const defaultLinkedin = 'https://linkedin.com';

    const addressDefault1 = 'No 1/2, Janakiraman St, West Jafferkhanpet, Chennai — 600083, Tamil Nadu';
    const addressDefault2 = 'West Jafferkhanpet, Chennai';
    const addressDefault3 = 'No 1/2, Janakiraman St, West Jafferkhanpet';
    const addressDefault4 = 'No 1/2, Janakiraman st, West Jafferkhanpet';

    // 1. Update Phone Numbers
    const phone = settings.contactPhone || settings.contact_phone;
    if (phone) {
        document.querySelectorAll('a[href^="tel:"]').forEach(a => a.href = 'tel:' + phone.replace(/[^0-9+]/g, ''));
        updateTextNodes(document.body, defaultPhone, phone);
        updateTextNodes(document.body, '+91 9940428882', '+91 ' + phone);
        updateTextNodes(document.body, '+919940428882', '+91 ' + phone);
        updateTextNodes(document.body, '99404 28882', phone);
    }

    // 2. Update WhatsApp Numbers
    const wa = settings.whatsappNumber || settings.whatsapp_number;
    const cleanWaArr = window.formatWhatsappNumber(wa);

    if (wa) {
        document.querySelectorAll('a[href^="https://wa.me/"]').forEach(a => {
            try {
                const url = new URL(a.href);
                const text = url.searchParams.get('text');
                a.href = `https://wa.me/${cleanWaArr}${text ? '?text=' + encodeURIComponent(text) : ''}`;
            } catch (e) {
                a.href = `https://wa.me/${cleanWaArr}`;
            }
        });
        updateTextNodes(document.body, defaultWa, cleanWaArr);
    }

    // 3. Update Email
    const email = settings.contactEmail || settings.contact_email;
    if (email) {
        document.querySelectorAll('a[href^="mailto:"]').forEach(a => a.href = 'mailto:' + email);
        updateTextNodes(document.body, defaultEmail, email);
        updateTextNodes(document.body, 'sales@oschennai.in', email);
        updateTextNodes(document.body, 'solutions@oschennai.in', email);
        updateTextNodes(document.body, 'sales@oschennai.com', email);
        updateTextNodes(document.body, 'info@oschennai.in', email);
    }

    // 4. Update Address
    const addressValue = settings.contactAddress || settings.contact_address;
    if (addressValue) {
        /**
         * SPECIAL ADDRESS HANDLER:
         * To prevent "doubling" where partial address pieces are replaced with the FULL address,
         * we prioritize replacing larger blocks first and avoid replacing short pieces that 
         * are already contained within the intended replacement.
         */
        const addressDefaults = [
            'No 1/2, Janakiraman St, West Jafferkhanpet, Chennai — 600083, Tamil Nadu',
            'No 1/2, Janakiraman st, West Jafferkhanpet, Chennai — 600083, Tamil Nadu',
            'No 1/2, Janakiraman st, 83rd St, Muthurangam Block, West Jafferkhanpet, Chennai — 600083, Tamil Nadu',
            'No 1/2, Janakiraman st, 83rd St, Muthurangam Block, West Jafferkhanpet, Chennai,Tamil Nadu 600083',
            'No 1/2, Janakiraman St, West Jafferkhanpet',
            'No 1/2, Janakiraman st, West Jafferkhanpet'
            // NOTE: Removed 'West Jafferkhanpet, Chennai' as it's too short and might cause partial doubling
        ];
        
        function updateAddressNodes(node) {
            if (node.nodeType === 3) {
                // PROTECTION: If this node already contains the target addressValue, skip it to avoid doubling.
                if (node.nodeValue.includes(addressValue)) return;

                for (const search of addressDefaults) {
                    if (node.nodeValue.includes(search)) {
                        node.nodeValue = node.nodeValue.split(search).join(addressValue);
                        return; // Important: Stop after first replacement for this node
                    }
                }

                // Final fallback for very specific multi-line pieces
                const pieces = ['Muthurangam Block', '83rd St'];
                pieces.forEach(p => {
                    if (node.nodeValue.includes(p)) {
                        node.nodeValue = node.nodeValue.replace(p, ''); // Remove the piece if it's redundant
                    }
                });

            } else if (node.nodeType === 1 && node.nodeName !== 'SCRIPT' && node.nodeName !== 'STYLE') {
                node.childNodes.forEach(child => updateAddressNodes(child));
            }
        }
        updateAddressNodes(document.body);
    }

    // 5. Update Social Links
    const social = settings.socialLinks || settings.social_links;
    if (social) {
        let s = social;
        if (typeof social === 'string') {
            try { s = JSON.parse(social); } catch (e) { s = {}; }
        }

        if (s.instagram) {
            document.querySelectorAll(`a[href^="${defaultInsta}"]`).forEach(a => a.href = s.instagram);
        }
        if (s.linkedin) {
            document.querySelectorAll(`a[href^="${defaultLinkedin}"]`).forEach(a => a.href = s.linkedin);
        }
    }

    // 6. Update Hero Image
    const hero = settings.heroImage || settings.hero_image;
    if (hero) {
        const resolvedHero = window.resolveAsset(hero);
        const heroSection = document.querySelector('.hero-section');
        if (heroSection) heroSection.style.backgroundImage = `url(${resolvedHero})`;
        const heroImg = document.querySelector('#heroImage, .hero-img');
        if (heroImg) heroImg.src = resolvedHero;
    }

    // 7. Update Logo
    const logo = settings.siteLogo || settings.site_logo;
    if (logo) {
        const resolvedLogo = window.resolveAsset(logo);
        document.querySelectorAll('#navLogo, #footerLogo, .logo-img').forEach(img => {
            img.src = resolvedLogo;
        });
    }

    // 8. Update SEO Meta & Title
    const sTitle = settings.siteTitle || settings.site_title;
    const sKeywords = settings.siteKeywords || settings.site_keywords;
    const sDesc = settings.siteDescription || settings.site_description;
    const sFavicon = settings.siteFavicon || settings.site_favicon;

    if (sTitle) {
        document.title = sTitle;
    } else if (siteName) {
        document.title = siteName + (siteTagline ? ' | ' + siteTagline : '');
    }

    if (sKeywords) {
        let metaKeywords = document.querySelector('meta[name="keywords"]');
        if (!metaKeywords) {
            metaKeywords = document.createElement('meta');
            metaKeywords.name = "keywords";
            document.head.appendChild(metaKeywords);
        }
        metaKeywords.content = sKeywords;
    }

    if (sDesc) {
        let metaDesc = document.querySelector('meta[name="description"]');
        if (!metaDesc) {
            metaDesc = document.createElement('meta');
            metaDesc.name = "description";
            document.head.appendChild(metaDesc);
        }
        metaDesc.content = sDesc;
    }

    if (sFavicon) {
        const resolvedFav = window.resolveAsset(sFavicon);
        let linkFav = document.querySelector('link[rel="icon"], link[rel="shortcut icon"]');
        if (!linkFav) {
            linkFav = document.createElement('link');
            linkFav.rel = "icon";
            document.head.appendChild(linkFav);
        }
        linkFav.href = resolvedFav;
    }
}

// Start loading immediately
loadLiveSiteData();
