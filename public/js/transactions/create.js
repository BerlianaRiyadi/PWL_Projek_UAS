class TransactionManager {
    constructor() {
        this.cart = [];
        this.products = [];
        this.init();
    }

    init() {
        this.loadQuickProducts();
        this.setupEventListeners();
        this.updateCartDisplay();
    }

    setupEventListeners() {
        // Search functionality
        $('#searchProduct').on('input', this.debounce(this.searchProducts.bind(this), 300));
        $('#searchBtn').on('click', this.searchProducts.bind(this));

        // Quick products
        $(document).on('click', '.quick-product', this.addQuickProduct.bind(this));

        // Cart controls
        $(document).on('click', '.increase-qty', this.increaseQuantity.bind(this));
        $(document).on('click', '.decrease-qty', this.decreaseQuantity.bind(this));
        $(document).on('input', '.quantity-input', this.updateQuantity.bind(this));
        $(document).on('click', '.remove-item', this.removeItem.bind(this));

        // Payment
        $('#jumlah_bayar').on('input', this.calculateChange.bind(this));
        $('#transactionForm').on('submit', this.processTransaction.bind(this));
        $('#resetCartBtn').on('click', this.resetCart.bind(this));

        // Modal buttons
        $('#printReceiptBtn').on('click', this.printReceipt.bind(this));
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    async searchProducts() {
        const keyword = $('#searchProduct').val().trim();

        if (keyword.length < 2) {
            $('#searchResults').hide();
            return;
        }

        try {
            const response = await fetch(`/api/products/search?search=${encodeURIComponent(keyword)}`);
            const products = await response.json();

            this.displaySearchResults(products);
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    displaySearchResults(products) {
        const resultsContainer = $('#searchResults');

        if (products.length === 0) {
            resultsContainer.html('<div class="p-2 text-muted">Produk tidak ditemukan</div>');
        } else {
            const resultsHtml = products.map(product => `
                <div class="search-result-item p-2 border-bottom" 
                     data-product-id="${product.id}"
                     data-product-name="${product.nama_produk}"
                     data-product-price="${product.harga}"
                     data-product-stock="${product.stok}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${product.nama_produk}</strong>
                            <br>
                            <small class="text-success">Rp ${this.formatNumber(product.harga)}</small>
                            <small class="text-muted ms-2">Stok: ${product.stok}</small>
                        </div>
                        <button class="btn btn-sm btn-outline-primary add-search-product">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            `).join('');

            resultsContainer.html(resultsHtml);
        }

        resultsContainer.show();

        // Add event listeners to search result items
        $(document).on('click', '.add-search-product', (e) => {
            const item = $(e.target).closest('.search-result-item');
            this.addProductFromSearch(item);
        });

        // Hide results when clicking outside
        $(document).on('click', (e) => {
            if (!$(e.target).closest('#searchResults').length && !$(e.target).is('#searchProduct')) {
                resultsContainer.hide();
            }
        });
    }

    addProductFromSearch(item) {
        const productId = item.data('product-id');
        const productName = item.data('product-name');
        const productPrice = item.data('product-price');
        const productStock = item.data('product-stock');

        this.addToCart({
            id: productId,
            nama_produk: productName,
            harga: productPrice,
            stok: productStock
        });

        $('#searchResults').hide();
        $('#searchProduct').val('');
    }

    addQuickProduct(e) {
        const button = $(e.currentTarget);
        const productData = {
            id: button.data('product-id'),
            nama_produk: button.data('product-name'),
            harga: button.data('product-price'),
            stok: button.data('product-stock')
        };

        this.addToCart(productData);
    }

    addToCart(product) {
        // Check if product already in cart
        const existingItem = this.cart.find(item => item.id === product.id);

        if (existingItem) {
            if (existingItem.quantity >= product.stok) {
                this.showAlert('Stok tidak mencukupi!', 'error');
                return;
            }
            existingItem.quantity += 1;
            existingItem.subtotal = existingItem.quantity * existingItem.harga;
        } else {
            if (product.stok <= 0) {
                this.showAlert('Stok produk habis!', 'error');
                return;
            }

            this.cart.push({
                id: product.id,
                nama: product.nama_produk,
                harga: product.harga,
                quantity: 1,
                subtotal: product.harga
            });
        }

        this.updateCartDisplay();
        this.updatePaymentSection();
        this.showAlert(`${product.nama_produk} ditambahkan ke keranjang`, 'success');
    }

    increaseQuantity(e) {
        const index = $(e.currentTarget).data('index');
        const item = this.cart[index];

        // Check stock (you might want to fetch current stock from server)
        if (item.quantity >= this.getProductStock(item.id)) {
            this.showAlert('Stok tidak mencukupi!', 'error');
            return;
        }

        item.quantity += 1;
        item.subtotal = item.quantity * item.harga;
        this.updateCartDisplay();
        this.updatePaymentSection();
    }

    decreaseQuantity(e) {
        const index = $(e.currentTarget).data('index');
        const item = this.cart[index];

        if (item.quantity > 1) {
            item.quantity -= 1;
            item.subtotal = item.quantity * item.harga;
            this.updateCartDisplay();
            this.updatePaymentSection();
        }
    }

    updateQuantity(e) {
        const index = $(e.currentTarget).data('index');
        const newQuantity = parseInt($(e.currentTarget).val()) || 1;
        const item = this.cart[index];

        if (newQuantity < 1) {
            $(e.currentTarget).val(1);
            return;
        }

        if (newQuantity > this.getProductStock(item.id)) {
            this.showAlert('Stok tidak mencukupi!', 'error');
            $(e.currentTarget).val(item.quantity);
            return;
        }

        item.quantity = newQuantity;
        item.subtotal = item.quantity * item.harga;
        this.updateCartDisplay();
        this.updatePaymentSection();
    }

    removeItem(e) {
        const index = $(e.currentTarget).data('index');
        const itemName = this.cart[index].nama;

        this.cart.splice(index, 1);
        this.updateCartDisplay();
        this.updatePaymentSection();
        this.showAlert(`${itemName} dihapus dari keranjang`, 'info');
    }

    updateCartDisplay() {
        const cartItemsContainer = $('#cartItems');
        const emptyCart = $('#emptyCart');
        const totalAmount = $('#totalAmount');
        const displayTotal = $('#displayTotal');

        if (this.cart.length === 0) {
            cartItemsContainer.html('');
            emptyCart.show();
            totalAmount.text('Rp 0');
            displayTotal.text('Rp 0');
            $('#totalInput').val(0);
            return;
        }

        emptyCart.hide();

        const cartHtml = this.cart.map((item, index) => `
            <tr>
                <td>
                    <strong>${item.nama}</strong><br>
                    <small class="text-muted">Rp ${this.formatNumber(item.harga)}</small>
                </td>
                <td>Rp ${this.formatNumber(item.harga)}</td>
                <td>
                    <div class="quantity-controls">
                        <button type="button" class="btn btn-sm btn-outline-secondary decrease-qty" data-index="${index}">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" class="form-control form-control-sm quantity-input" 
                        value="${item.quantity}" min="1" data-index="${index}">
                        <button type="button" class="btn btn-sm btn-outline-secondary increase-qty" data-index="${index}">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </td>
                <td>Rp ${this.formatNumber(item.subtotal)}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger remove-item" data-index="${index}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');

        cartItemsContainer.html(cartHtml);

        const total = this.cart.reduce((sum, item) => sum + item.subtotal, 0);
        totalAmount.text(`Rp ${this.formatNumber(total)}`);
        displayTotal.text(`Rp ${this.formatNumber(total)}`);
        $('#totalInput').val(total);

        // Enable/disable payment input
        $('#jumlah_bayar').prop('disabled', total === 0);
        $('#processBtn').prop('disabled', total === 0);
    }

    updatePaymentSection() {
        this.calculateChange();
    }

    calculateChange() {
        const total = this.cart.reduce((sum, item) => sum + item.subtotal, 0);
        const payment = parseInt($('#jumlah_bayar').val()) || 0;
        const change = payment - total;

        const changeElement = $('#displayKembalian');

        if (change >= 0) {
            changeElement.text(`Rp ${this.formatNumber(change)}`).removeClass('text-danger').addClass('text-success');
        } else {
            changeElement.text(`-Rp ${this.formatNumber(Math.abs(change))}`).removeClass('text-success').addClass('text-danger');
        }
    }

    async processTransaction(e) {
        e.preventDefault();

        const total = this.cart.reduce((sum, item) => sum + item.subtotal, 0);
        const payment = parseInt($('#jumlah_bayar').val()) || 0;

        if (payment < total) {
            this.showAlert('Jumlah bayar kurang dari total belanja!', 'error');
            return;
        }

        if (this.cart.length === 0) {
            this.showAlert('Keranjang belanja kosong!', 'error');
            return;
        }

        // Show loading modal
        $('#loadingModal').modal('show');

        try {
            const formData = {
                items: this.cart.map(item => ({
                    product_id: item.id,
                    quantity: item.quantity
                })),
                jumlah_bayar: payment,
                _token: $('meta[name="csrf-token"]').attr('content')
            };

            const response = await fetch('/transaksi', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            $('#loadingModal').modal('hide');

            if (result.success) {
                this.showSuccessModal(result);
                this.resetCart();
            } else {
                this.showAlert(result.message, 'error');
            }

        } catch (error) {
            $('#loadingModal').modal('hide');
            console.error('Transaction error:', error);
            this.showAlert('Terjadi kesalahan saat memproses transaksi', 'error');
        }
    }

    showSuccessModal(result) {
        $('#successMessage').html(`
            <strong>ID Transaksi: #${result.transaction_id}</strong><br>
            Total: Rp ${this.formatNumber(result.total)}<br>
            Bayar: Rp ${this.formatNumber(result.jumlah_bayar)}<br>
            Kembali: Rp ${this.formatNumber(result.kembalian)}
        `);
        $('#successModal').modal('show');

        // Store transaction ID for receipt printing
        $('#successModal').data('transaction-id', result.transaction_id);
    }

    printReceipt() {
        const transactionId = $('#successModal').data('transaction-id');
        if (transactionId) {
            window.open(`/transaksi/${transactionId}/receipt`, '_blank');
        }
    }

    resetCart() {
        this.cart = [];
        this.updateCartDisplay();
        $('#jumlah_bayar').val('');
        this.calculateChange();
        this.showAlert('Keranjang berhasil direset', 'info');
    }

    loadQuickProducts() {
        // Products are already loaded in the blade template
        console.log('Quick products loaded');
    }

    getProductStock(productId) {
        // This should ideally fetch current stock from server
        // For now, we'll use a conservative approach
        const quickProduct = $(`.quick-product[data-product-id="${productId}"]`);
        if (quickProduct.length) {
            return quickProduct.data('product-stock');
        }
        return 999; // Fallback
    }

    formatNumber(number) {
        return new Intl.NumberFormat('id-ID').format(number);
    }

    showAlert(message, type = 'info') {
        // You can implement a toast or alert system here
        console.log(`${type.toUpperCase()}: ${message}`);

        // Simple alert for now
        const alertClass = type === 'error' ? 'alert-danger' :
            type === 'success' ? 'alert-success' : 'alert-info';

        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
                style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        $('body').append(alertHtml);

        // Auto remove after 3 seconds
        setTimeout(() => {
            $('.alert').alert('close');
        }, 3000);
    }
}

// Initialize when document is ready
$(document).ready(function () {
    window.transactionManager = new TransactionManager();
});