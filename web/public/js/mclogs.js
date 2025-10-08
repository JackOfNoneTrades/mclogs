const titles = window.titles; //["Paste", "Share", "Analyse"];
let currentTitle = 0;
let speed = 30;
let pause = 3000;
const pasteArea = document.getElementById('paste');
const titleElement = document.querySelector('.title-verb');
const pasteSaveButtons = document.querySelectorAll('.paste-save');
const pasteHeader = document.querySelector('.paste-header');
const pasteFooter = document.querySelector('.paste-footer');

setTimeout(nextTitle, pause);
function nextTitle() {
    currentTitle++;
    if(typeof(titles[currentTitle]) === "undefined") {
        currentTitle = 0;
    }

    const title = titleElement.innerHTML;
    for (let i = 0; i < title.length - 1; i++) {
        setTimeout(function() {
            titleElement.innerHTML = titleElement.innerHTML.substring(0, titleElement.innerHTML.length - 1);
        }, i * speed);
    }

    const newTitle = titles[currentTitle];
    for (let i = 1; i <= newTitle.length; i++) {
        setTimeout(function(){
            titleElement.innerHTML = newTitle.substring(0, titleElement.innerHTML.length + 1);
        }, title.length * speed + i * speed);
    }

    setTimeout(nextTitle, title.length * speed + newTitle.length * speed + pause);
}

pasteArea.focus();

pasteSaveButtons.forEach(button => button.addEventListener('click', sendLog));

document.addEventListener('keydown', event => {
    if ((event.key.toLowerCase() === 's' && event.ctrlKey) || event.key.codePointAt(0) === 19) {
        void sendLog();
        event.preventDefault();
        return false;
    }

    return true;
})

/**
 * Privacy Filters - Applied client-side before upload
 */

// IP Address Filter
function filterIpAddresses(data) {
    // IPv6 pattern
    const ipv6Pattern = /(?<=^|\W)((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:)))(%.+)?(?=$|\W)/g;
    
    // IPv4 pattern (exclude "version: " and "version ")
    const ipv4Pattern = /(?<!version: )(?<!version )(?<!([0-9]|-|\w))([0-9]{1,3}\.){3}[0-9]{1,3}(?![0-9])/gi;
    
    // IPv6 whitelist (localhost)
    const ipv6Whitelist = [/^[0:]+1?$/];
    
    // IPv4 whitelist (localhost, 0.0.0.0, DNS servers)
    const ipv4Whitelist = [
        /^127\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/,
        /^0\.0\.0\.0$/,
        /^1\.[01]\.[01]\.1$/,
        /^8\.8\.[84]\.[84]$/
    ];
    
    // Filter IPv6
    data = data.replace(ipv6Pattern, (match) => {
        for (const whitelist of ipv6Whitelist) {
            if (whitelist.test(match)) {
                return match;
            }
        }
        return '****:****:****:****:****:****:****:****';
    });
    
    // Filter IPv4
    data = data.replace(ipv4Pattern, (match) => {
        for (const whitelist of ipv4Whitelist) {
            if (whitelist.test(match)) {
                return match;
            }
        }
        return '**.**.**.**';
    });
    
    return data;
}

// Username Filter
function filterUsernames(data) {
    const patterns = [
        // Windows with backslashes
        { pattern: /C:\\Users\\([^\\]+)\\/g, replacement: 'C:\\Users\\********\\' },
        // Windows with double backslashes
        { pattern: /C:\\\\Users\\\\([^\\]+)\\\\/g, replacement: 'C:\\\\Users\\\\********\\\\' },
        // Windows with forward slashes
        { pattern: /C:\/Users\/([^\/]+)\//g, replacement: 'C:/Users/********/' },
        // Linux
        { pattern: /(?<!\w)\/home\/[^\/]+\//g, replacement: '/home/********/' },
        // macOS
        { pattern: /(?<!\w)\/Users\/[^\/]+\//g, replacement: '/Users/********/' },
        // Environment variable
        { pattern: /^USERNAME=.+$/gm, replacement: 'USERNAME=********' }
    ];
    
    for (const {pattern, replacement} of patterns) {
        data = data.replace(pattern, replacement);
    }
    
    return data;
}

// Access Token Filter
function filterAccessTokens(data) {
    const patterns = [
        // Session ID
        { pattern: /\(Session ID is token:[^:]+:[^)]+\)/g, replacement: '(Session ID is token:****************:****************)' },
        // Access token argument
        { pattern: /--accessToken [^ ]+/g, replacement: '--accessToken ****************:****************' }
    ];
    
    for (const {pattern, replacement} of patterns) {
        data = data.replace(pattern, replacement);
    }
    
    return data;
}

// Apply all privacy filters
function applyPrivacyFilters(data) {
    data = filterIpAddresses(data);
    data = filterUsernames(data);
    data = filterAccessTokens(data);
    return data;
}

/**
 * Encrypt log content using AES-GCM with PBKDF2 key derivation
 * @param {string} text - The text to encrypt
 * @param {string} password - The password to use
 * @returns {Promise<string>} Base64 encoded encrypted data
 */
async function encryptLog(text, password) {
    const encoder = new TextEncoder();
    const data = encoder.encode(text);
    
    // Generate salt
    const salt = crypto.getRandomValues(new Uint8Array(16));
    
    // Derive key from password using PBKDF2
    const keyMaterial = await crypto.subtle.importKey(
        'raw',
        encoder.encode(password),
        'PBKDF2',
        false,
        ['deriveBits', 'deriveKey']
    );
    
    const key = await crypto.subtle.deriveKey(
        {
            name: 'PBKDF2',
            salt: salt,
            iterations: 100000,
            hash: 'SHA-256'
        },
        keyMaterial,
        { name: 'AES-GCM', length: 256 },
        false,
        ['encrypt']
    );
    
    // Generate IV
    const iv = crypto.getRandomValues(new Uint8Array(12));
    
    // Encrypt
    const encrypted = await crypto.subtle.encrypt(
        { name: 'AES-GCM', iv: iv },
        key,
        data
    );
    
    // Combine salt + iv + encrypted data
    const result = new Uint8Array(salt.length + iv.length + encrypted.byteLength);
    result.set(salt, 0);
    result.set(iv, salt.length);
    result.set(new Uint8Array(encrypted), salt.length + iv.length);
    
    // Return as base64 - use chunk processing for efficiency
    const chunkSize = 0x8000; // Process 32KB at a time
    let binary = '';
    for (let i = 0; i < result.length; i += chunkSize) {
        const chunk = result.subarray(i, Math.min(i + chunkSize, result.length));
        binary += String.fromCharCode.apply(null, chunk);
    }
    return btoa(binary);
}

/**
 * Save the log to the API
 * @returns {Promise<void>}
 */
async function sendLog() {
    if (pasteArea.value === "") {
        return;
    }

    pasteSaveButtons.forEach(button => button.classList.add("btn-working"));

    try {
        let log = pasteArea.value
            .substring(0, parseInt(pasteArea.dataset.maxLength))
            .split('\n').slice(0, parseInt(pasteArea.dataset.maxLines)).join('\n');

        // Apply privacy filters BEFORE encryption/upload
        log = applyPrivacyFilters(log);

        // Get expiration options
        const noResetTimer = document.getElementById('no-reset-timer')?.checked || false;
        const expiryDays = document.getElementById('expiry-days')?.value || '';
        const password = document.getElementById('log-password')?.value || '';
        
        // Encrypt if password provided
        if (password) {
            try {
                log = await encryptLog(log, password);
            } catch (e) {
                console.error('Encryption failed:', e);
                handleUploadError('Failed to encrypt log');
                return;
            }
        }
        
        // Build request params
        const params = { content: log };
        if (noResetTimer) {
            params.no_reset_timer = '1';
        }
        if (expiryDays !== '' && parseInt(expiryDays) > 0) {
            params.expiry_days = expiryDays;
        }
        if (password) {
            params.encrypted = '1';
        }

        const apiBase = window.MCLOGS_CONFIG?.apiBaseUrl || `${location.protocol}//api.${location.host}`;
        const response = await fetch(`${apiBase}/1/log`, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams(params)
        });

        if (!response.ok) {
            handleUploadError(`${response.status} (${response.statusText})`);
            return;
        }

        let data = null;
        try {
            data = await response.json();
        }
        catch (e) {
            console.error("Failed to parse JSON returned by API", e);
            handleUploadError("API returned invalid JSON");
            return;
        }

        if (typeof data === 'object' && !data.success && data.error) {
            console.error(new Error("API returned an error"), data.error);
            handleUploadError(data.error);
            return;
        }

        if (typeof data !== 'object' || !data.success || !data.id) {
            console.error(new Error("API returned an invalid response"), data);
            handleUploadError("API returned an invalid response");
            return;
        }

        location.href = `/${data.id}`;
    }
    catch (e) {
        handleUploadError("Network error");
    }
}

/**
 * Show an error message and stop the loading animation
 * @param {string|null} reason
 * @return {void}
 */
function handleUploadError(reason = null) {
    showPasteError(reason ?? "Unknown error");
    pasteSaveButtons.forEach(button => button.classList.remove("btn-working"));
}

/**
 * show an error message in the paste header and footer
 * @param {string|null} message
 * @return {void}
 */
function showPasteError(message) {
    for (const pasteError of document.querySelectorAll('.paste-error')) {
        pasteError.remove();
    }

    for (const parent of [pasteHeader, pasteFooter]) {
        const pasteError = document.createElement('div');
        pasteError.classList.add('paste-error');
        pasteError.innerText = `Failed to save log: ${message}`;

        parent.insertBefore(pasteError, parent.querySelector('.paste-save'));
    }
}

let dropZone = document.getElementById('dropzone');
let fileSelectButton = document.getElementById('paste-select-file');
let windowDragCount = 0;
let dropZoneDragCount = 0;

function updateWindowDragCount(amount) {
    windowDragCount = Math.max(0, windowDragCount + amount);
    if (windowDragCount > 0) {
        dropZone.classList.add('window-dragover');
    } else {
        dropZone.classList.remove('window-dragover');
    }
}

function updateDropZoneDragCount(amount) {
    dropZoneDragCount = Math.max(0, dropZoneDragCount + amount);
    if (dropZoneDragCount > 0) {
        dropZone.classList.add('dragover');
    } else {
        dropZone.classList.remove('dragover');
    }
}

/**
 * @param {Blob} file
 * @return {Promise<Uint8Array>}
 */
function readFile(file) {
    return new Promise((resolve, reject) => {
        let reader = new FileReader();
        // noinspection JSCheckFunctionSignatures
        reader.onload = () => resolve(new Uint8Array(reader.result));
        reader.onerror = e => reject(e);
        reader.readAsArrayBuffer(file);
    });
}

async function handleDropEvent(e) {
    let files = e.dataTransfer.files;
    if (files.length !== 1) {
        return;
    }

    await loadFileContents(files[0]);
}

async function loadFileContents(file) {
    if (file.size > 1024 * 1024 * 100) {
        return;
    }
    let content = await readFile(file);
    if (file.name.endsWith('.gz')) {
        content = await unpackGz(content);
    }

    if (content.includes(0)) {
        return;
    }

    pasteArea.value = new TextDecoder().decode(content);
}

function loadScript(url) {
    return new Promise((resolve, reject) => {
        let elem = document.createElement('script');
        elem.addEventListener('load', resolve);
        elem.addEventListener('error', reject);
        elem.src = url;
        document.head.appendChild(elem);
    });
}

async function loadFflate() {
    if(typeof fflate === 'undefined') {
        await loadScript('https://unpkg.com/fflate');
    }
}

function selectLogFile() {
    let input = document.createElement('input');
    input.type = 'file';
    input.style.display = 'none';
    document.body.appendChild(input);
    input.onchange = async () => {
        if(input.files.length) {
            await loadFileContents(input.files[0]);
        }
    }
    input.click();
    document.body.removeChild(input);
}

/**
 * @param {Uint8Array} data
 * @return {Promise<Uint8Array>}
 */
async function unpackGz(data) {
    if(typeof DecompressionStream === 'undefined') {
        await loadFflate();
        return fflate.gunzipSync(data);
    }

    let inputStream = new ReadableStream({
        start: (controller) => {
            controller.enqueue(data);
            controller.close();
        }
    });
    const ds = new DecompressionStream('gzip');
    const decompressedStream = inputStream.pipeThrough(ds);
    return new Uint8Array(await new Response(decompressedStream).arrayBuffer());
}

window.addEventListener('dragover', e => e.preventDefault());
window.addEventListener('dragenter', e => {
    e.preventDefault();
    updateWindowDragCount(1);
});
window.addEventListener('dragleave', e => {
    e.preventDefault()
    updateWindowDragCount(-1);
});
window.addEventListener('drop', e => {
    e.preventDefault()
    updateWindowDragCount(-1);
});

dropZone.addEventListener('dragenter', e => {
    e.preventDefault();
    updateDropZoneDragCount(1);
});
dropZone.addEventListener('dragleave', e => {
    e.preventDefault();
    updateDropZoneDragCount(-1);
});
dropZone.addEventListener('drop', async e => {
    e.preventDefault();
    updateDropZoneDragCount(-1);
    await handleDropEvent(e);
});

fileSelectButton.addEventListener('click', selectLogFile);
