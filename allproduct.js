// Global variables
let allProducts = [];
let filteredProducts = [];
let currentSort = 'latest';
let currentLimit = 0;
let currentOffset = 0;

// API endpoint
const projectRoot = window.location.pathname.split('/')[1];
const API_ENDPOINTS = [
    `/${projectRoot}/controllers/product_home.php`
];

// Initialize page
document.addEventListener('DOMContentLoaded', function () {
    console.log("=== DOM Content Loaded (All Products) ===");
    loadProducts();
    setupEventListeners();
    setupEnhancedSearch(); // Add enhanced search setup
    
    // Setup cart system check
    waitForDependencies(() => {
        console.log("Dependencies loaded for all products page");
    });
});

// Setup event listeners
function setupEventListeners() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                searchProducts();
            }
        });
    }

    // Price range inputs
    const minPrice = document.getElementById('minPrice');
    const maxPrice = document.getElementById('maxPrice');
    if (minPrice) minPrice.addEventListener('input', applyCurrentFilters);
    if (maxPrice) maxPrice.addEventListener('input', applyCurrentFilters);
}

// Load products
async function loadProducts(limit = 0, offset = 0) {
    showLoading();
    
    currentLimit = limit;
    currentOffset = offset;

    for (let i = 0; i < API_ENDPOINTS.length; i++) {
        const endpoint = API_ENDPOINTS[i];
        let url = endpoint;

        if (limit > 0) {
            url += `?limit=${limit}&offset=${offset}`;
        }

        try {
            console.log(`Fetching from: ${url}`);
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const text = await response.text();
            console.log("Raw response:", text);

            let result;
            try {
                result = JSON.parse(text);
            } catch (parseError) {
                console.error('JSON parse error:', parseError, 'from', text);
                continue;
            }

            console.log("Parsed API Response:", result);

            if (result && result.success) {
                let products = result.data.products || result.data || [];

                if (!Array.isArray(products)) {
                    console.error('Products data is not an array:', products);
                    throw new Error('Invalid products data format');
                }

                console.log(`Found ${products.length} products`);

                // Map product data
                allProducts = products.map((product, index) => {
                    const productId = String(product.product_id || product.id || '').trim();
                    if (!productId) {
                        console.warn('Product found with no ID:', product);
                    }

                    return {
                        id: productId,
                        product_id: productId,
                        name: product.name || 'ไม่ระบุชื่อ',
                        category: product.category_name || 'ไม่ระบุ',
                        category_id: product.category_id || '',
                        price: parseFloat(product.price) || 0,
                        description: product.description || '',
                        images: product.images || [],
                        image: getMainImageUrl(product),
                        date: new Date(product.created_at),
                        lot: product.lot || '',
                        stock: parseInt(product.stock) || 0,
                        supplier: product.supplier_name || 'ไม่ระบุ',
                        supplier_name: product.supplier_name || 'ไม่ระบุ',
                        received_date: product.received_date || '',
                        specifications: product.specifications || '',
                        weight: parseFloat(product.weight) || 0,
                        dimensions: product.dimensions || '',
                        width: product.width || 0,
                        length: product.length || 0,
                        height: product.height || 0,
                        width_unit: product.width_unit || 'mm',
                        length_unit: product.length_unit || 'mm',
                        height_unit: product.height_unit || 'mm',
                        weight_unit: product.weight_unit || 'kg',
                        grade: product.grade || '',
                        unit: product.unit || 'กก.'
                    };
                });

                // Filter out products without ID
                allProducts = allProducts.filter(product => product.id && product.id !== '');

                console.log(`Filtered products: ${allProducts.length} items`);
                console.log("Final products array:", allProducts);

                filteredProducts = [...allProducts];
                displayProducts();
                hideLoading();

                console.log(`โหลดข้อมูลสินค้าสำเร็จ: ${allProducts.length} รายการ`);
                return;

            } else {
                throw new Error(result.message || 'API returned success: false');
            }

        } catch (error) {
            console.error(`API endpoint ${endpoint} failed:`, error);
            if (i === API_ENDPOINTS.length - 1) {
                showError(`ไม่สามารถเชื่อมต่อ API ได้: ${error.message}`);
            }
        }
    }
}

// Display products
function displayProducts() {
    const grid = document.getElementById('productsGrid');
    const resultsCount = document.getElementById('resultsCount');

    if (!grid) {
        console.error('Element with id "productsGrid" not found');
        return;
    }

    if (resultsCount) {
        resultsCount.textContent = `แสดงสินค้าทั้งหมด ${filteredProducts.length} รายการ`;
    }

    if (filteredProducts.length === 0) {
        grid.innerHTML = '<div class="no-products"><h3>ไม่พบสินค้าที่ค้นหา</h3><p>กรุณาลองใช้คำค้นหาอื่น หรือเปลี่ยนตัวกรอง</p></div>';
        return;
    }

    grid.innerHTML = filteredProducts.map((product, index) => createProductCard(product, index)).join('');

    // Setup event delegation for cart buttons (capture phase to block card navigation)
    if (grid.__cartHandlerAttached !== true) {
        grid.addEventListener('click', function (event) {
            const button = event.target.closest('.add-to-cart-btn');
            if (button && !button.disabled && grid.contains(button)) {
                const index = button.dataset.productIndex;
                const product = filteredProducts[index];
                // Prevent card click navigation
                if (event.stopPropagation) event.stopPropagation();
                if (event.preventDefault) event.preventDefault();
                handleAddToCart(product, { target: button });
            }
        }, true); // use capture to intercept before bubbling to card
        grid.__cartHandlerAttached = true;
    }
}

// Create product card HTML (matching style from document 3 & 4)
function createProductCard(product, index) {
    const imageUrl = getMainImageUrl(product);
    const dimensions = formatDimensions(product);
    const price = formatPrice(product.price);
    const stockStatus = getStockStatus(product.stock);
    const productDetailUrl = `product.php?id=${product.product_id}`;
    const cleanProductId = String(product.id || '').trim();

    // Create details array
    const details = [];
    if (product.weight && product.weight > 0) {
        details.push(`น้ำหนัก: ${product.weight} ${product.weight_unit || 'kg'}`);
    }
    if (product.dimensions) {
        details.push(`ขนาด: ${product.dimensions}`);
    }
    if (product.grade) {
        details.push(`เกรด: ${product.grade}`);
    }

    // Stock status styling (treat unknown stock as available)
    const stockIsNumber = typeof product.stock === 'number' && !isNaN(product.stock);
    let stockClass = 'stock-available';
    let stockText = stockIsNumber ? `คงเหลือ ${product.stock} ชิ้น` : 'มีสินค้า';
    
    if (stockIsNumber && product.stock === 0) {
        stockClass = 'stock-out';
        stockText = 'สินค้าหมด';
    } else if (stockIsNumber && product.stock > 0 && product.stock <= 5) {
        stockClass = 'stock-low';
        stockText = `เหลือน้อย ${product.stock} ชิ้น`;
    }

    return `
        <div class="product-card" 
             data-category="${product.category}" 
             data-price="${product.price}" 
             data-product-id="${cleanProductId}" 
             data-product-index="${index}"
             onclick="navigateToProduct('${product.product_id}')">
            <div class="product-image">
                <span class="product-category">${product.category}</span>
                ${imageUrl
                    ? `<img src="${imageUrl}" alt="${escapeHtml(product.name)}" 
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                       <div style="display:none; padding: 20px; background: #f8f9fa; color: #666; width: 100%; height: 100%; align-items: center; justify-content: center;">
                           <div class="steel-bars">
                               <div class="steel-bar"></div>
                               <div class="steel-bar"></div>
                               <div class="steel-bar"></div>
                               <div class="steel-bar"></div>
                               <div class="steel-bar"></div>
                           </div>
                       </div>`
                    : `<div style="padding: 20px; background: #f8f9fa; color: #666; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                           <div class="steel-bars">
                               <div class="steel-bar"></div>
                               <div class="steel-bar"></div>
                               <div class="steel-bar"></div>
                               <div class="steel-bar"></div>
                               <div class="steel-bar"></div>
                           </div>
                       </div>`
                }
            </div>
            
            <div class="product-info">
                <div class="product-title">${escapeHtml(product.name) || 'ไม่ระบุชื่อ'}</div>
                
                ${product.specifications 
                    ? `<div class="product-specs">${escapeHtml(product.specifications)}</div>`
                    : `<div class="product-specs">${dimensions}</div>`
                }
                
                ${details.length > 0 
                    ? `<div class="product-details">
                        ${details.slice(0, 3).map(detail => `
                            <div class="product-detail-line">
                                <span class="detail-label">${detail.split(':')[0]}:</span>
                                <span class="detail-value">${detail.split(':')[1] || ''}</span>
                            </div>
                        `).join('')}
                       </div>`
                    : ''
                }
                
                <div class="product-price">${price}</div>
                
                <div class="product-stock ${stockClass}">
                    ${stockText}
                </div>

                <div class="action-buttons">
                    <button class="add-to-cart-btn" 
                            data-product-index="${index}"
                            ${stockIsNumber && product.stock === 0 ? 'disabled' : ''}>
                        ${(stockIsNumber && product.stock === 0) ? 'สินค้าหมด' : 'ใส่ตะกร้า'}
                    </button>
                    <a href="${productDetailUrl}" class="view-detail-btn" onclick="event.stopPropagation();">
                        ดูรายละเอียด
                    </a>
                </div>
            </div>
        </div>
    `;
}

// Utility functions matching document 4 style
function getMainImageUrl(product) {
    if (product.images && Array.isArray(product.images) && product.images.length > 0) {
        const mainImage = product.images.find(img => img.is_main === 1 || img.is_main === '1');
        let selectedImageUrl = mainImage ? mainImage.image_url : product.images[0].image_url;

        if (selectedImageUrl) {
            if (!selectedImageUrl.startsWith('http') && !selectedImageUrl.startsWith('/')) {
                const projectRoot = window.location.pathname.split('/')[1];
                selectedImageUrl = `/${projectRoot}/admin/controllers/uploads/products/${selectedImageUrl}`;
            }
        }

        return selectedImageUrl;
    }
    return null;
}

// Format dimensions
function formatDimensions(product) {
    let dimensions = [];
    if (product.width) dimensions.push(`กว้าง ${product.width} ${product.width_unit || 'mm'}`);
    if (product.length) dimensions.push(`ยาว ${product.length} ${product.length_unit || 'mm'}`);
    if (product.height) dimensions.push(`สูง ${product.height} ${product.height_unit || 'mm'}`);
    //if (product.weight) dimensions.push(`หนัก ${product.weight} ${product.weight_unit || 'kg'}`);
    return dimensions.length > 0 ? dimensions.join(', ') : 'ไม่ระบุขนาด';
}

// Format price
function formatPrice(price) {
    if (!price || price == 0) return 'ราคาสอบถาม';
    return parseFloat(price).toLocaleString('th-TH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }) + ' บาท';
}

// Stock status
function getStockStatus(stock) {
    return (!stock || stock <= 0) ? 'สินค้าหมด' : `คงเหลือ ${stock} ชิ้น`;
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Navigate to product detail page
function navigateToProduct(productId) {
    if (productId) {
        window.location.href = `product.php?id=${productId}`;
    }
}

/// Enhanced Helper function to normalize text for better Thai/English search
function normalizeSearchText(text) {
    if (!text) return '';
    
    return text
        // Convert to lowercase
        .toLowerCase()
        // Remove extra whitespace
        .trim()
        // Replace multiple spaces with single space
        .replace(/\s+/g, ' ')
        // Remove special characters but keep Thai characters, English characters, numbers, and spaces
        .replace(/[^\u0e00-\u0e7fa-z0-9\s]/g, '')
        // Thai character normalization
        .replace(/ำ/g, 'ํา')  // Normalize sara am
        .replace(/์/g, '')   // Remove mai taikhu (silent marker)
        // Additional Thai normalizations
        .replace(/เ([ก-ฮ])ะ/g, 'เ$1็') // เกะ -> เก็
        .replace(/โ([ก-ฮ])ะ/g, 'โ$1็') // โกะ -> โก็
        .replace(/([ก-ฮ])ั([ก-ฮ])/g, '$1ิ$2') // กัน -> กิน
        // English character normalization
        .replace(/ph/g, 'f')    // phone -> fone
        .replace(/ck/g, 'k')    // black -> blak
        .replace(/qu/g, 'kw')   // queen -> kween
        // Remove common prefixes/suffixes for better matching
        .replace(/^(the|a|an)\s+/g, '') // Remove English articles
        .replace(/\s+(ltd|co|inc|corp)$/g, ''); // Remove company suffixes
}

// Enhanced function to create search variations
function createSearchVariations(text) {
    if (!text) return [];
    
    const variations = new Set();
    const normalized = normalizeSearchText(text);
    
    // Add original and normalized versions
    variations.add(text.toLowerCase());
    variations.add(normalized);
    
    // Thai-specific variations
    if (/[\u0e00-\u0e7f]/.test(text)) {
        // Common Thai typos and variations
        const thaiVariations = normalized
            // ไ/ใ confusion
            .replace(/ไ/g, 'ใ').replace(/ใ/g, 'ไ')
            // อ/ออ confusion  
            .replace(/ออ/g, 'อ').replace(/([ก-ฮ])อ([ก-ฮ])/g, '$1ออ$2')
            // Short/long vowels
            .replace(/า/g, '').replace(/([ก-ฮ])([ก-ฮ])/g, '$1า$2')
            // Silent letters
            .replace(/ห([ก-ฮ])/g, '$1').replace(/([ก-ฮ])/g, 'ห$1');
        
        variations.add(thaiVariations);
        
        // Add phonetic variations for common Thai words
        const phoneticMap = {
            'เหล็ก': ['เหลก', 'เลก', 'เล็ก'],
            'เหล่า': ['เหลา', 'เลา'],
            'เหลือ': ['เหลอ', 'เลอ'],
            'สเตน': ['สเตนเลส', 'สเตนเลสส์', 'stainless', 'stain'],
            'คาร์บอน': ['carbon', 'คารบอน', 'คาบอน'],
            'อลูมิเนียม': ['aluminum', 'aluminium', 'อลูมินั่ม', 'อลูม'],
            'ทองแดง': ['copper', 'คอปเปอร์', 'ทองแดง'],
            'ทองเหลือง': ['brass', 'บราส', 'ทองเหลือง']
        };
        
        Object.entries(phoneticMap).forEach(([thai, variants]) => {
            if (normalized.includes(thai)) {
                variants.forEach(variant => variations.add(normalized.replace(thai, variant)));
            }
        });
    }
    
    // English-specific variations
    if (/[a-z]/.test(text)) {
        // Common English variations
        const englishVariations = normalized
            // Common typos
            .replace(/ie/g, 'ei').replace(/ei/g, 'ie')
            .replace(/c/g, 'k').replace(/k/g, 'c')
            .replace(/f/g, 'ph').replace(/ph/g, 'f')
            // Plural/singular
            .replace(/s$/g, '').replace(/([^s])$/g, '$1s')
            // Past tense
            .replace(/ed$/g, '').replace(/([^e])$/g, '$1ed');
        
        variations.add(englishVariations);
        
        // Add common English-Thai material translations
        const materialMap = {
            'steel': ['เหล็ก', 'สตีล'],
            'stainless': ['สเตนเลส', 'สเตนเลสส์', 'เหล็กกล้าไร้สนิม'],
            'carbon': ['คาร์บอน', 'คารบอน'],
            'aluminum': ['อลูมิเนียม', 'อลูมินัม'],
            'copper': ['ทองแดง', 'คอปเปอร์'],
            'brass': ['ทองเหลือง', 'บราส'],
            'iron': ['เหล็ก', 'ไอรอน'],
            'galvanized': ['ชุบสังกะสี', 'กัลวาไนซ์'],
            'pipe': ['ท่อ', 'ไปป์'],
            'sheet': ['แผ่น', 'ชีท'],
            'plate': ['แผ่น', 'เพลท'],
            'bar': ['แท่ง', 'บาร์'],
            'rod': ['แกน', 'ร็อด'],
            'wire': ['ลวด', 'ไวร์'],
            'coil': ['ม้วน', 'คอยล์']
        };
        
        Object.entries(materialMap).forEach(([eng, thaiVars]) => {
            if (normalized.includes(eng)) {
                thaiVars.forEach(thai => variations.add(normalized.replace(eng, thai)));
            }
        });
    }
    
    return Array.from(variations).filter(v => v.length > 0);
}

// Enhanced fuzzy search function
function fuzzyMatch(searchTerm, targetText, threshold = 0.6) {
    if (!searchTerm || !targetText) return false;
    
    const search = searchTerm.toLowerCase();
    const target = targetText.toLowerCase();
    
    // Exact match
    if (target.includes(search)) return true;
    
    // Calculate Levenshtein distance for short terms
    if (search.length <= 4) {
        const distance = levenshteinDistance(search, target);
        return distance <= 1; // Allow 1 character difference for short terms
    }
    
    // For longer terms, use Jaro-Winkler similarity
    const similarity = jaroWinklerSimilarity(search, target);
    return similarity >= threshold;
}

// Levenshtein distance calculation
function levenshteinDistance(str1, str2) {
    const matrix = [];
    
    for (let i = 0; i <= str2.length; i++) {
        matrix[i] = [i];
    }
    
    for (let j = 0; j <= str1.length; j++) {
        matrix[0][j] = j;
    }
    
    for (let i = 1; i <= str2.length; i++) {
        for (let j = 1; j <= str1.length; j++) {
            if (str2.charAt(i - 1) === str1.charAt(j - 1)) {
                matrix[i][j] = matrix[i - 1][j - 1];
            } else {
                matrix[i][j] = Math.min(
                    matrix[i - 1][j - 1] + 1,
                    matrix[i][j - 1] + 1,
                    matrix[i - 1][j] + 1
                );
            }
        }
    }
    
    return matrix[str2.length][str1.length];
}

// Jaro-Winkler similarity calculation
function jaroWinklerSimilarity(s1, s2) {
    if (s1 === s2) return 1.0;
    if (s1.length === 0 || s2.length === 0) return 0.0;
    
    const matchWindow = Math.floor(Math.max(s1.length, s2.length) / 2) - 1;
    if (matchWindow < 0) return 0.0;
    
    const s1Matches = new Array(s1.length).fill(false);
    const s2Matches = new Array(s2.length).fill(false);
    
    let matches = 0;
    let transpositions = 0;
    
    // Identify matches
    for (let i = 0; i < s1.length; i++) {
        const start = Math.max(0, i - matchWindow);
        const end = Math.min(i + matchWindow + 1, s2.length);
        
        for (let j = start; j < end; j++) {
            if (s2Matches[j] || s1[i] !== s2[j]) continue;
            s1Matches[i] = s2Matches[j] = true;
            matches++;
            break;
        }
    }
    
    if (matches === 0) return 0.0;
    
    // Count transpositions
    let k = 0;
    for (let i = 0; i < s1.length; i++) {
        if (!s1Matches[i]) continue;
        while (!s2Matches[k]) k++;
        if (s1[i] !== s2[k]) transpositions++;
        k++;
    }
    
    const jaro = (matches / s1.length + matches / s2.length + 
                  (matches - transpositions / 2) / matches) / 3.0;
    
    // Jaro-Winkler similarity
    if (jaro < 0.7) return jaro;
    
    let prefix = 0;
    for (let i = 0; i < Math.min(s1.length, s2.length); i++) {
        if (s1[i] === s2[i]) prefix++;
        else break;
    }
    
    return jaro + (0.1 * prefix * (1 - jaro));
}

// Enhanced search function with scoring system
function calculateSearchScore(product, searchTerms, searchVariations) {
    let totalScore = 0;
    let maxPossibleScore = searchTerms.length * 10; // Max score per term = 10
    
    // Prepare searchable fields with weights
    const searchableFields = [
        { text: product.name || '', weight: 10 },           // Highest weight for product name
        { text: product.category || '', weight: 8 },        // High weight for category
        { text: product.specifications || '', weight: 6 },   // Medium-high for specifications
        { text: product.description || '', weight: 5 },     // Medium for description
        { text: product.grade || '', weight: 4 },           // Medium-low for grade
        { text: product.supplier_name || '', weight: 3 },   // Lower for supplier
        { text: product.lot || '', weight: 2 }              // Lowest for lot
    ];
    
    searchTerms.forEach(term => {
        let termScore = 0;
        let termMaxScore = 0;
        
        searchableFields.forEach(field => {
            termMaxScore += field.weight;
            const fieldText = normalizeSearchText(field.text);
            
            // Exact match in original text
            if (field.text.toLowerCase().includes(term.toLowerCase())) {
                termScore += field.weight * 1.0;
            }
            // Exact match in normalized text
            else if (fieldText.includes(normalizeSearchText(term))) {
                termScore += field.weight * 0.9;
            }
            // Fuzzy match
            else if (fuzzyMatch(term, fieldText, 0.7)) {
                termScore += field.weight * 0.7;
            }
            // Check variations
            else {
                const termVariations = searchVariations[term] || [];
                for (const variation of termVariations) {
                    if (fieldText.includes(variation)) {
                        termScore += field.weight * 0.8;
                        break;
                    }
                    if (fuzzyMatch(variation, fieldText, 0.6)) {
                        termScore += field.weight * 0.6;
                        break;
                    }
                }
            }
        });
        
        totalScore += (termScore / termMaxScore) * 10; // Normalize to 0-10 per term
    });
    
    return totalScore / searchTerms.length; // Average score per term
}

// Completely rewritten applyCurrentFilters function with enhanced search
function applyCurrentFilters() {
    let filtered = [...allProducts];
    
    // Enhanced search term filter
    const searchInput = document.getElementById('searchInput');
    const searchTerm = searchInput ? searchInput.value.trim() : '';
    
    if (searchTerm) {
        console.log(`🔍 Searching for: "${searchTerm}"`);
        
        // Split search term into individual words
        const searchWords = searchTerm.split(/\s+/).filter(word => word.length > 0);
        console.log('Search words:', searchWords);
        
        // Create variations for each search word
        const searchVariations = {};
        searchWords.forEach(word => {
            searchVariations[word] = createSearchVariations(word);
            console.log(`Variations for "${word}":`, searchVariations[word]);
        });
        
        // Filter products with scoring
        const scoredProducts = filtered.map(product => ({
            product,
            score: calculateSearchScore(product, searchWords, searchVariations)
        })).filter(item => item.score > 0); // Only keep products with some match
        
        // Sort by score (highest first) and extract products
        filtered = scoredProducts
            .sort((a, b) => b.score - a.score)
            .map(item => item.product);
        
        console.log(`🎯 Found ${filtered.length} matching products`);
        
        // Debug: Show top 3 matches with scores
        if (filtered.length > 0) {
            console.log('Top matches:');
            scoredProducts.slice(0, 3).forEach((item, index) => {
                console.log(`${index + 1}. "${item.product.name}" (Score: ${item.score.toFixed(2)})`);
            });
        }
    }

    // Category filter (unchanged)
    const checkedCategories = Array.from(document.querySelectorAll('input[type="checkbox"]:checked'))
        .map(checkbox => checkbox.value);
    if (checkedCategories.length > 0) {
        filtered = filtered.filter(product =>
            checkedCategories.includes(product.category)
        );
    }

    // Price range filter (unchanged)
    const minPriceInput = document.getElementById('minPrice');
    const maxPriceInput = document.getElementById('maxPrice');
    const minPrice = minPriceInput ? (parseFloat(minPriceInput.value) || 0) : 0;
    const maxPrice = maxPriceInput ? parseFloat(maxPriceInput.value) : null;

    if (minPrice > 0 || maxPrice) {
        filtered = filtered.filter(product => {
            const productPrice = parseFloat(product.price) || 0;
            const minCheck = productPrice >= minPrice;
            const maxCheck = !maxPrice || productPrice <= maxPrice;
            return minCheck && maxCheck;
        });
    }

    filteredProducts = filtered;
}

// Enhanced search setup (manual search only)
function setupEnhancedSearch() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;
    
    // Only keep the enter key functionality for manual search
    searchInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            searchProducts();
        }
    });
    
    // Simple placeholder text
    if (!searchInput.placeholder) {
        searchInput.placeholder = 'ค้นหาสินค้า...';
    }
}

// Enhanced test function with more comprehensive testing
window.testEnhancedSearch = function(testTerms = ['เหล็ก', 'steel', 'สเตนเลส', 'stainless', 'แผ่น', 'sheet']) {
    console.log('🧪 Testing Enhanced Search System');
    console.log('================================');
    
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) {
        console.error('❌ Search input not found');
        return;
    }
    
    testTerms.forEach((term, index) => {
        console.log(`\n🔍 Test ${index + 1}: "${term}"`);
        searchInput.value = term;
        applyCurrentFilters();
        sortProducts();
        
        console.log(`📊 Results: ${filteredProducts.length} products found`);
        
        if (filteredProducts.length > 0) {
            console.log('🏆 Top 3 results:');
            filteredProducts.slice(0, 3).forEach((product, idx) => {
                console.log(`   ${idx + 1}. ${product.name} (${product.category})`);
            });
        } else {
            console.log('❌ No results found');
        }
    });
    
    // Test variations
    console.log('\n🔄 Testing search variations:');
    const testVariations = createSearchVariations('เหล็กสเตนเลส');
    console.log('Variations for "เหล็กสเตนเลส":', testVariations);
    
    console.log('\n✅ Enhanced search test completed');
};

// Additional utility function for debugging search performance
window.debugSearchPerformance = function(searchTerm) {
    const startTime = performance.now();
    
    console.log(`⏱️  Performance test for: "${searchTerm}"`);
    
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = searchTerm;
        applyCurrentFilters();
        
        const endTime = performance.now();
        const duration = endTime - startTime;
        
        console.log(`🏁 Search completed in ${duration.toFixed(2)}ms`);
        console.log(`📊 Found ${filteredProducts.length} results`);
        console.log(`⚡ Performance: ${duration < 100 ? 'Excellent' : duration < 300 ? 'Good' : 'Needs optimization'}`);
    }
};

// Search products
function searchProducts() {
    applyCurrentFilters();
    sortProducts();
    displayProducts();
}

// Filter by category
function filterByCategory() {
    applyCurrentFilters();
    sortProducts();
    displayProducts();
}

// Apply price filter
function applyPriceFilter() {
    applyCurrentFilters();
    sortProducts();
    displayProducts();
}

// Sort products
function sortProducts() {
    const sortValue = document.getElementById('sortSelect')?.value || 'latest';
    currentSort = sortValue;
    
    switch (sortValue) {
        case 'price-high':
            filteredProducts.sort((a, b) => b.price - a.price);
            break;
        case 'price-low':
            filteredProducts.sort((a, b) => a.price - b.price);
            break;
        case 'name-az':
            filteredProducts.sort((a, b) => a.name.localeCompare(b.name, 'th'));
            break;
        case 'latest':
        default:
            filteredProducts.sort((a, b) => b.date - a.date);
            break;
    }
}

// Clear all filters
function clearAllFilters() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) searchInput.value = '';

    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = false;
    });

    const minPrice = document.getElementById('minPrice');
    const maxPrice = document.getElementById('maxPrice');
    if (minPrice) minPrice.value = '';
    if (maxPrice) maxPrice.value = '';

    const sortSelect = document.getElementById('sortSelect');
    if (sortSelect) sortSelect.value = 'latest';
    currentSort = 'latest';

    filteredProducts = [...allProducts];
    sortProducts();
    displayProducts();
}

// Handle add to cart
function handleAddToCart(product, event) {
    if (!product || !product.id) return;

    console.log(`Adding to cart: ${product.name} (ID: ${product.id})`);

    // Use CartManager if available
    if (typeof cartManager !== 'undefined' && cartManager.addItem) {
        cartManager.addItem(
            product.id,
            product.name,
            product.price,
            1,
            product.image,
            product.weight || 0
        );

        // Show toast notification
        if (typeof showToast === 'function') {
            showToast(`เพิ่ม "${product.name}" ลงในตะกร้าแล้ว!`);
        } else {
            alert(`เพิ่ม "${product.name}" ลงในตะกร้าแล้ว!`);
        }

        // Button animation
        if (event && event.target) {
            const button = event.target;
            const originalText = button.textContent;
            const originalBg = button.style.background;

            button.textContent = 'เพิ่มแล้ว!';
            button.style.background = '#27ae60';
            button.disabled = true;

            setTimeout(() => {
                button.textContent = originalText;
                button.style.background = originalBg;
                button.disabled = false;
            }, 1500);
        }

    } else {
        console.error('CartManager ยังโหลดไม่เสร็จ');
        fallbackAddToCart(product, event);
    }
}

// Fallback add to cart function
function fallbackAddToCart(product, event) {
    try {
        let cart = JSON.parse(localStorage.getItem('shopping_cart') || '{}');
        const itemKey = String(product.id).trim();

        if (cart[itemKey]) {
            cart[itemKey].quantity += 1;
        } else {
            cart[itemKey] = {
                id: itemKey,
                name: product.name,
                price: product.price,
                quantity: 1,
                image: product.image,
                addedAt: new Date().toISOString()
            };
        }

        localStorage.setItem('shopping_cart', JSON.stringify(cart));

        // Update cart badge
        const totalItems = Object.values(cart).reduce((total, item) => total + item.quantity, 0);
        const cartBadge = document.getElementById('cartBadge');
        if (cartBadge) {
            cartBadge.textContent = totalItems;
            cartBadge.style.display = totalItems > 0 ? 'flex' : 'none';
        }

        if (typeof showToast === 'function') {
            showToast(`เพิ่ม "${product.name}" ลงในตะกร้าแล้ว!`);
        } else {
            alert(`เพิ่ม "${product.name}" ลงในตะกร้าแล้ว!`);
        }

        // Button animation
        if (event && event.target) {
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = 'เพิ่มแล้ว!';
            button.style.background = '#27ae60';
            button.disabled = true;

            setTimeout(() => {
                button.textContent = originalText;
                button.style.background = '';
                button.disabled = false;
            }, 1500);
        }

    } catch (error) {
        console.error('Fallback add to cart failed:', error);
        alert('เกิดข้อผิดพลาดในการเพิ่มสินค้า กรุณาลองใหม่อีกครั้ง');
    }
}

// Fetch individual product data
async function fetchProductData(productId) {
    try {
        const projectRoot = window.location.pathname.split('/')[1];
        const url = `/${projectRoot}/controllers/product_home.php?product_id=${productId}`;
        
        console.log(`Fetching product data from: ${url}`);
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const text = await response.text();
        console.log('Raw API response:', text);
        
        let result;
        try {
            result = JSON.parse(text);
        } catch (parseError) {
            console.error('JSON parse error:', parseError, 'Response:', text);
            throw new Error('รูปแบบข้อมูลจากเซิร์ฟเวอร์ไม่ถูกต้อง');
        }

        if (result.success && result.data) {
            console.log('Product data fetched successfully:', result.data);
            return result.data;
        } else {
            throw new Error(result.message || 'ไม่พบข้อมูลสินค้า');
        }

    } catch (error) {
        console.error('Fetch product data error:', error);
        throw error;
    }
}

// Loading and error display functions
function showLoading() {
    const grid = document.getElementById('productsGrid');
    if (grid) {
        grid.innerHTML = '<div class="loading">กำลังโหลดข้อมูลสินค้า...</div>';
    }
}

function hideLoading() {
    // Loading state removed when displayProducts() is called
}

function showError(message) {
    const grid = document.getElementById('productsGrid');
    if (grid) {
        grid.innerHTML = `<div class="error">${message}</div>`;
    }
}

// Wait for dependencies
function waitForDependencies(callback, attempts = 0, maxAttempts = 30) {
    console.log(`Waiting for dependencies... attempt ${attempts + 1}`);
    console.log('Cart Manager available:', typeof window.cartManager);

    if (typeof window.cartManager !== 'undefined') {
        console.log("✅ Cart Manager loaded!");
        callback();
    } else if (attempts < maxAttempts) {
        setTimeout(() => waitForDependencies(callback, attempts + 1, maxAttempts), 200);
    } else {
        console.warn("⚠️ Cart Manager not found after maximum attempts, proceeding anyway");
        callback();
    }
}

// Toast notification system
function showToast(message, type = 'success') {
    const existingToast = document.querySelector('.toast');
    if (existingToast) {
        existingToast.remove();
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    if (!document.querySelector('#toast-styles')) {
        const style = document.createElement('style');
        style.id = 'toast-styles';
        style.textContent = `
            .toast {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                color: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                z-index: 10000;
                font-size: 14px;
                max-width: 300px;
                word-wrap: break-word;
                animation: slideInRight 0.3s ease-out;
            }
            .toast-success {
                background: #27ae60;
            }
            .toast-error {
                background: #e74c3c;
            }
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }
    
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease-in';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 3000);
}

// Debug functions
function debugProductData() {
    console.log('=== Product Data Debug ===');
    console.log('All products loaded:', allProducts.length);
    
    if (allProducts.length > 0) {
        const sample = allProducts[0];
        console.log('Sample product:', {
            id: sample.product_id,
            name: sample.name,
            price: sample.price,
            weight: sample.weight,
            stock: sample.stock,
            images: sample.images ? sample.images.length : 0
        });
    }
    
    console.log('Cart items:', window.cartManager ? window.cartManager.getCartItems() : 'CartManager not ready');
    console.log('========================');
}

// Check cart system
function checkCartSystem() {
    console.log('=== Cart System Check ===');
    console.log('CartManager available:', !!window.cartManager);
    console.log('All products loaded:', allProducts.length);
    console.log('Toast function available:', typeof showToast);
    console.log('Cart items count:', window.cartManager ? window.cartManager.getTotalItems() : 'N/A');
    console.log('========================');
    
    if (!window.cartManager) {
        console.error('CartManager not found! Make sure cart.js is loaded properly.');
        return false;
    }
    
    return true;
}

// Setup debug functions
window.debugProductData = debugProductData;
window.checkCartSystem = checkCartSystem;

// Test add to cart function
window.testAddToCart = function() {
    console.log('Testing add to cart system...');
    
    if (allProducts.length > 0) {
        const testProduct = allProducts[0];
        console.log('Test product:', testProduct);
        
        // Create mock event
        const mockEvent = {
            target: {
                disabled: false,
                textContent: 'ใส่ตะกร้า',
                style: {}
            }
        };
        
        handleAddToCart(testProduct, mockEvent);
    } else {
        console.error('No products available for testing');
    }
};

// Sort products - แก้ไขแล้ว
function sortProducts() {
    const sortValue = document.getElementById('sortSelect')?.value || 'latest';
    currentSort = sortValue;
    
    console.log('Sorting products by:', sortValue);
    
    switch (sortValue) {
        case 'price-high':
            // เรียงราคาจากสูงไปต่ำ
            filteredProducts.sort((a, b) => {
                const priceA = parseFloat(a.price) || 0;
                const priceB = parseFloat(b.price) || 0;
                return priceB - priceA;
            });
            console.log('Sorted by price (high to low)');
            break;
            
        case 'price-low':
            // เรียงราคาจากต่ำไปสูง
            filteredProducts.sort((a, b) => {
                const priceA = parseFloat(a.price) || 0;
                const priceB = parseFloat(b.price) || 0;
                return priceA - priceB;
            });
            console.log('Sorted by price (low to high)');
            break;
            
        case 'name-az':
            // เรียงชื่อ A-Z (รองรับภาษาไทยและภาษาอังกฤษ)
            filteredProducts.sort((a, b) => {
                const nameA = (a.name || '').toLowerCase();
                const nameB = (b.name || '').toLowerCase();
                
                // ใช้ localeCompare เพื่อรองรับการเรียงภาษาไทย
                return nameA.localeCompare(nameB, ['th', 'en'], {
                    numeric: true,
                    sensitivity: 'base'
                });
            });
            console.log('Sorted by name (A-Z)');
            break;
            
        case 'name-za':
            // เรียงชื่อ Z-A (เพิ่มเติม)
            filteredProducts.sort((a, b) => {
                const nameA = (a.name || '').toLowerCase();
                const nameB = (b.name || '').toLowerCase();
                
                // ใช้ localeCompare แล้วกลับผลลัพธ์
                return nameB.localeCompare(nameA, ['th', 'en'], {
                    numeric: true,
                    sensitivity: 'base'
                });
            });
            console.log('Sorted by name (Z-A)');
            break;
            
        case 'latest':
        default:
            // เรียงวันที่จากใหม่ไปเก่า
            filteredProducts.sort((a, b) => {
                const dateA = new Date(a.date || 0);
                const dateB = new Date(b.date || 0);
                return dateB - dateA;
            });
            console.log('Sorted by latest');
            break;
    }
    
    console.log(`Total products after sorting: ${filteredProducts.length}`);
    
    // Debug: แสดงผลลัพธ์ 3 รายการแรกหลังจากเรียง
    if (filteredProducts.length > 0) {
        console.log('Top 3 products after sorting:');
        filteredProducts.slice(0, 3).forEach((product, index) => {
            console.log(`${index + 1}. ${product.name} - ราคา: ${product.price} บาท`);
        });
    }
}

// เพิ่มฟังก์ชันสำหรับเรียกใช้การเรียงลำดับ
function applySorting() {
    sortProducts();
    displayProducts();
}

// แก้ไขฟังก์ชัน searchProducts ให้เรียกใช้การเรียงลำดับด้วย
function searchProducts() {
    console.log('Searching products...');
    applyCurrentFilters();
    sortProducts();  // เพิ่มการเรียงลำดับหลังค้นหา
    displayProducts();
}

// แก้ไขฟังก์ชัน filterByCategory ให้เรียกใช้การเรียงลำดับด้วย
function filterByCategory() {
    console.log('Filtering by category...');
    applyCurrentFilters();
    sortProducts();  // เพิ่มการเรียงลำดับหลังกรอง
    displayProducts();
}

// แก้ไขฟังก์ชัน applyPriceFilter ให้เรียกใช้การเรียงลำดับด้วย
function applyPriceFilter() {
    console.log('Applying price filter...');
    applyCurrentFilters();
    sortProducts();  // เพิ่มการเรียงลำดับหลังกรองราคา
    displayProducts();
}

// เพิ่มฟังก์ชันทดสอบการเรียงลำดับ
window.testSorting = function() {
    console.log('=== Testing Sort Functions ===');
    
    const sortOptions = ['latest', 'price-high', 'price-low', 'name-az'];
    const sortSelect = document.getElementById('sortSelect');
    
    if (!sortSelect) {
        console.error('Sort select element not found!');
        return;
    }
    
    sortOptions.forEach((option, index) => {
        console.log(`\n--- Test ${index + 1}: ${option} ---`);
        sortSelect.value = option;
        sortProducts();
        
        if (filteredProducts.length > 0) {
            console.log('Top 3 results:');
            filteredProducts.slice(0, 3).forEach((product, idx) => {
                const price = formatPrice(product.price);
                const date = product.date ? product.date.toLocaleDateString('th-TH') : 'ไม่ระบุ';
                console.log(`  ${idx + 1}. ${product.name}`);
                console.log(`     ราคา: ${price}, วันที่: ${date}`);
            });
        }
    });
    
    console.log('\n=== Sort testing completed ===');
};

// เพิ่ม Event Listener สำหรับ sortSelect
document.addEventListener('DOMContentLoaded', function() {
    // รอให้ DOM โหลดเสร็จก่อน
    setTimeout(() => {
        const sortSelect = document.getElementById('sortSelect');
        if (sortSelect) {
            // ลบ event listener เก่าออกก่อน (ถ้ามี)
            sortSelect.removeEventListener('change', applySorting);
            // เพิ่ม event listener ใหม่
            sortSelect.addEventListener('change', applySorting);
            console.log('Sort select event listener attached');
        } else {
            console.warn('Sort select element not found during setup');
        }
    }, 1000);
});