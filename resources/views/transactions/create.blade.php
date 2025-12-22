@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">Transaksi Baru</h1>
                <a href="{{ route('transaksi.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>

            <div class="row">
                <!-- Kolom Kiri: Produk Tersedia -->
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-boxes"></i> Produk Tersedia
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Search Bar di Header Produk -->
                            <div class="mb-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="searchProduct"
                                        placeholder="Cari produk..." autocomplete="off">
                                    <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div id="searchResults" class="mt-2 border rounded bg-white shadow-sm"
                                    style="display: none; max-height: 250px; overflow-y: auto; position: absolute; z-index: 1000; width: 100%;">
                                </div>
                            </div>

                            <!-- Grid Produk Tersedia -->
                            <div class="row g-2" id="productsGrid">
                                @foreach($products as $product)
                                <div class="col-4 col-md-3 col-lg-4">
                                    <div class="product-card border rounded p-2 text-center bg-light cursor-pointer"
                                        data-product-id="{{ $product->id }}"
                                        data-product-name="{{ $product->nama_produk }}"
                                        data-product-price="{{ $product->harga }}"
                                        data-product-stock="{{ $product->stok }}">
                                        <small class="fw-bold d-block text-truncate product-name">{{ $product->nama_produk }}</small>
                                        <small class="text-success fw-bold d-block product-price">
                                            Rp {{ number_format($product->harga, 0, ',', '.') }}
                                        </small>
                                        <small class="text-muted d-block product-stock">Stok: {{ $product->stok }}</small>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <!-- Selected Product Form (Hidden) -->
                            <form action="{{ route('transaksi.store') }}" method="POST" id="addToCartForm" style="display: none;">
                                @csrf
                                <input type="hidden" name="product_id" id="selectedProductId">
                                <input type="hidden" name="quantity" id="selectedQuantity" value="1">
                                <input type="hidden" name="action" value="add_to_cart">
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Keranjang Belanja & Pembayaran -->
                <div class="col-lg-4">
                    <!-- Section Pembayaran (Besar & Jelas) -->
                    @if(session('cart') && count(session('cart')) > 0)
                    <div class="card border-success mb-2">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-credit-card"></i> Pembayaran Cash
                            </h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('transaksi.store') }}" method="POST">
                                @csrf
                                <div class="payment-section">
                                    <!-- Total Belanja -->
                                    <div class="mb-4 text-center bg-light p-4 rounded">
                                        <label class="form-label fw-bold fs-5 mb-2">Total Belanja</label>
                                        <h1 class="text-primary fw-bold mb-0" id="total-belanja">
                                            Rp {{ number_format($total ?? 0, 0, ',', '.') }}
                                        </h1>
                                    </div>

                                    <!-- Input Jumlah Bayar -->
                                    <div class="mb-4">
                                        <label for="jumlah_bayar" class="form-label fw-bold fs-5">
                                            Jumlah Bayar <span class="text-danger">*</span>
                                        </label>
                                        
                                        <!-- Shortcut Uang Buttons -->
                                        <div class="d-grid gap-2 mb-3" style="grid-template-columns: repeat(4, 1fr);">
                                            <button type="button" class="btn btn-outline-success btn-lg money-shortcut" data-amount="10000">
                                                <small class="fw-bold fs-5">10.000</small>
                                            </button>
                                            <button type="button" class="btn btn-outline-success btn-lg money-shortcut" data-amount="20000">
                                                <small class="fw-bold fs-5">20.000</small>
                                            </button>
                                            <button type="button" class="btn btn-outline-success btn-lg money-shortcut" data-amount="50000">
                                                <small class="fw-bold fs-5">50.000</small>
                                            </button>
                                            <button type="button" class="btn btn-outline-success btn-lg money-shortcut" data-amount="100000">
                                                <small class="fw-bold fs-5">100.000</small>
                                            </button>
                                        </div>

                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text bg-light fw-bold fs-2">Rp</span>
                                            <input type="number"
                                                class="form-control form-control-lg text-start"
                                                id="jumlah_bayar"
                                                name="jumlah_bayar"
                                                min="{{ $total ?? 0 }}"
                                                value="{{ old('jumlah_bayar') }}"
                                                required
                                                placeholder="0"
                                                style="font-size: 3rem;">
                                        </div>
                                        @error('jumlah_bayar')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Kembalian -->
                                    <div class="mb-4 text-center bg-success bg-opacity-10 p-4 rounded border border-success">
                                        <label class="form-label  fw-bold fs-5 mb-2">Kembalian</label>
                                        <h1 class="text-success fw-bold mb-0" id="kembalian">
                                            Rp 0
                                        </h1>
                                    </div>

                                    <!-- Tombol Proses -->
                                    <div class="d-grid">
                                        <button type="submit" name="action" value="process_transaction"
                                            class="btn btn-success btn-lg fw-bold py-3">
                                            <i class="fas fa-check-circle"></i> PROSES TRANSAKSI
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif

                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white py-2">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-shopping-cart"></i> Keranjang Belanja
                                @if(session('cart') && count(session('cart')) > 0)
                                <span class="badge bg-light text-primary ms-2">{{ count(session('cart')) }} item</span>
                                @endif
                            </h6>
                        </div>
                        <div class="card-body p-2">
                            <!-- Cart Items -->
                            @if(session('cart') && count(session('cart')) > 0)
                            <div class="table-responsive mb-2">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="35%">Produk</th>
                                            <th width="20%" class="text-end">Harga</th>
                                            <th width="10%" class="text-center">Qty</th>
                                            <th width="20%" class="text-end">Subtotal</th>
                                            <th width="15%" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $total = 0; @endphp
                                        @foreach(session('cart') as $id => $item)
                                        @php
                                        $subtotal = $item['harga'] * $item['quantity'];
                                        $total += $subtotal;
                                        @endphp
                                        <tr>
                                            <td>
                                                <small class="fw-bold">{{ $item['nama'] }}</small>
                                            </td>
                                            <td class="text-end">
                                                <small>Rp {{ number_format($item['harga'], 0, ',', '.') }}</small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary">{{ $item['quantity'] }}</span>
                                            </td>
                                            <td class="text-end">
                                                <small class="fw-bold">Rp {{ number_format($subtotal, 0, ',', '.') }}</small>
                                            </td>
                                            <td class="text-center">
                                                <form action="{{ route('transaksi.store') }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" name="remove_item" value="{{ $id }}"
                                                        class="btn btn-sm btn-danger py-0 px-2">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Reset Cart Button -->
                            <form action="{{ route('transaksi.store') }}" method="POST">
                                @csrf
                                <button type="submit" name="action" value="reset_cart"
                                    class="btn btn-sm btn-warning">
                                    <i class="fas fa-redo"></i> Reset Keranjang
                                </button>
                            </form>
                            @else
                            <!-- Empty Cart Message -->
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                                <p class="mb-0 small">Keranjang Kosong</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Input Jumlah -->
<div class="modal fade" id="quantityModal" tabindex="-1" aria-labelledby="quantityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="quantityModalLabel">
                    <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <h5 class="text-primary" id="modalProductName"></h5>
                    <p class="text-muted mb-0">
                        Harga: <span class="fw-bold text-success" id="modalProductPrice"></span>
                    </p>
                    <p class="text-muted">
                        Stok tersedia: <span class="fw-bold" id="modalProductStock"></span>
                    </p>
                </div>
                
                <div class="mb-3">
                    <label for="modalQuantityInput" class="form-label fw-bold">Jumlah <span class="text-danger">*</span></label>
                    <input type="number" class="form-control form-control-lg text-center" id="modalQuantityInput" 
                        min="1" value="1" style="font-size: 2rem; font-weight: bold;">
                    <div class="invalid-feedback" id="quantityError"></div>
                </div>

                <!-- Quick Quantity Buttons -->
                <div class="d-flex gap-2 mb-3">
                    <button type="button" class="btn btn-outline-secondary flex-fill quick-qty" data-qty="1">1</button>
                    <button type="button" class="btn btn-outline-secondary flex-fill quick-qty" data-qty="5">5</button>
                    <button type="button" class="btn btn-outline-secondary flex-fill quick-qty" data-qty="10">10</button>
                    <button type="button" class="btn btn-outline-secondary flex-fill quick-qty" data-qty="20">20</button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="button" class="btn btn-primary btn-lg" id="confirmAddToCart">
                    <i class="fas fa-check"></i> Tambah ke Keranjang
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
@if(session('success') && session('transaction_id'))
<div class="modal fade show" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="false" style="display: block; background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="successModalLabel">
                    <i class="fas fa-check-circle"></i> Transaksi Berhasil!
                </h5>
            </div>
            <div class="modal-body text-center">
                <div class="mb-3">
                    <i class="fas fa-check-circle fa-4x text-success"></i>
                </div>
                <h5 class="mb-3">Transaksi berhasil diproses</h5>
                <div class="bg-light rounded p-3 mb-3">
                    <p class="mb-1"><strong>ID Transaksi: #{{ session('transaction_id') }}</strong></p>
                    <p class="mb-1">Total: Rp {{ number_format(session('total'), 0, ',', '.') }}</p>
                    <p class="mb-1">Bayar: Rp {{ number_format(session('jumlah_bayar'), 0, ',', '.') }}</p>
                    <p class="mb-0">Kembali: Rp {{ number_format(session('kembalian'), 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="modal-footer">
                <a href="{{ route('transaksi.receipt', ['transaksi' => session('transaction_id')]) }}" target="_blank"
                    class="btn btn-secondary">
                    <i class="fas fa-print"></i> Cetak Struk
                </a>
                <a href="{{ route('transaksi.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Transaksi Baru
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    // Auto show modal when page loads
    document.addEventListener('DOMContentLoaded', function() {
        var successModal = document.getElementById('successModal');
        if (successModal) {
            successModal.style.display = 'block';
            successModal.classList.add('show');
        }
    });

    document.addEventListener('click', function(event) {
        var successModal = document.getElementById('successModal');
        if (successModal && event.target === successModal) {
            window.location.href = "{{ route('transaksi.create') }}";
        }
    });
</script>
@endif

<!-- JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchProduct');
        const searchResults = document.getElementById('searchResults');
        const clearSearch = document.getElementById('clearSearch');
        const productsGrid = document.getElementById('productsGrid');
        const addToCartForm = document.getElementById('addToCartForm');
        const jumlahBayarInput = document.getElementById('jumlah_bayar');
        const kembalianElement = document.getElementById('kembalian');
        
        // Modal elements
        const quantityModal = new bootstrap.Modal(document.getElementById('quantityModal'));
        const modalQuantityInput = document.getElementById('modalQuantityInput');
        const modalProductName = document.getElementById('modalProductName');
        const modalProductPrice = document.getElementById('modalProductPrice');
        const modalProductStock = document.getElementById('modalProductStock');
        const confirmAddToCart = document.getElementById('confirmAddToCart');
        const quantityError = document.getElementById('quantityError');
        
        let currentProduct = null;
        let searchTimeout;

        // Fungsi untuk mencari produk
        function searchProducts(keyword) {
            if (keyword.length < 1) {
                searchResults.style.display = 'none';
                showAllProducts();
                return;
            }

            fetch(`/transaksi/search/products?search=${encodeURIComponent(keyword)}`)
                .then(response => response.json())
                .then(products => {
                    displaySearchResults(products);
                })
                .catch(error => {
                    console.error('Error searching products:', error);
                    searchResults.innerHTML = '<div class="p-3 text-center text-danger">Error saat mencari produk</div>';
                    searchResults.style.display = 'block';
                });
        }

        // Fungsi untuk menampilkan semua produk
        function showAllProducts() {
            const productCards = document.querySelectorAll('.product-card');
            productCards.forEach(card => {
                card.style.display = 'block';
            });
        }

        // Fungsi untuk menampilkan hasil pencarian
        function displaySearchResults(products) {
            if (products.length === 0) {
                searchResults.innerHTML = '<div class="p-3 text-center text-muted">Produk tidak ditemukan</div>';
            } else {
                searchResults.innerHTML = products.map(product => `
                <div class="search-result-item p-3 border-bottom cursor-pointer" 
                     data-product-id="${product.id}"
                     data-product-name="${product.nama_produk}"
                     data-product-price="${product.harga}"
                     data-product-stock="${product.stok}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong class="d-block">${product.nama_produk}</strong>
                            <small class="text-success">Rp ${formatNumber(product.harga)}</small>
                            <small class="text-muted ms-2">Stok: ${product.stok}</small>
                        </div>
                        <i class="fas fa-plus-circle fa-2x text-primary"></i>
                    </div>
                </div>
            `).join('');
            }
            searchResults.style.display = 'block';

            // Sembunyikan produk grid saat search aktif
            const productCards = document.querySelectorAll('.product-card');
            productCards.forEach(card => {
                card.style.display = 'none';
            });
        }

        // Format number dengan separator
        function formatNumber(number) {
            return new Intl.NumberFormat('id-ID').format(number);
        }

        // Format Rupiah
        function formatRupiah(angka) {
            return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        // Hitung kembalian
        function hitungKembalian() {
            const totalBelanja = parseFloat("{{ $total ?? 0 }}");
            const jumlahBayar = parseFloat(jumlahBayarInput.value) || 0;

            if (jumlahBayar >= totalBelanja) {
                const kembalian = jumlahBayar - totalBelanja;
                kembalianElement.textContent = formatRupiah(kembalian);
                kembalianElement.className = 'text-success fw-bold mb-0';
            } else {
                const kurang = totalBelanja - jumlahBayar;
                kembalianElement.className = 'text-danger fw-bold mb-0';
            }
        }

        // Show modal with product info
        function showQuantityModal(element) {
            currentProduct = {
                id: element.getAttribute('data-product-id'),
                name: element.getAttribute('data-product-name'),
                price: element.getAttribute('data-product-price'),
                stock: parseInt(element.getAttribute('data-product-stock'))
            };

            // Validasi stok
            if (currentProduct.stock <= 0) {
                alert(`Stok ${currentProduct.name} habis!`);
                return;
            }

            // Set modal content
            modalProductName.textContent = currentProduct.name;
            modalProductPrice.textContent = formatRupiah(parseInt(currentProduct.price));
            modalProductStock.textContent = currentProduct.stock;
            modalQuantityInput.value = 1;
            modalQuantityInput.max = currentProduct.stock;
            modalQuantityInput.classList.remove('is-invalid');
            quantityError.textContent = '';

            // Show modal
            quantityModal.show();
            
            // Focus on input after modal is shown
            document.getElementById('quantityModal').addEventListener('shown.bs.modal', function () {
                modalQuantityInput.focus();
                modalQuantityInput.select();
            }, { once: true });
        }

        // Event listener untuk search input
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const keyword = this.value.trim();

            searchTimeout = setTimeout(() => {
                searchProducts(keyword);
            }, 300);
        });

        // Clear search
        clearSearch.addEventListener('click', function() {
            searchInput.value = '';
            searchResults.style.display = 'none';
            showAllProducts();
        });

        // Event listener untuk hasil pencarian
        searchResults.addEventListener('click', function(e) {
            const item = e.target.closest('.search-result-item');
            if (item) {
                showQuantityModal(item);
            }
        });

        // Event listener untuk klik produk di grid
        productsGrid.addEventListener('click', function(e) {
            const card = e.target.closest('.product-card');
            if (card) {
                showQuantityModal(card);
            }
        });

        // Quick quantity buttons
        document.querySelectorAll('.quick-qty').forEach(btn => {
            btn.addEventListener('click', function() {
                const qty = parseInt(this.getAttribute('data-qty'));
                if (qty <= currentProduct.stock) {
                    modalQuantityInput.value = qty;
                    modalQuantityInput.classList.remove('is-invalid');
                    quantityError.textContent = '';
                } else {
                    modalQuantityInput.value = currentProduct.stock;
                }
            });
        });

        // Validate quantity on input
        modalQuantityInput.addEventListener('input', function() {
            const qty = parseInt(this.value);
            
            if (isNaN(qty) || qty <= 0) {
                this.classList.add('is-invalid');
                quantityError.textContent = 'Masukkan jumlah yang valid!';
            } else if (qty > currentProduct.stock) {
                this.classList.add('is-invalid');
                quantityError.textContent = `Stok hanya tersedia ${currentProduct.stock}!`;
            } else {
                this.classList.remove('is-invalid');
                quantityError.textContent = '';
            }
        });

        // Confirm add to cart
        confirmAddToCart.addEventListener('click', function() {
            const qty = parseInt(modalQuantityInput.value);
            
            if (isNaN(qty) || qty <= 0) {
                modalQuantityInput.classList.add('is-invalid');
                quantityError.textContent = 'Masukkan jumlah yang valid!';
                return;
            }

            if (qty > currentProduct.stock) {
                modalQuantityInput.classList.add('is-invalid');
                quantityError.textContent = `Stok hanya tersedia ${currentProduct.stock}!`;
                return;
            }

            // Set form values and submit
            document.getElementById('selectedProductId').value = currentProduct.id;
            document.getElementById('selectedQuantity').value = qty;
            addToCartForm.submit();
        });

        // Enter key to confirm in modal
        modalQuantityInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                confirmAddToCart.click();
            }
        });

        // Money shortcut buttons
        let lastClickedButton = null;
        let lastClickTime = 0;
        const doubleClickThreshold = 500; // milliseconds

        document.querySelectorAll('.money-shortcut').forEach(btn => {
            btn.addEventListener('click', function() {
                const amount = parseInt(this.getAttribute('data-amount'));
                const currentTime = Date.now();
                const currentValue = parseInt(jumlahBayarInput.value) || 0;

                // Check if same button clicked within threshold (double click)
                if (lastClickedButton === this && (currentTime - lastClickTime) < doubleClickThreshold) {
                    // Add amount to current value
                    jumlahBayarInput.value = currentValue + amount;
                } else {
                    // First click or different button - replace value
                    jumlahBayarInput.value = currentValue + amount;
                }

                lastClickedButton = this;
                lastClickTime = currentTime;

                // Trigger kembalian calculation
                hitungKembalian();

                // Visual feedback
                this.classList.add('btn-success');
                this.classList.remove('btn-outline-success');
                setTimeout(() => {
                    this.classList.remove('btn-success');
                    this.classList.add('btn-outline-success');
                }, 200);
            });
        });

        // Event listener untuk input jumlah bayar
        if (jumlahBayarInput) {
            jumlahBayarInput.addEventListener('input', hitungKembalian);
            jumlahBayarInput.addEventListener('change', hitungKembalian);

            // Hitung kembalian saat halaman pertama kali dimuat
            if (jumlahBayarInput.value) {
                hitungKembalian();
            }
        }

        // Close search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });

        // Enter key untuk search
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const keyword = this.value.trim();
                if (keyword.length >= 1) {
                    searchProducts(keyword);
                }
            }
        });
    });
</script>

<style>
    .cursor-pointer {
        cursor: pointer;
    }

    .search-result-item:hover {
        background-color: #f8f9fa;
    }

    .product-card {
        transition: all 0.2s ease;
        height: 100px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .product-card:hover {
        background-color: #e9f7fe !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        border-color: #0dcaf0 !important;
    }

    .product-card:active {
        transform: scale(0.98);
    }

    .product-name {
        font-size: 0.85rem;
    }

    .product-price {
        font-size: 0.9rem;
    }

    .product-stock {
        font-size: 0.8rem;
    }

    #searchResults {
        border: 1px solid #dee2e6;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .payment-section {
        background-color: #f8fff8;
        padding: 30px;
        border-radius: 10px;
        border: 2px solid #d4edda;
    }

    /* Styling untuk area pembayaran agar lebih menonjol */
    #total-belanja {
        font-size: 2.5rem;
        letter-spacing: -1px;
    }

    #kembalian {
        font-size: 2.5rem;
        letter-spacing: -1px;
    }

    #jumlah_bayar {
        text-align: right;
        font-weight: bold;
    }

    /* Compact table styling */
    .table-sm td,
    .table-sm th {
        padding: 0.4rem;
        font-size: 0.875rem;
    }

    /* Badge styling */
    .badge {
        font-size: 0.875rem;
        padding: 0.35em 0.65em;
    }

    /* Modal styling */
    #modalQuantityInput::-webkit-inner-spin-button,
    #modalQuantityInput::-webkit-outer-spin-button {
        opacity: 1;
        height: 40px;
    }

    .quick-qty {
        font-size: 1.2rem;
        font-weight: bold;
        padding: 10px;
    }

    .quick-qty:hover {
        background-color: #0d6efd;
        color: white;
        border-color: #0d6efd;
    }

    /* Money shortcut buttons */
    .money-shortcut {
        padding: 12px 8px;
        transition: all 0.2s ease;
    }

    .money-shortcut:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .money-shortcut:active {
        transform: scale(0.95);
    }

    .money-shortcut small {
        font-size: 0.7rem;
        opacity: 0.8;
    }
</style>
@endsection