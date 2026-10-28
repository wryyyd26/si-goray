
<div class="payment-instruction payment-warning">
    Pilih metode pembayaran untuk menyelesaikan pemesanan tiket SI-GORAY.
</div>

<form method="POST" action="{{ route('pembayaran.midtrans') }}"
      id="midtrans-payment-form">
    @csrf

    <input type="hidden"
           name="jenis_pemesan"
           value="{{ $jenisPemesan ?? 'warga-kota' }}">

    <h2 class="payment-method-title">Metode Pembayaran</h2>
    <p class="payment-method-subtitle">
        Pilih metode pembayaran yang kamu inginkan.
    </p>

    <div class="payment-method-options">

        <label class="payment-method-option">
            <input type="radio"
                   name="metode_pembayaran"
                   value="bank_transfer"
                   checked
                   required>

            <span class="payment-method-icon">
                <span class="material-symbols-rounded">
                    account_balance
                </span>
            </span>

            <span class="payment-method-description">
                <strong>Transfer Bank</strong>
                <small>BCA, BNI, BRI, Mandiri, dan bank yang tersedia</small>
            </span>

            <span class="material-symbols-rounded payment-radio-icon">
                radio_button_unchecked
            </span>
        </label>

        <label class="payment-method-option">
            <input type="radio"
                   name="metode_pembayaran"
                   value="qris"
                   required>

            <span class="payment-method-icon">
                <span class="material-symbols-rounded">
                    qr_code_2
                </span>
            </span>

            <span class="payment-method-description">
                <strong>QRIS</strong>
                <small>Bayar melalui aplikasi mobile banking atau e-wallet yang mendukung QRIS</small>
            </span>

            <span class="material-symbols-rounded payment-radio-icon">
                radio_button_unchecked
            </span>
        </label>

        <label class="payment-method-option">
            <input type="radio"
                   name="metode_pembayaran"
                   value="ewallet"
                   required>

            <span class="payment-method-icon">
                <span class="material-symbols-rounded">
                    account_balance_wallet
                </span>
            </span>

            <span class="payment-method-description">
                <strong>E-Wallet</strong>
                <small>GoPay, ShopeePay, atau opsi yang tersedia</small>
            </span>

            <span class="material-symbols-rounded payment-radio-icon">
                radio_button_unchecked
            </span>
        </label>

    </div>

    <div class="payment-checkout-summary">
        <span>Total yang harus dibayar</span>
        <strong>
            Rp {{ number_format($total, 0, ',', '.') }}
        </strong>
    </div>

    <button type="submit" class="payment-button">
        Lanjutkan Pembayaran
        <span class="material-symbols-rounded">arrow_forward</span>
    </button>

    <p class="payment-disclaimer">
        Pembayaran diproses secara aman melalui Midtrans.
        Status tiket diperbarui setelah pembayaran diverifikasi.
    </p>
</form>
